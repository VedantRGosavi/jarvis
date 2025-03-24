<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/Purchase.php';

use App\Utils\Response;
use App\Utils\Auth;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Purchase;

// Load Stripe SDK
require_once BASE_PATH . '/vendor/autoload.php';
$stripeConfig = require BASE_PATH . '/app/config/stripe.php';
\Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $api_segments[1] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// Special case: Config endpoint doesn't require authentication
if ($action === 'config' && $method === 'GET') {
    try {
        Response::success([
            'publishable_key' => $stripeConfig['publishable_key']
        ]);
        exit;
    } catch (\Exception $e) {
        Response::error('Failed to get Stripe configuration', 500);
        exit;
    }
}

// Validate authentication for protected endpoints
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$isAuthenticated = false;
$userId = null;

if ($token && Auth::validateToken($token)) {
    $isAuthenticated = true;
    $userId = Auth::getUserIdFromToken($token);
} else {
    Response::error('Unauthorized', 401);
    exit;
}

// Get authenticated user
$userModel = new User();
$user = $userModel->getById($userId);

if (!$user) {
    Response::error('User not found', 404);
    exit;
}

// Route to appropriate payment handler
switch ($action) {
    case 'create-subscription':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            exit;
        }

        try {
            // Create Stripe customer if not exists
            $subscriptionModel = new Subscription();
            $stripeCustomerId = $subscriptionModel->getStripeCustomerId($userId);

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $subscriptionModel->saveStripeCustomerId($userId, $stripeCustomerId);
            }

            // Create subscription
            $subscription = \Stripe\Subscription::create([
                'customer' => $stripeCustomerId,
                'items' => [
                    ['price' => $stripeConfig['subscription_price_id']]
                ],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
                'trial_period_days' => $stripeConfig['trial_period_days'] ?? 7
            ]);

            $subscriptionModel->createSubscription(
                $userId,
                $subscription->id,
                'trialing',
                date('Y-m-d H:i:s', $subscription->current_period_end)
            );

            // Update user status
            $userModel->updateSubscription($userId, 'trialing');

            Response::success([
                'subscription_id' => $subscription->id,
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
                'status' => $subscription->status
            ]);

        } catch (\Exception $e) {
            Response::error('Subscription creation failed: ' . $e->getMessage(), 400);
        }
        break;

    case 'purchase-game':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            exit;
        }

        if (!isset($data['game_id'])) {
            Response::error('Game ID required', 400);
            exit;
        }

        try {
            $gameId = $data['game_id'];
            $appConfig = require BASE_PATH . '/app/config/app.php';

            // Validate game ID
            if (!isset($appConfig['supported_games'][$gameId])) {
                Response::error('Invalid game ID', 400);
                exit;
            }

            $gamePrice = $appConfig['supported_games'][$gameId]['price'];

            // Create Stripe customer if not exists
            $subscriptionModel = new Subscription();
            $stripeCustomerId = $subscriptionModel->getStripeCustomerId($userId);

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $subscriptionModel->saveStripeCustomerId($userId, $stripeCustomerId);
            }

            // Create payment intent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $gamePrice,
                'currency' => 'usd',
                'customer' => $stripeCustomerId,
                'metadata' => [
                    'user_id' => $userId,
                    'game_id' => $gameId
                ]
            ]);

            // Create purchase record
            $purchaseModel = new Purchase();
            $purchaseModel->createPurchase(
                $userId,
                $gameId,
                $paymentIntent->id,
                'pending',
                $gamePrice
            );

            Response::success([
                'client_secret' => $paymentIntent->client_secret
            ]);

        } catch (\Exception $e) {
            Response::error('Payment creation failed: ' . $e->getMessage(), 400);
        }
        break;

    case 'subscription':
        if ($method !== 'GET') {
            Response::error('Method not allowed', 405);
            exit;
        }

        try {
            $subscriptionModel = new Subscription();
            $subscription = $subscriptionModel->getUserSubscription($userId);

            if (!$subscription) {
                Response::success(['subscription' => null]);
                exit;
            }

            Response::success(['subscription' => $subscription]);

        } catch (\Exception $e) {
            Response::error('Failed to get subscription: ' . $e->getMessage(), 400);
        }
        break;

    case 'purchases':
        if ($method !== 'GET') {
            Response::error('Method not allowed', 405);
            exit;
        }

        try {
            $purchaseModel = new Purchase();
            $purchases = $purchaseModel->getUserPurchases($userId);

            Response::success(['purchases' => $purchases]);

        } catch (\Exception $e) {
            Response::error('Failed to get purchases: ' . $e->getMessage(), 400);
        }
        break;

    default:
        Response::error('Payment endpoint not found', 404);
}
