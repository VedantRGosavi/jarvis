<?php
/**
 * OAuth Test Tool
 * 
 * This tool is designed to test OAuth provider connections and verify their functionality.
 * It displays connection status and relevant debugging information without affecting the main application.
 */

// Only run in development mode
if (getenv('APP_ENV') !== 'development') {
    die('This tool is only available in development mode');
}

// Load environment variables
$dotenv = __DIR__ . '/../../.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Include OAuth provider classes
require_once __DIR__ . '/../utils/OAuthProvider.php';
require_once __DIR__ . '/../utils/GoogleOAuth.php';
require_once __DIR__ . '/../utils/GitHubOAuth.php';
require_once __DIR__ . '/../utils/PlayStationOAuth.php';
require_once __DIR__ . '/../utils/SteamOAuth.php';

// Handle provider actions
$action = $_GET['action'] ?? 'status';
$provider = $_GET['provider'] ?? '';

// Define provider info with logos
$providers = [
    'google' => [
        'name' => 'Google',
        'class' => 'GoogleOAuth',
        'logo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>',
    ],
    'github' => [
        'name' => 'GitHub',
        'class' => 'GitHubOAuth',
        'logo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#181717"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
    ],
    'playstation' => [
        'name' => 'PlayStation',
        'class' => 'PlayStationOAuth',
        'logo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#003791"><path d="M23.845 12.53a2.187 2.187 0 0 0-1.19-1.515c-1.47-.812-3.962.395-3.962.395v2.043s2.49-1.18 3.359-.615c.38.245.507.565.38.9-.19.48-.912.668-1.634.772v-5.843l-3.921 1.265v14.747l2.756-.902c2.126-.663 4.336-3.516 4.211-4.887-.171-1.718-.998-4.36-.998-6.36M4.007 21.047l5.412-1.954V14.88L4.007 16.894v4.153zm0-5.648l7.53-2.576c2.26-.778 2.463-1.456 1.118-2.042-1.344-.586-3.363-.125-5.514.636L4.01 12.11v-4.65s3.532-1.28 6.157-1.783c2.625-.504 5.348.21 6.358 1.205.755.763 1.305 1.723 1.305 3.113v11.744l-7.629 2.675-6.194 1.602V15.399zM22.963 8.83a.666.666 0 0 1-.64-.694.75.75 0 0 1 .764-.743c.375 0 .644.318.644.694a.722.722 0 0 1-.768.743m-.005-1.333c-.348 0-.624.3-.624.64 0 .336.264.64.624.64.36 0 .625-.304.625-.64 0-.34-.276-.64-.625-.64m.145.424c0-.08-.055-.12-.14-.12h-.12v.384h.084v-.136h.068l.068.136h.096l-.08-.136c.048-.008.084-.04.084-.128h-.06zm-.171.06v-.112h.044c.04 0 .072.004.072.052 0 .044-.032.06-.072.06h-.044z"/></svg>',
    ],
    'steam' => [
        'name' => 'Steam',
        'class' => 'SteamOAuth',
        'logo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#000000"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.605 0 11.979 0zM7.54 18.21l-1.473-.61c.262.543.714.999 1.314 1.25 1.297.539 2.793-.076 3.332-1.375.263-.63.264-1.319.005-1.949s-.75-1.121-1.377-1.383c-.624-.26-1.29-.249-1.878-.03l1.523.63c.956.4 1.409 1.5 1.009 2.455-.397.957-1.497 1.41-2.454 1.012H7.54zm11.415-9.303c0-1.662-1.353-3.015-3.015-3.015-1.665 0-3.015 1.353-3.015 3.015 0 1.665 1.35 3.015 3.015 3.015 1.663 0 3.015-1.35 3.015-3.015zm-5.273-.005c0-1.252 1.013-2.266 2.265-2.266 1.249 0 2.266 1.014 2.266 2.266 0 1.251-1.017 2.265-2.266 2.265-1.253 0-2.265-1.014-2.265-2.265z"/></svg>',
    ]
];

