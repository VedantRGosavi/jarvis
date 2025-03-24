<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/SchemaGenerator.php';

use App\Utils\SchemaGenerator;

// Set SEO meta variables for this page
$pageTitle = 'Payment Cancelled';
$pageDescription = 'Your payment process has been cancelled. No charges were made to your account.';
$pageKeywords = 'payment cancelled, transaction cancelled, FridayAI, gaming assistant, payment';
$pageImage = '/images/payment-cancel.png';

// Add breadcrumb schema
$breadcrumbs = [
    ['name' => 'Home', 'url' => 'https://fridayai.com/'],
    ['name' => 'Games', 'url' => 'https://fridayai.com/games'],
    ['name' => 'Payment', 'url' => 'https://fridayai.com/payment'],
    ['name' => 'Payment Cancelled', 'url' => 'https://fridayai.com/payment/cancel']
];
$breadcrumbSchema = SchemaGenerator::generateBreadcrumbSchema($breadcrumbs);

// Combine schemas
$schemaMarkup = $breadcrumbSchema;

// Start output buffering to capture the content
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - FridayAI Gaming Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gaming-primary text-gaming-light min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4 p-8 bg-gaming-secondary rounded-lg shadow-xl">
        <div class="text-center">
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-4">Payment Cancelled</h2>
            <p class="text-gaming-gray-200 mb-8">Your payment was cancelled. No charges were made.</p>

            <div class="space-y-4">
                <a href="/games"
                   class="block w-full px-4 py-2 bg-gaming-accent hover:bg-gaming-gray-600 rounded transition duration-150">
                    Return to Games
                </a>
                <a href="/support"
                   class="block w-full px-4 py-2 border border-gaming-accent hover:bg-gaming-accent rounded transition duration-150">
                    Need Help?
                </a>
            </div>
        </div>
    </div>

    <script>
        // Clear any payment-related session data
        sessionStorage.removeItem('payment_intent');
        sessionStorage.removeItem('payment_method');
    </script>
</body>
</html>

<?php
// Store the buffered content
$content = ob_get_clean();

// Additional scripts specific to this page
$additionalScripts = <<<HTML
<script>
    // Clear any payment-related session data
    sessionStorage.removeItem('payment_intent');
    sessionStorage.removeItem('payment_method');
</script>
HTML;

// Additional head content specific to this page
$additionalHead = <<<HTML
<script src="https://cdn.tailwindcss.com"></script>
HTML;

// Include the layout template
require_once BASE_PATH . '/app/views/layout.php';
?>
