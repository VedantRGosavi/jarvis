<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/WebhookHandler.php';
require_once BASE_PATH . '/vendor/autoload.php';

use App\Utils\Response;
use App\Utils\WebhookHandler;
use Stripe\Stripe;
use Stripe\Webhook;

// Initialize Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Get the payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Debug logging
error_log("Webhook received - Payload: " . $payload);
error_log("Signature header: " . $sigHeader);
error_log("Webhook secret: " . substr($_ENV['STRIPE_WEBHOOK_SECRET'], 0, 10) . '...');

try {
    if (empty($payload)) {
        throw new \Exception('Empty payload received');
    }

    if (empty($sigHeader)) {
        throw new \Exception('No signature header received');
    }

    if (empty($_ENV['STRIPE_WEBHOOK_SECRET'])) {
        throw new \Exception('Webhook secret not configured');
    }

    $event = Webhook::constructEvent(
        $payload,
        $sigHeader,
        $_ENV['STRIPE_WEBHOOK_SECRET']
    );
    
    error_log("Event constructed successfully: " . $event->type);
    
    // Handle the event using WebhookHandler
    $handler = new WebhookHandler($event);
    $result = $handler->handle();
    
    error_log("Event handled successfully: " . json_encode($result));
    Response::success($result);
} catch(\UnexpectedValueException $e) {
    error_log("Invalid payload error: " . $e->getMessage());
    Response::error('Invalid payload: ' . $e->getMessage(), 400);
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    error_log("Signature verification failed: " . $e->getMessage());
    Response::error('Invalid signature: ' . $e->getMessage(), 400);
} catch(\Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    Response::error('Webhook error: ' . $e->getMessage(), 500);
} 