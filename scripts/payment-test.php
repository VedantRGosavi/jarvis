<?php
/**
 * Stripe Integration Test Script
 *
 * This script tests the complete payment flow including:
 * 1. Customer creation
 * 2. Subscription with trial period
 * 3. One-time purchase
 * 4. Webhook event handling
 *
 * Note: This test uses LIVE keys - be cautious with real transactions!
 */

// Define the base path for includes
define('BASE_PATH', dirname(__DIR__));

// Load dependencies
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/Purchase.php';

use App\Utils\Auth;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Purchase;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Initialize Stripe with live keys
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
echo "Using Stripe API Key: " . substr($_ENV['STRIPE_SECRET_KEY'], 0, 8) . "...\n";

// Helper function to run test cases
function runTest($name, $testFn) {
    echo "\n======================================\n";
    echo "🧪 Testing: $name\n";
    echo "======================================\n";

    try {
        $result = $testFn();
        echo "✅ Test passed: $name\n";
        return $result;
    } catch (\Exception $e) {
        echo "❌ Test failed: $name\n";
        echo "Error: " . $e->getMessage() . "\n";
        return null;
    }
}

// Create test customer
$customer = \Stripe\Customer::create([
    'email' => 'test-' . time() . '@example.com',
    'name' => 'Test User',
    'description' => 'Test customer for payment integration testing',
    'metadata' => [
        'test' => true
    ]
]);

echo "Created test customer: " . $customer->id . "\n";

// Create a test user in our database
$testUsername = 'testuser' . time();

try {
    // Get database connection
    require_once BASE_PATH . '/app/utils/Database.php';
    $db = \App\Utils\Database::getSystemInstance();

    // Check if the table has the expected structure
    $tableCheckStmt = $db->query("PRAGMA table_info(users)");
    $columns = [];
    while ($row = $tableCheckStmt->fetchArray(SQLITE3_ASSOC)) {
        $columns[$row['name']] = true;
    }

    // Build appropriate SQL based on available columns
    $fields = ['username', 'email', 'password', 'name'];
    $values = [$testUsername, $customer->email, password_hash('TestUser123', PASSWORD_DEFAULT), 'Test User'];
    $placeholders = ['?', '?', '?', '?'];

    if (isset($columns['created_at'])) {
        $fields[] = 'created_at';
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
    }

    if (isset($columns['status'])) {
        $fields[] = 'status';
        $values[] = 'active';
        $placeholders[] = '?';
    }

    if (isset($columns['stripe_customer_id'])) {
        $fields[] = 'stripe_customer_id';
        $values[] = $customer->id;
        $placeholders[] = '?';
    }

    $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    echo "Executing SQL: $sql\n";

    $stmt = $db->prepare($sql);
    for ($i = 0; $i < count($values); $i++) {
        $stmt->bindValue($i + 1, $values[$i]);
    }

    $result = $stmt->execute();
    if (!$result) {
        throw new \Exception("Database insertion failed");
    }

    // Get the inserted user ID
    $userId = $db->lastInsertRowID();

    if (!$userId) {
        throw new \Exception("Failed to get user ID after insertion");
    }

    echo "Created user with ID: $userId\n";
} catch (\Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    echo "Continuing test without user in database...\n";
    $userId = null;
}

// Update the customer with the user ID if we have one
if ($userId) {
    $customer = \Stripe\Customer::update($customer->id, [
        'metadata' => [
            'user_id' => $userId,
            'test' => true
        ]
    ]);
}

// 2. Test subscription with trial
$testSubscription = runTest('Subscription with Trial', function() use ($customer) {
    // Get the subscription price ID from the environment
    $subscriptionPriceId = $_ENV['STRIPE_SUBSCRIPTION_PRICE_ID'] ?? null;

    if (!$subscriptionPriceId) {
        echo "⚠️ No subscription price ID found in environment, using default from Stripe account...\n";

        // Get the first active subscription price
        $prices = \Stripe\Price::all(['limit' => 1, 'active' => true, 'type' => 'recurring']);
        if (count($prices->data) > 0) {
            $subscriptionPriceId = $prices->data[0]->id;
            echo "Using price ID: $subscriptionPriceId\n";
        } else {
            // No prices found, create a test product and price
            echo "No active subscription prices found. Creating test product and price...\n";

            $product = \Stripe\Product::create([
                'name' => 'Test Subscription',
                'description' => 'Created during payment system testing',
                'metadata' => [
                    'test' => true
                ]
            ]);

            echo "Created test product with ID: " . $product->id . "\n";

            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => 1999, // $19.99
                'currency' => 'usd',
                'recurring' => [
                    'interval' => 'month'
                ],
                'metadata' => [
                    'test' => true
                ]
            ]);

            $subscriptionPriceId = $price->id;
            echo "Created and using price ID: $subscriptionPriceId\n";
        }
    }

    // Create a subscription with a 7-day trial
    $subscription = \Stripe\Subscription::create([
        'customer' => $customer->id,
        'items' => [
            ['price' => $subscriptionPriceId]
        ],
        'trial_period_days' => 7,
        'payment_behavior' => 'default_incomplete',
        'expand' => ['latest_invoice.payment_intent'],
        'metadata' => [
            'test' => true
        ]
    ]);

    echo "Created subscription with ID: " . $subscription->id . "\n";
    echo "Subscription status: " . $subscription->status . "\n";

    // Verify subscription status and trial period
    if ($subscription->status !== 'trialing') {
        throw new \Exception("Subscription should be in trial status");
    }

    return $subscription;
});

