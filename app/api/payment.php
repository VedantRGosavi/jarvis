<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/vendor/autoload.php';

use App\Utils\Response;
use App\Utils\Auth;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

// Initialize Stripe with secret key from environment
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Validate authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token || !Auth::validateToken($token)) {
    Response::error('Unauthorized', 401);
    exit;
}

// Get authenticated user
$userId = Auth::getUserIdFromToken($token);
$userModel = new User();
$user = $userModel->getById($userId);

if (!$user) {
    Response::error('User not found', 404);
    exit;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $api_segments[1] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// Load app config for pricing
$config = require BASE_PATH . '/app/config/app.php';

// Route to appropriate handler
switch ($action) {
    case 'setup':
        if ($method === 'POST') {
            try {
                // Create or get Stripe customer
                $customer = Customer::create([
                    'email' => $user['email'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                // Create setup intent for future payments
                $setupIntent = \Stripe\SetupIntent::create([
                    'customer' => $customer->id,
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                Response::success([
                    'client_secret' => $setupIntent->client_secret,
                    'customer_id' => $customer->id
                ]);
            } catch (ApiErrorException $e) {
                Response::error('Failed to setup payment: ' . $e->getMessage(), 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'subscribe':
        if ($method === 'POST') {
            if (!isset($data['payment_method']) || !isset($data['game_id'])) {
                Response::error('Payment method and game ID required', 400);
                break;
            }

            // Validate game ID
            if (!isset($config['games'][$data['game_id']])) {
                Response::error('Invalid game ID', 400);
                break;
            }

            try {
                // Get or create customer
                $customer = null;
                try {
                    $customers = Customer::search([
                        'query' => "metadata['user_id']:'{$userId}'"
                    ]);
                    $customer = $customers->data[0] ?? null;
                } catch (ApiErrorException $e) {
                    // Ignore search errors
                }

                if (!$customer) {
                    $customer = Customer::create([
                        'email' => $user['email'],
                        'payment_method' => $data['payment_method'],
                        'invoice_settings' => [
                            'default_payment_method' => $data['payment_method']
                        ],
                        'metadata' => [
                            'user_id' => $userId
                        ]
                    ]);
                } else {
                    Customer::update($customer->id, [
                        'payment_method' => $data['payment_method'],
                        'invoice_settings' => [
                            'default_payment_method' => $data['payment_method']
                        ]
                    ]);
                }

                // Create subscription
                $subscription = Subscription::create([
                    'customer' => $customer->id,
                    'items' => [[
                        'price' => $config['games'][$data['game_id']]['price_id']
                    ]],
                    'payment_behavior' => 'default_incomplete',
                    'expand' => ['latest_invoice.payment_intent'],
                    'metadata' => [
                        'user_id' => $userId,
                        'game_id' => $data['game_id']
                    ]
                ]);

                // Update user subscription status
                $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
                $userModel->updateSubscription($userId, 'active', $endDate);

                Response::success([
                    'subscription_id' => $subscription->id,
                    'client_secret' => $subscription->latest_invoice->payment_intent->client_secret
                ]);
            } catch (ApiErrorException $e) {
                Response::error('Failed to create subscription: ' . $e->getMessage(), 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'cancel':
        if ($method === 'POST') {
            if (!isset($data['subscription_id'])) {
                Response::error('Subscription ID required', 400);
                break;
            }

            try {
                $subscription = Subscription::retrieve($data['subscription_id']);

                // Verify subscription belongs to user
                if ($subscription->metadata['user_id'] !== $userId) {
                    Response::error('Unauthorized', 401);
                    break;
                }

                // Cancel subscription at period end
                $subscription->cancel_at_period_end = true;
                $subscription->save();

                // Update user subscription status
                $userModel->updateSubscription($userId, 'cancelled');

                Response::success(['message' => 'Subscription cancelled']);
            } catch (ApiErrorException $e) {
                Response::error('Failed to cancel subscription: ' . $e->getMessage(), 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'webhook':
        if ($method === 'POST') {
            $payload = @file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $_ENV['STRIPE_WEBHOOK_SECRET']
                );

                // Handle webhook events
                switch ($event->type) {
                    case 'customer.subscription.deleted':
                        $subscription = $event->data->object;
                        $userId = $subscription->metadata['user_id'];
                        if ($userId) {
                            $userModel->updateSubscription($userId, 'expired');
                        }
                        break;

                    case 'invoice.payment_failed':
                        $invoice = $event->data->object;
                        $subscription = Subscription::retrieve($invoice->subscription);
                        $userId = $subscription->metadata['user_id'];
                        if ($userId) {
                            $userModel->updateSubscription($userId, 'payment_failed');
                        }
                        break;
                }

                Response::success(['message' => 'Webhook processed']);
            } catch (\UnexpectedValueException $e) {
                Response::error('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Response::error('Invalid signature', 400);
            } catch (ApiErrorException $e) {
                Response::error('Webhook error: ' . $e->getMessage(), 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    default:
        Response::error('Payment endpoint not found', 404);
}
