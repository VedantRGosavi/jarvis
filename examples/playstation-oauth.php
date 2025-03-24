<?php
/**
 * PlayStation OAuth NPSSO Token Example
 * 
 * This example shows how to authenticate with PlayStation Network using an NPSSO token.
 * 
 * Usage:
 * 1. Make sure you have a valid NPSSO token in your .env file as PLAYSTATION_NPSSO
 * 2. Run this file: php examples/playstation-oauth.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

use App\Utils\PlayStationOAuth;

// Create a new instance of the PlayStation OAuth provider
$playstation = new PlayStationOAuth();

// Check if PlayStation OAuth is configured
if (!$playstation->isConfigured()) {
    echo "PlayStation OAuth is not configured correctly.\n";
    echo "Please check your .env file and ensure PLAYSTATION_CLIENT_ID, PLAYSTATION_REDIRECT_URI, and PLAYSTATION_NPSSO are set.\n";
    exit(1);
}

// Method 1: Using step-by-step approach
echo "Method 1: Step-by-step authentication\n";
echo "-------------------------------------\n";

try {
    // Step 1: Exchange NPSSO for code
    echo "Exchanging NPSSO for authorization code...\n";
    $code = $playstation->exchangeNpssoForCode();
    if (!$code) {
        echo "Failed to get authorization code.\n";
    } else {
        echo "Authorization code received.\n";
        
        // Step 2: Exchange code for access token
        echo "Exchanging authorization code for access token...\n";
        $accessToken = $playstation->getAccessToken($code);
        if (!$accessToken) {
            echo "Failed to get access token.\n";
        } else {
            echo "Access token received.\n";
            
            // Get the full response including refresh token
            $tokenResponse = $playstation->getRawResponse();
            echo "Access Token: " . substr($tokenResponse['access_token'], 0, 10) . "...\n";
            echo "Refresh Token: " . (isset($tokenResponse['refresh_token']) ? substr($tokenResponse['refresh_token'], 0, 10) . "..." : "Not provided") . "\n";
            echo "Expires in: " . ($tokenResponse['expires_in'] ?? "Unknown") . " seconds\n";
            
            // Step 3: Get user information
            echo "\nFetching user information...\n";
            $userInfo = $playstation->getUserInfo();
            if (!$userInfo) {
                echo "Failed to get user information.\n";
            } else {
                echo "User information received:\n";
                echo "ID: " . $userInfo['id'] . "\n";
                echo "Name: " . $userInfo['name'] . "\n";
                echo "Online ID: " . ($userInfo['online_id'] ?? "Not available") . "\n";
                echo "Email: " . ($userInfo['email'] ?? "Not available") . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Method 2: Using the simplified authentication method
echo "Method 2: Simplified authentication\n";
echo "-----------------------------------\n";

try {
    echo "Authenticating with NPSSO token...\n";
    $authResult = $playstation->authenticateWithNpsso();
    
    if (!$authResult['success']) {
        echo "Authentication failed: " . ($authResult['error'] ?? "Unknown error") . "\n";
    } else {
        echo "Authentication successful!\n";
        
        echo "\nUser information:\n";
        echo "ID: " . $authResult['user']['id'] . "\n";
        echo "Name: " . $authResult['user']['name'] . "\n";
        echo "Email: " . $authResult['user']['email'] . "\n";
        echo "Subscription Status: " . $authResult['user']['subscription_status'] . "\n";
        
        echo "\nPlayStation tokens:\n";
        echo "Access Token: " . substr($authResult['playstation_tokens']['access_token'], 0, 10) . "...\n";
        echo "Refresh Token: " . (isset($authResult['playstation_tokens']['refresh_token']) ? substr($authResult['playstation_tokens']['refresh_token'], 0, 10) . "..." : "Not provided") . "\n";
        echo "Expires in: " . ($authResult['playstation_tokens']['expires_in'] ?? "Unknown") . " seconds\n";
        
        echo "\nJWT Token for FridayAI: " . substr($authResult['token'], 0, 20) . "...\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 