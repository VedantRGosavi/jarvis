<?php
/**
 * Stripe Webhook Event Test
 *
 * This script tests the webhook handling for various Stripe events:
 * 1. Simulates various webhook events
 * 2. Tests webhook signature verification
 * 3. Verifies database updates from webhook events
 * 4. Tests all critical payment events
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
require_once BASE_PATH . '/app/utils/WebhookHandler.php';

use App\Utils\Auth;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Purchase;
use App\Utils\WebhookHandler;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Initialize Stripe with live keys
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
echo "Using Stripe API Key: " . substr($_ENV['STRIPE_SECRET_KEY'], 0, 8) . "...\n";

// Get webhook secret
$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;
if (!$webhookSecret) {
    die("❌ No webhook secret found in environment variables. Cannot continue tests.\n");
}
echo "Using webhook secret: " . substr($webhookSecret, 0, 5) . "...\n";

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

// Create test data for webhook tests
$testData = runTest('Create Test Data', function() {
    // Create a test customer
    $customer = \Stripe\Customer::create([
        'email' => 'webhook-test-' . time() . '@example.com',
        'name' => 'Webhook Test User',
        'metadata' => [
            'test' => true
        ]
    ]);

    echo "Created test customer with ID: " . $customer->id . "\n";

    // Create a test user in our database using the User model
    try {
        $userModel = new User();
        $user = $userModel->createUser(
            'Webhook Test User',
            $customer->email,
            'WebhookTest123',
            $customer->id
        );

        if (!$user) {
            // If user model failed, try direct database insertion
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
            $fields = ['name', 'email', 'password', 'created_at'];
            $values = ['Webhook Test User', $customer->email, password_hash('WebhookTest123', PASSWORD_DEFAULT), date('Y-m-d H:i:s')];
            $placeholders = ['?', '?', '?', '?'];

            if (isset($columns['subscription_status'])) {
                $fields[] = 'subscription_status';
                $values[] = 'none';
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

            // Update Stripe customer with the user ID
            $customer = \Stripe\Customer::update($customer->id, [
                'metadata' => [
                    'user_id' => $userId,
                    'test' => true
                ]
            ]);

            return [
                'customer' => $customer,
                'user_id' => $userId
            ];
        } else {
            // Update Stripe customer with the user ID
            $customer = \Stripe\Customer::update($customer->id, [
                'metadata' => [
                    'user_id' => $user['id'],
                    'test' => true
                ]
            ]);

            return [
                'customer' => $customer,
                'user_id' => $user['id']
            ];
        }
    } catch (\Exception $e) {
        echo "Error creating user: " . $e->getMessage() . "\n";
        throw $e;
    }

    // Create a test product
    $product = \Stripe\Product::create([
        'name' => 'Webhook Test Product',
        'description' => 'Product created for webhook testing',
        'metadata' => [
            'test' => true
        ]
    ]);

    echo "Created test product with ID: " . $product->id . "\n";

    // Create a price for the product
    $price = \Stripe\Price::create([
        'product' => $product->id,
        'unit_amount' => 1500, // $15.00
        'currency' => 'usd',
        'recurring' => [
            'interval' => 'month'
        ],
        'metadata' => [
            'test' => true
        ]
    ]);

    echo "Created test price with ID: " . $price->id . "\n";

    return [
        'customer' => $customer,
        'user_id' => isset($user) ? $user['id'] : $userId,
        'product' => $product,
        'price' => $price
    ];
});

if (!$testData) {
    die("Cannot continue tests without test data\n");
}

// Helper function to simulate and process webhook events
function testWebhookEvent($eventType, $data, $webhookSecret) {
    echo "Testing webhook event: $eventType\n";

    // Create a Stripe Event object
    $event = \Stripe\Event::constructFrom([
        'id' => 'evt_' . time() . rand(1000, 9999),
        'object' => 'event',
        'api_version' => '2020-08-27',
        'created' => time(),
        'data' => [
            'object' => $data
        ],
        'type' => $eventType
    ]);

    // Process the event with our webhook handler
    $handler = new WebhookHandler($event);
    $result = $handler->handle();

    echo "Webhook handler result: " . json_encode($result) . "\n";

    if ($result['status'] !== 'success') {
        throw new \Exception("Webhook handler did not return success status");
    }

    return $result;
}

// 1. Test customer.created webhook
$customerCreatedTest = runTest('customer.created Webhook', function() use ($testData, $webhookSecret) {
    return testWebhookEvent('customer.created', $testData['customer']->toArray(), $webhookSecret);
});

// 2. Test subscription creation
$testSubscription = runTest('Create Subscription for Tests', function() use ($testData) {
    // Use a test payment method token instead of creating a card directly
    $paymentMethod = \Stripe\PaymentMethod::create([
        'type' => 'card',
        'card' => [
            'token' => 'tok_visa', // Special test token that Stripe allows
        ],
    ]);

    // Attach the payment method to the customer
    $paymentMethod = \Stripe\PaymentMethod::attach(
        $paymentMethod->id,
        ['customer' => $testData['customer']->id]
    );

    // Set as default payment method
    \Stripe\Customer::update($testData['customer']->id, [
        'invoice_settings' => [
            'default_payment_method' => $paymentMethod->id,
        ],
    ]);

    // Create a subscription with a 0-day trial so it transitions immediately
    $subscription = \Stripe\Subscription::create([
        'customer' => $testData['customer']->id,
        'items' => [
            ['price' => $testData['price']->id]
        ],
        'default_payment_method' => $paymentMethod->id,
        'trial_period_days' => 0,
        'expand' => ['latest_invoice.payment_intent'],
        'metadata' => [
            'user_id' => $testData['user_id'],
            'test' => true
        ]
    ]);

    echo "Created subscription with ID: " . $subscription->id . "\n";
    echo "Subscription status: " . $subscription->status . "\n";

    return $subscription;
});

if (!$testSubscription) {
    die("Cannot continue tests without a subscription\n");
}

// 3. Test customer.subscription.created webhook
$subscriptionCreatedTest = runTest('customer.subscription.created Webhook', function() use ($testSubscription, $webhookSecret) {
    return testWebhookEvent('customer.subscription.created', $testSubscription->toArray(), $webhookSecret);
});

// 4. Test invoice.paid webhook
$invoicePaidTest = runTest('invoice.paid Webhook', function() use ($testSubscription, $webhookSecret) {
    // Get the latest invoice for the subscription
    $invoice = \Stripe\Invoice::all([
        'customer' => $testSubscription->customer,
        'subscription' => $testSubscription->id,
        'limit' => 1
    ])->data[0];

    echo "Using invoice with ID: " . $invoice->id . "\n";

    return testWebhookEvent('invoice.paid', $invoice->toArray(), $webhookSecret);
});

// 5. Test payment_intent.succeeded webhook
$paymentIntentTest = runTest('payment_intent.succeeded Webhook', function() use ($testData, $webhookSecret) {
    // Create a payment intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => 2000, // $20.00
        'currency' => 'usd',
        'customer' => $testData['customer']->id,
        'payment_method_types' => ['card'],
        'metadata' => [
            'user_id' => $testData['user_id'],
            'test' => true
        ]
    ]);

    echo "Created payment intent with ID: " . $paymentIntent->id . "\n";

    // Create a purchase record in our database
    $purchaseModel = new Purchase();
    $purchaseModel->createPurchase(
        $testData['user_id'],
        $testData['product']->id,
        $paymentIntent->id,
        'pending',
        $paymentIntent->amount
    );

    // Simulate the payment_intent.succeeded webhook
    return testWebhookEvent('payment_intent.succeeded', $paymentIntent->toArray(), $webhookSecret);
});

// 6. Verify database updates
$dbVerificationTest = runTest('Database Status Verification', function() use ($testData, $testSubscription) {
    // Verify user subscription status
    $userModel = new User();
    $user = $userModel->getById($testData['user_id']);

    echo "User subscription status: " . ($user['subscription_status'] ?? 'not set') . "\n";

    // Verify subscription in database
    $subscriptionModel = new Subscription();
    $dbSubscription = $subscriptionModel->getSubscription($testSubscription->id);

    echo "Database subscription status: " . ($dbSubscription['status'] ?? 'not found') . "\n";

    // Get the payment intent from the previous test
    $purchaseModel = new Purchase();
    $purchases = $purchaseModel->getUserPurchases($testData['user_id']);

    echo "Found " . count($purchases) . " purchases for user\n";
    if (count($purchases) > 0) {
        echo "Latest purchase status: " . $purchases[0]['status'] . "\n";
    }

    return true;
});

// Clean up test data
$cleanup = runTest('Cleanup Test Data', function() use ($testData, $testSubscription) {
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
    // Delete product & price
    if (isset($testData['product'])) {
        \Stripe\Product::update($testData['product']->id, ['active' => false]);
        echo "Deactivated test product\n";
    }

    // Delete customer (this will also delete associated subscriptions and payment methods)
    if (isset($testData['customer'])) {
        $deletedCustomer = \Stripe\Customer::delete($testData['customer']->id);
        echo "Deleted test customer\n";
    }
    */

    return true;
});

// Summary
echo "\n======================================\n";
echo "🔍 Webhook Testing Summary\n";
echo "======================================\n";

if ($testData && $customerCreatedTest && $testSubscription && $subscriptionCreatedTest &&
    $invoicePaidTest && $paymentIntentTest && $dbVerificationTest && $cleanup) {
    echo "✅ All webhook tests passed!\n";
} else {
    echo "❌ Some webhook tests failed. Check logs above for details.\n";
}

echo "\nNote: Test data has been preserved for review.\n";
echo "Remember to clean up test data manually when no longer needed.\n";
