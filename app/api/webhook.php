<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/models/Purchase.php';
require_once BASE_PATH . '/vendor/autoload.php';

use App\Utils\Response;
use App\Models\Purchase;
use Stripe\Stripe;
use Stripe\Webhook;

// Initialize Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Get the payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent(
        $payload,
        $sigHeader,
        $_ENV['STRIPE_WEBHOOK_SECRET']
    );
    
    // Handle webhook events
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            
            // Handle successful payment
            $purchaseModel = new Purchase();
            $purchaseModel->createPurchase(
                $session->metadata->user_id ?? null,
                $session->metadata->product_id ?? null,
                $session->payment_intent,
                'completed',
                $session->amount_total
            );
            break;
            
        case 'checkout.session.expired':
            // Handle expired checkout session
            break;
            
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            $purchaseModel = new Purchase();
            $purchaseModel->updatePurchaseStatus($paymentIntent->id, 'completed');
            break;
            
        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            $purchaseModel = new Purchase();
            $purchaseModel->updatePurchaseStatus($paymentIntent->id, 'failed');
            break;
    }
    
    Response::success(['status' => 'Webhook processed']);
} catch(\UnexpectedValueException $e) {
    Response::error('Invalid payload', 400);
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    Response::error('Invalid signature', 400);
} catch(\Exception $e) {
    Response::error('Webhook error: ' . $e->getMessage(), 500);
} 