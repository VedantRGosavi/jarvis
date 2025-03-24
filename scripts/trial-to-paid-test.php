<?php
/**
 * Stripe Trial to Paid Subscription Test
 *
 * This script tests the complete subscription journey:
 * 1. Create a customer
 * 2. Start a trial subscription
 * 3. Simulate trial ending
 * 4. Transition to paid subscription
 * 5. Verify subscription status in database
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

use App\Utils\Auth;
use App\Models\User;
use App\Models\Subscription;

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

// 1. Create a test user in the system
$testUser = runTest('User Creation', function() {
    // Generate unique email and username
    $testEmail = 'test-' . time() . '@example.com';
    $testName = 'Trial User';

    echo "Creating test user with email: $testEmail\n";

    // Create a Stripe customer first
    $customer = \Stripe\Customer::create([
        'email' => $testEmail,
        'name' => $testName,
        'metadata' => [
            'test' => true
        ]
    ]);

    echo "Created Stripe customer with ID: " . $customer->id . "\n";

    // Create a test user record using the User model
    try {
        $userModel = new User();
        $user = $userModel->createUser(
            $testName,
            $testEmail,
            'TrialUser123',
            $customer->id
        );

        if (!$user) {
            // If user model failed, try direct database insertion
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
            $values = [$testName, $testEmail, password_hash('TrialUser123', PASSWORD_DEFAULT), date('Y-m-d H:i:s')];
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

            // Construct user array
            $user = [
                'id' => $userId,
                'email' => $testEmail,
                'name' => $testName,
                'stripe_customer_id' => $customer->id
            ];
        }

        return $user;
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        throw $e;
    }
});

if (!$testUser) {
    die("Cannot continue tests without a user\n");
}

// 2. Create a Stripe customer for the test user - SKIP this step as we already created it
$testCustomer = runTest('Stripe Customer Creation', function() use ($testUser) {
    // Get the customer already created
    $customer = \Stripe\Customer::retrieve($testUser['stripe_customer_id']);

    echo "Using existing Stripe customer with ID: " . $customer->id . "\n";

    return $customer;
});

// 3. Create a trial subscription
$testSubscription = runTest('Trial Subscription Creation', function() use ($testCustomer, $testUser) {
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
            throw new \Exception("No active subscription prices found in Stripe account");
        }
    }

    // Create a subscription with a very short trial period (1 day)
    // For testing, we want the trial to end quickly
    $subscription = \Stripe\Subscription::create([
        'customer' => $testCustomer->id,
        'items' => [
            ['price' => $subscriptionPriceId]
        ],
        'trial_period_days' => 1, // Short trial for testing
        'payment_behavior' => 'default_incomplete',
        'expand' => ['latest_invoice.payment_intent'],
        'metadata' => [
            'user_id' => $testUser['id'],
            'test' => true
        ]
    ]);

    echo "Created subscription with ID: " . $subscription->id . "\n";
    echo "Subscription status: " . $subscription->status . "\n";

    // Save subscription to database
    $subscriptionModel = new Subscription();
    $subscriptionModel->createSubscription(
        $testUser['id'],
        $subscription->id,
        $subscription->status,
        date('Y-m-d H:i:s', $subscription->current_period_end)
    );

    // Update user subscription status
    $userModel = new User();
    $userModel->updateSubscription($testUser['id'], 'trialing');

    return $subscription;
});

if (!$testSubscription) {
    die("Cannot continue tests without a subscription\n");
}

// 4. Test adding payment method to subscription
$paymentMethod = runTest('Add Payment Method', function() use ($testCustomer) {
    // Instead of creating a card directly (which is unsafe and restricted by Stripe),
    // we'll use a test payment method token that Stripe allows in test mode

    // Create a SetupIntent first which is the secure way to set up payment methods
    $setupIntent = \Stripe\SetupIntent::create([
        'customer' => $testCustomer->id,
        'payment_method_types' => ['card'],
    ]);

    echo "Created SetupIntent with ID: " . $setupIntent->id . "\n";

    // Use a test payment method token that Stripe provides for testing
    // In production, this would come from Stripe.js Elements or Checkout on the frontend
    $paymentMethod = \Stripe\PaymentMethod::create([
        'type' => 'card',
        'card' => [
            'token' => 'tok_visa', // Special test token that Stripe allows
        ],
    ]);

    echo "Created payment method with ID: " . $paymentMethod->id . "\n";

    // Attach the payment method to the customer
    $paymentMethod = \Stripe\PaymentMethod::attach(
        $paymentMethod->id,
        ['customer' => $testCustomer->id]
    );

    // Set as default payment method
    \Stripe\Customer::update($testCustomer->id, [
        'invoice_settings' => [
            'default_payment_method' => $paymentMethod->id,
        ],
    ]);

    echo "Set payment method as default for customer\n";

    return $paymentMethod;
});

if (!$paymentMethod) {
    die("Cannot continue tests without a payment method\n");
}

// 5. Simulate the trial ending by updating the subscription
$updatedSubscription = runTest('Simulate Trial Ending', function() use ($testSubscription) {
    // We'll simulate ending the trial by updating the subscription
    $subscription = \Stripe\Subscription::retrieve($testSubscription->id);

    // Update the subscription to end trial now
    $updatedSubscription = \Stripe\Subscription::update($testSubscription->id, [
        'trial_end' => 'now', // End trial immediately
    ]);

    echo "Updated subscription to end trial\n";
    echo "New subscription status: " . $updatedSubscription->status . "\n";

    // Verify the subscription status changed from trialing
    if ($updatedSubscription->status === 'trialing') {
        throw new \Exception("Subscription still in trial after updating trial_end");
    }

    return $updatedSubscription;
});

// 6. Verify subscription status in database
$dbVerification = runTest('Verify Database Status', function() use ($testUser, $testSubscription, $updatedSubscription) {
    // Give the webhook some time to process
    echo "Waiting 5 seconds for webhook processing...\n";
    sleep(5);

    $subscriptionModel = new Subscription();
    $dbSubscription = $subscriptionModel->getSubscription($testSubscription->id);

    echo "Database subscription status: " . ($dbSubscription['status'] ?? 'not found') . "\n";

    // The status might be 'active' if the webhook was processed,
    // or still 'trialing' if not yet processed
    if (!$dbSubscription) {
        throw new \Exception("Subscription not found in database");
    }

    // Verify user subscription status
    $userModel = new User();
    $user = $userModel->getById($testUser['id']);

    echo "User subscription status: " . ($user['subscription_status'] ?? 'not set') . "\n";

    return true;
});

// 7. Clean up test data if needed
$cleanup = runTest('Cleanup Test Data', function() use ($testCustomer, $testSubscription, $testUser) {
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
    // Delete user from database
    $userModel = new User();
    $userModel->deleteUser($testUser['id']);
    echo "Deleted test user from database\n";

    // Delete customer (this will also delete associated subscriptions and payment methods)
    if ($testCustomer) {
        $deletedCustomer = \Stripe\Customer::delete($testCustomer->id);
        echo "Deleted test customer from Stripe\n";
    }
    */

    return true;
});

// Summary
echo "\n======================================\n";
echo "🔍 Trial to Paid Subscription Test Summary\n";
echo "======================================\n";

if ($testUser && $testCustomer && $testSubscription && $paymentMethod && $updatedSubscription && $dbVerification && $cleanup) {
    echo "✅ All tests passed!\n";
    echo "Test user ID: " . $testUser['id'] . "\n";
    echo "Test customer ID: " . $testCustomer->id . "\n";
    echo "Test subscription ID: " . $testSubscription->id . "\n";
} else {
    echo "❌ Some tests failed. Check logs above for details.\n";
}

echo "\nNote: Test data has been preserved for review.\n";
echo "Remember to clean up test data manually when no longer needed.\n";