// 3. Test one-time purchase
$testPaymentIntent = runTest('One-time Purchase', function() use ($customer) {
    // Create a payment intent for a one-time purchase
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => 2500, // $25.00
        'currency' => 'usd',
        'customer' => $customer->id,
        'payment_method_types' => ['card'],
        'receipt_email' => $customer->email,
        'metadata' => [
            'test' => true,
            'product_id' => 'game_123',
            'product_name' => 'Test Game Purchase'
        ]
    ]);

    echo "Created payment intent with ID: " . $paymentIntent->id . "\n";
    echo "Payment intent status: " . $paymentIntent->status . "\n";

    // In a real system, this would be where the frontend collects payment details
    // and confirms the payment. For testing, we'll just verify the intent was created.

    return $paymentIntent;
});

// 4. Test webhook handling
$webhookTest = runTest('Webhook Event Processing', function() use ($customer, $testSubscription) {
    // Get the webhook secret from the environment
    $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

    if (!$webhookSecret) {
        throw new \Exception("No webhook secret found in environment variables");
    }

    echo "Using webhook secret: " . substr($webhookSecret, 0, 5) . "...\n";

    // Create a mock webhook for customer.subscription.created
    $event = [
        'id' => 'evt_' . time() . rand(1000, 9999),
        'object' => 'event',
        'api_version' => '2020-08-27',
        'created' => time(),
        'data' => [
            'object' => [
                'id' => $testSubscription->id,
                'object' => 'subscription',
                'customer' => $customer->id,
                'status' => 'trialing',
                'metadata' => [
                    'test' => true
                ]
            ]
        ],
        'type' => 'customer.subscription.created'
    ];

    // Load the WebhookHandler class
    require_once BASE_PATH . '/app/utils/WebhookHandler.php';

    // In a real test, we would POST this event to our webhook endpoint
    // For this test, we'll directly instantiate the WebhookHandler

    // We need to convert our array to a Stripe Event object
    $eventObj = \Stripe\Event::constructFrom($event);

    // Process with the webhook handler
    $handler = new \App\Utils\WebhookHandler($eventObj);
    $result = $handler->handle();

    echo "Webhook handler result: " . json_encode($result) . "\n";

    if ($result['status'] !== 'success') {
        throw new \Exception("Webhook handler did not return success status");
    }

    return true;
});

// Clean up test data if this is just a test
$cleanup = runTest('Cleanup Test Data', function() use ($customer, $testSubscription) {
    // Cancel subscription
    if ($testSubscription) {
        $canceledSubscription = \Stripe\Subscription::update($testSubscription->id, [
            'cancel_at_period_end' => true
        ]);
        echo "Marked subscription for cancellation at period end\n";
    }

    // In a real production test, you might want to comment out this part
    // to keep the test data for investigation

    /*
    // Delete customer (this will also delete associated subscriptions and payment methods)
    if ($customer) {
        $deletedCustomer = \Stripe\Customer::delete($customer->id);
        echo "Deleted test customer\n";
    }
    */

    return true;
});

// Summary
echo "\n======================================\n";
echo "🔍 Stripe Integration Test Summary\n";
echo "======================================\n";

if ($customer && $testSubscription && $testPaymentIntent && $webhookTest && $cleanup) {
    echo "✅ All tests passed!\n";
    echo "Test customer ID: " . $customer->id . "\n";
    echo "Test subscription ID: " . $testSubscription->id . "\n";
    echo "Test payment intent ID: " . $testPaymentIntent->id . "\n";
} else {
    echo "❌ Some tests failed. Check logs above for details.\n";
}

echo "\nNote: Test data has been preserved in Stripe for review.\n";
echo "Remember to clean up test data manually when no longer needed.\n";
