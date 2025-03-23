<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware([
    'auth:api',
    config('jetstream.auth_middleware'),
    'verified'
])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::post('/webhook', function (Request $request) {
    if ($request->method() !== 'POST') {
        return response()->json(['error' => 'Method not allowed'], 405);
    }
    
    $payload = @file_get_contents('php://input');
    $sigHeader = $request->header('HTTP_STRIPE_SIGNATURE') ?? '';
    
    try {
        $event = \Stripe\Webhook::constructEvent(
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
                    $session->metadata->user_id,
                    $session->metadata->product_id,
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
        
        return response()->json(['status' => 'Webhook processed']);
    } catch(\UnexpectedValueException $e) {
        return response()->json(['error' => 'Invalid payload'], 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        return response()->json(['error' => 'Invalid signature'], 400);
    } catch(\Exception $e) {
        return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 500);
    }
});

Route::middleware([
    'auth:api',
    config('jetstream.auth_middleware'),
    'verified'
])->group(function () {
    Route::apiResource('/payments', PaymentController::class);
}); 