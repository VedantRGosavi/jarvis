<?php

namespace App\Utils;

use App\Models\User;
use App\Models\Purchase;
use App\Models\Subscription;
use Stripe\Event;
use Exception;

class WebhookHandler {
    private $event;
    private $userModel;
    private $purchaseModel;
    private $subscriptionModel;

    public function __construct(Event $event) {
        $this->event = $event;
        $this->userModel = new User();
        $this->purchaseModel = new Purchase();
        $this->subscriptionModel = new Subscription();
    }

    public function handle(): array {
        $method = str_replace('.', '_', $this->event->type);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return ['status' => 'Unhandled event type: ' . $this->event->type];
    }

    // Account Events
    protected function account_external_account_created(): array {
        // Log the event
        error_log("External account created: " . json_encode($this->event->data->object));
        return ['status' => 'success'];
    }

    protected function account_external_account_deleted(): array {
        error_log("External account deleted: " . json_encode($this->event->data->object));
        return ['status' => 'success'];
    }

    // Checkout Events
    protected function checkout_session_completed(): array {
        $session = $this->event->data->object;

        $this->purchaseModel->createPurchase(
            $session->metadata->user_id ?? null,
            $session->metadata->product_id ?? null,
            $session->payment_intent,
            'completed',
            $session->amount_total
        );

        return ['status' => 'success'];
    }

    protected function checkout_session_async_payment_failed(): array {
        $session = $this->event->data->object;

        $this->purchaseModel->updatePurchaseStatus(
            $session->payment_intent,
            'failed'
        );

        return ['status' => 'success'];
    }

    protected function checkout_session_async_payment_succeeded(): array {
        $session = $this->event->data->object;

        $this->purchaseModel->updatePurchaseStatus(
            $session->payment_intent,
            'completed'
        );

        return ['status' => 'success'];
    }

    protected function checkout_session_expired(): array {
        $session = $this->event->data->object;

        $this->purchaseModel->updatePurchaseStatus(
            $session->payment_intent,
            'expired'
        );

        return ['status' => 'success'];
    }

    // Customer Events
    protected function customer_created(): array {
        $customer = $this->event->data->object;

        if (isset($customer->metadata->user_id)) {
            $this->userModel->updateStripeCustomerId(
                $customer->metadata->user_id,
                $customer->id
            );
        }

        return ['status' => 'success'];
    }

    protected function customer_deleted(): array {
        $customer = $this->event->data->object;

        $this->userModel->removeStripeCustomerId($customer->id);
        return ['status' => 'success'];
    }

    protected function customer_updated(): array {
        $customer = $this->event->data->object;

        if (isset($customer->metadata->user_id)) {
            $this->userModel->updateCustomerDetails(
                $customer->metadata->user_id,
                $customer->email,
                $customer->name
            );
        }

        return ['status' => 'success'];
    }

    // Subscription Events
    protected function customer_subscription_created(): array {
        $subscription = $this->event->data->object;

        if (isset($subscription->metadata->user_id)) {
            $this->subscriptionModel->createSubscription(
                $subscription->metadata->user_id,
                $subscription->id,
                $subscription->status,
                date('Y-m-d H:i:s', $subscription->current_period_end)
            );
        }

        return ['status' => 'success'];
    }

    protected function customer_subscription_updated(): array {
        $subscription = $this->event->data->object;

        $this->subscriptionModel->updateSubscriptionStatus(
            $subscription->id,
            $subscription->status
        );

        return ['status' => 'success'];
    }

    protected function customer_subscription_deleted(): array {
        $subscription = $this->event->data->object;

        $userId = $this->subscriptionModel->getUserIdFromSubscription($subscription->id);
        if ($userId) {
            $this->userModel->updateSubscription($userId, 'cancelled');
            $this->subscriptionModel->updateSubscriptionStatus($subscription->id, 'cancelled');
        }

        return ['status' => 'success'];
    }

    protected function customer_subscription_trial_will_end(): array {
        $subscription = $this->event->data->object;

        // Notify user about trial ending
        if (isset($subscription->metadata->user_id)) {
            // TODO: Implement notification system
            error_log("Trial ending for user: " . $subscription->metadata->user_id);
        }

        return ['status' => 'success'];
    }

    // Invoice Events
    protected function invoice_paid(): array {
        $invoice = $this->event->data->object;

        if ($invoice->subscription) {
            $this->subscriptionModel->updateSubscriptionStatus($invoice->subscription, 'active');

            $userId = $this->subscriptionModel->getUserIdFromSubscription($invoice->subscription);
            if ($userId) {
                $this->userModel->updateSubscription($userId, 'active');
            }
        }

        return ['status' => 'success'];
    }

    protected function invoice_payment_failed(): array {
        $invoice = $this->event->data->object;

        if ($invoice->subscription) {
            $userId = $this->subscriptionModel->getUserIdFromSubscription($invoice->subscription);
            if ($userId) {
                $this->userModel->updateSubscription($userId, 'payment_failed');
                // TODO: Notify user about payment failure
            }
        }

        return ['status' => 'success'];
    }

    protected function invoice_upcoming(): array {
        $invoice = $this->event->data->object;

        if (isset($invoice->customer)) {
            // TODO: Notify customer about upcoming invoice
            error_log("Upcoming invoice for customer: " . $invoice->customer);
        }

        return ['status' => 'success'];
    }

    // Payment Intent Events
    protected function payment_intent_succeeded(): array {
        $paymentIntent = $this->event->data->object;

        $this->purchaseModel->updatePurchaseStatus(
            $paymentIntent->id,
            'completed'
        );

        return ['status' => 'success'];
    }

    protected function payment_intent_payment_failed(): array {
        $paymentIntent = $this->event->data->object;

        $this->purchaseModel->updatePurchaseStatus(
            $paymentIntent->id,
            'failed'
        );

        return ['status' => 'success'];
    }

    // Product Events
    protected function product_created(): array {
        $product = $this->event->data->object;
        error_log("New product created: " . $product->id);
        return ['status' => 'success'];
    }

    protected function product_updated(): array {
        $product = $this->event->data->object;
        error_log("Product updated: " . $product->id);
        return ['status' => 'success'];
    }

    protected function product_deleted(): array {
        $product = $this->event->data->object;
        error_log("Product deleted: " . $product->id);
        return ['status' => 'success'];
    }

    // Price Events
    protected function price_created(): array {
        $price = $this->event->data->object;
        error_log("New price created: " . $price->id);
        return ['status' => 'success'];
    }

    protected function price_updated(): array {
        $price = $this->event->data->object;
        error_log("Price updated: " . $price->id);
        return ['status' => 'success'];
    }

    protected function price_deleted(): array {
        $price = $this->event->data->object;
        error_log("Price deleted: " . $price->id);
        return ['status' => 'success'];
    }
}
