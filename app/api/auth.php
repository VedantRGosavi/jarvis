<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/utils/OAuthProvider.php';
require_once BASE_PATH . '/app/utils/OAuthFactory.php';
require_once BASE_PATH . '/app/utils/GoogleOAuth.php';
require_once BASE_PATH . '/app/utils/GitHubOAuth.php';
require_once BASE_PATH . '/app/utils/PlayStationOAuth.php';
require_once BASE_PATH . '/app/utils/SteamOAuth.php';

use App\Utils\Response;
use App\Utils\OAuthFactory;
use App\Models\User;

// Enable CORS for API requests - support both fridayai.me and fridayai-gold.vercel.app
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowedOrigins = [
        'https://fridayai.me',
        'https://fridayai-gold.vercel.app',
        'http://localhost:8000'
    ];

    if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    }
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

// Log the request for debugging
error_log("API Request: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $method);
if ($data) {
    error_log("Request Data: " . json_encode($data));
}

// Route to appropriate auth handler
$action = $api_segments[1] ?? '';

// Check if the request is for CSRF token
if (isset($api_segments[1]) && $api_segments[1] === 'csrf') {
    // Handle CSRF token request
    require_once BASE_PATH . '/app/utils/Security.php';

    // Return the CSRF token
    echo json_encode([
        'csrf_token' => \App\Utils\Security::generateCSRFToken()
    ]);
    exit;
}

// OAuth debug endpoint - only available in development mode
if (isset($api_segments[1]) && $api_segments[1] === 'debug' && ($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'development') {
    $provider = $api_segments[2] ?? '';

    if (empty($provider)) {
        Response::error('Provider is required', 400);
        exit;
    }

    try {
        $oauthProvider = OAuthFactory::getProvider($provider);
        $debug = $oauthProvider->debugEnvironment();
        Response::success($debug);
    } catch (\Exception $e) {
        Response::error($e->getMessage(), 400);
    }
    exit;
}

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password required', 400);
            break;
        }

        $user = new User();
        $result = $user->authenticate($data['email'], $data['password']);

        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } else {
            Response::error('Invalid credentials', 401);
        }
        break;

    case 'register':
        if ($method !== 'POST' && $method !== 'OPTIONS') {
            Response::error('Method not allowed', 405);
            break;
        }

        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            Response::error('Name, email and password required', 400);
            break;
        }

        try {
            $user = new User();
            $result = $user->register($data['name'], $data['email'], $data['password']);

            if ($result['success']) {
                Response::success([
                    'token' => $result['token'],
                    'user' => $result['user']
                ]);
            } else {
                Response::error($result['message'] ?? 'Registration failed', 400);
            }
        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            Response::error('Registration failed: ' . $e->getMessage(), 500);
        }
        break;

    case 'verify':
        if ($method !== 'GET') {
            Response::error('Method not allowed', 405);
            break;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token || !\App\Utils\Auth::validateToken($token)) {
            Response::error('Invalid token', 401);
            break;
        }

        $userId = \App\Utils\Auth::getUserIdFromToken($token);
        $user = new User();
        $userData = $user->getById($userId);

        if (!$userData) {
            Response::error('User not found', 404);
            break;
        }

        Response::success(['user' => $userData]);
        break;

    case 'oauth':
        // Handles OAuth provider authorization URLs
        $provider = $api_segments[2] ?? '';

        if (empty($provider)) {
            Response::error('Provider is required', 400);
            break;
        }

        try {
            $oauthProvider = OAuthFactory::getProvider($provider);
            $authUrl = $oauthProvider->getAuthorizationUrl();

            Response::success(['auth_url' => $authUrl]);
        } catch (\Exception $e) {
            error_log("OAuth URL error for $provider: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
        break;

    case 'callback':
        // Handles OAuth callback
        $provider = $api_segments[2] ?? '';

        if (empty($provider)) {
            Response::error('Provider is required', 400);
            break;
        }

        try {
            $oauthProvider = OAuthFactory::getProvider($provider);

            // Different providers have different callback parameters
            if ($provider === 'steam') {
                $result = $oauthProvider->handleCallback($_GET);
            } else {
                $code = $_GET['code'] ?? '';
                if (empty($code)) {
                    Response::error('Authorization code is required', 400);
                    break;
                }
                $result = $oauthProvider->handleCallback($code);
            }

            if ($result['success']) {
                // Redirect to frontend with token
                $redirectUrl = $_ENV['FRONTEND_URL'] . '/auth/callback?token=' . $result['token'];
                header("Location: $redirectUrl");
                exit;
            } else {
                Response::error($result['error'] ?? 'Authentication failed', 400);
            }
        } catch (\Exception $e) {
            error_log("OAuth callback error for $provider: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
        break;

    default:
        Response::error('Auth endpoint not found', 404);
}
