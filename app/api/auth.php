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

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate auth handler
$action = $api_segments[1] ?? '';

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
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            Response::error('Name, email and password required', 400);
            break;
        }

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
            Response::error($e->getMessage(), 400);
        }
        break;

    default:
        Response::error('Auth endpoint not found', 404);
}
