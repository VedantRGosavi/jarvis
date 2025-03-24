<?php
require_once BASE_PATH . '/app/utils/Response.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - FridayAI Gaming Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gaming-primary text-gaming-light min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4 p-8 bg-gaming-secondary rounded-lg shadow-xl">
        <div class="text-center">
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-4">Payment Successful!</h2>
            <p class="text-gaming-gray-200 mb-8">Thank you for your purchase. Your game content is now available.</p>

            <div class="space-y-4">
                <a href="/dashboard"
                   class="block w-full px-4 py-2 bg-gaming-accent hover:bg-gaming-gray-600 rounded transition duration-150">
                    Go to Dashboard
                </a>
                <a href="/games"
                   class="block w-full px-4 py-2 border border-gaming-accent hover:bg-gaming-accent rounded transition duration-150">
                    Browse More Games
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