/**
 * Get the provider instance
 */
function getProvider($providerName) {
    global $providers;
    
    if (!isset($providers[$providerName])) {
        return null;
    }
    
    $className = '\\App\\Utils\\' . $providers[$providerName]['class'];
    return new $className();
}

/**
 * Display callback result
 */
function displayCallbackResult($provider, $result) {
    global $providers;
    
    $userInfo = $result['user_info'] ?? null;
    $accessToken = $result['access_token'] ?? null;
    $idToken = $result['id_token'] ?? null;
    $rawResponse = $result['raw_response'] ?? null;
    
    echo '<div class="result-container">';
    echo '<h2>Authentication Result for ' . $providers[$provider]['name'] . '</h2>';
    
    if (!empty($userInfo)) {
        echo '<div class="success-message">Authentication successful! User information retrieved.</div>';
        
        echo '<div class="user-info">';
        echo '<div class="user-avatar">';
        if (isset($userInfo['picture'])) {
            echo '<img src="' . htmlspecialchars($userInfo['picture']) . '" alt="User Avatar">';
        } else {
            echo '<span>' . substr($userInfo['name'] ?? 'U', 0, 1) . '</span>';
        }
        echo '</div>';
        echo '<div class="user-details">';
        echo '<h3 class="user-name">' . htmlspecialchars($userInfo['name'] ?? 'Unknown Name') . '</h3>';
        if (isset($userInfo['email'])) {
            echo '<p class="user-email">' . htmlspecialchars($userInfo['email']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '<h3>User Information</h3>';
        echo '<table>';
        foreach ($userInfo as $key => $value) {
            if ($key === 'picture') continue; // Skip picture as we've already displayed it
            echo '<tr>';
            echo '<th>' . htmlspecialchars($key) . '</th>';
            echo '<td>' . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="error-message">Authentication failed or user information not retrieved.</div>';
    }
    
    if ($accessToken) {
        echo '<h3>Access Token</h3>';
        echo '<div class="token-container">';
        // Only show part of the token for security
        $maskedToken = substr($accessToken, 0, 10) . '...' . substr($accessToken, -10);
        echo '<div class="token-value">' . htmlspecialchars($maskedToken) . '</div>';
        echo '</div>';
    }
    
    if ($idToken) {
        echo '<h3>ID Token</h3>';
        echo '<div class="token-container">';
        // Only show part of the token for security
        $maskedToken = substr($idToken, 0, 10) . '...' . substr($idToken, -10);
        echo '<div class="token-value">' . htmlspecialchars($maskedToken) . '</div>';
        echo '</div>';
    }
    
    if ($rawResponse) {
        echo '<h3>Raw Response Data</h3>';
        echo '<pre>' . htmlspecialchars(json_encode($rawResponse, JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    echo '<a href="oauth-test.php" class="back-link">← Back to provider list</a>';
    echo '</div>';
}

/**
 * Show error message
 */
function showError($message) {
    echo '<div class="error-message">' . htmlspecialchars($message) . '</div>';
}

// Main handler logic
$frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:8000';
$baseUrl = $frontendUrl . '/app/tools/oauth-test.php';

// Process the request based on action
$processingResult = null;

if ($action === 'authorize' && !empty($provider)) {
    try {
        $providerInstance = getProvider($provider);
        if (!$providerInstance) {
            throw new Exception("Unknown provider: $provider");
        }
        
        // Set test callback URL
        $callbackUrl = "$baseUrl?action=callback&provider=$provider";
        $providerInstance->setRedirectUri($callbackUrl);
        
        // Get authorization URL and redirect
        $authUrl = $providerInstance->getAuthorizationUrl();
        header("Location: $authUrl");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif ($action === 'callback' && !empty($provider)) {
    try {
        $providerInstance = getProvider($provider);
        if (!$providerInstance) {
            throw new Exception("Unknown provider: $provider");
        }
        
        // Set test callback URL
        $callbackUrl = "$baseUrl?action=callback&provider=$provider";
        $providerInstance->setRedirectUri($callbackUrl);
        
        // Process the callback
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        
        if (empty($code) && $provider === 'steam') {
            // Steam uses a different flow
            $steamId = $providerInstance->validateCallback($_GET);
            if ($steamId) {
                $userInfo = $providerInstance->getUserInfo();
                $processingResult = [
                    'user_info' => $userInfo,
                    'access_token' => 'steam_auth_successful',
                    'raw_response' => $_GET
                ];
            } else {
                throw new Exception("Steam authentication failed");
            }
        } else {
            // Standard OAuth 2.0 flow
            if (empty($code)) {
                throw new Exception("No authorization code provided");
            }
            
            $accessToken = $providerInstance->getAccessToken($code, $state);
            if (empty($accessToken)) {
                throw new Exception("Failed to get access token");
            }
            
            $userInfo = $providerInstance->getUserInfo();
            if (empty($userInfo)) {
                throw new Exception("Failed to get user information");
            }
            
            $processingResult = [
                'user_info' => $userInfo,
                'access_token' => $accessToken,
                'id_token' => $providerInstance->getIdToken(),
                'raw_response' => $providerInstance->getRawResponse()
            ];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Test Tool - FridayAI</title>
    <link rel="stylesheet" href="oauth-test.css">
</head>
<body>
    <h1>FridayAI OAuth Test Tool</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($processingResult): ?>
        <?php displayCallbackResult($provider, $processingResult); ?>
    <?php else: ?>
        <div class="info-panel">
            <p>This tool helps you test the OAuth integration for FridayAI. It shows the configuration status for each provider and allows you to test the authentication flow.</p>
            <p>Make sure you have the necessary environment variables set in your <code>.env</code> file before testing.</p>
        </div>
        
        <h2>OAuth Provider Status</h2>
        
        <div class="provider-grid">
            <?php foreach ($providers as $providerKey => $providerInfo): ?>
                <?php
                $providerInstance = getProvider($providerKey);
                $isConfigured = $providerInstance->isConfigured();
                ?>
                <div class="provider-card">
                    <h3>
                        <span class="logo"><?= $providerInfo['logo'] ?></span>
                        <?= $providerInfo['name'] ?>
                        <span class="status-indicator <?= $isConfigured ? 'status-configured' : 'status-not-configured' ?>"></span>
                    </h3>
                    <p>
                        Status: 
                        <?php if ($isConfigured): ?>
                            <strong style="color: #2ecc71;">Configured</strong>
                        <?php else: ?>
                            <strong style="color: #e74c3c;">Not Configured</strong>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($isConfigured): ?>
                        <a href="oauth-test.php?action=authorize&provider=<?= $providerKey ?>" class="test-button">Test Authentication</a>
                    <?php else: ?>
                        <p>Check your .env file for the required credentials.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <h2>Required Environment Variables</h2>
        
        <div class="result-container">
            <p>Make sure the following environment variables are set in your <code>.env</code> file:</p>
            
            <h3>Google OAuth</h3>
            <pre>GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/google</pre>

            <h3>GitHub OAuth</h3>
            <pre>GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/github</pre>

            <h3>PlayStation OAuth</h3>
            <pre>PLAYSTATION_CLIENT_ID=your_client_id
PLAYSTATION_CLIENT_SECRET=your_client_secret
PLAYSTATION_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/playstation</pre>

            <h3>Steam OAuth</h3>
            <pre>STEAM_API_KEY=your_api_key
STEAM_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/steam</pre>
            
            <p>For testing purposes, you should also add the following testing redirect URIs to your provider developer consoles:</p>
            <pre><?= $baseUrl ?>?action=callback&provider=[provider_name]</pre>
            
            <p>For more information, see the <a href="../../documentation/oauth-setup.md">OAuth Setup Documentation</a>.</p>
        </div>
    <?php endif; ?>
</body>
</html> 