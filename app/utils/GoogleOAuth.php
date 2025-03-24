<?php
namespace App\Utils;

class GoogleOAuth extends OAuthProvider {
    protected function loadConfig() {
        $this->clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
    }
    
    public function getAuthorizationUrl() {
        // Generate state for CSRF protection
        $state = bin2hex(random_bytes(16));
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['oauth_state'] = $state;
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile openid',
            'access_type' => 'online',
            'state' => $state,
            'prompt' => 'select_account'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    public function handleCallback($code) {
        // Verify state parameter
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (isset($_GET['state']) && isset($_SESSION['oauth_state']) && $_GET['state'] !== $_SESSION['oauth_state']) {
            error_log('Google OAuth: State parameter mismatch');
            return [
                'success' => false,
                'error' => 'Invalid state parameter'
            ];
        }
        
        // Exchange code for token
        $token = $this->getAccessToken($code);
        
        if (empty($token)) {
            return [
                'success' => false,
                'error' => 'Failed to get access token'
            ];
        }
        
        // Get user info using the token
        $userInfo = $this->getUserInfo($token);
        
        if (empty($userInfo)) {
            return [
                'success' => false,
                'error' => 'Failed to get user info'
            ];
        }
        
        // Ensure we have the necessary fields
        if (empty($userInfo['email'])) {
            error_log('Google OAuth: Email not provided in user info');
            return [
                'success' => false,
                'error' => 'Email is required'
            ];
        }
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $userInfo['sub'] ?? $userInfo['id'], // Use OpenID sub or regular id
            $userInfo['email'],
            $userInfo['name'],
            'google'
        );
        
        // Generate JWT token
        $jwtToken = $this->generateToken($userData['id']);
        
        return [
            'success' => true,
            'token' => $jwtToken,
            'user' => [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'subscription_status' => $userData['subscription_status'],
                'picture' => $userInfo['picture'] ?? null // Include profile picture if available
            ]
        ];
    }
    
    private function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true // Get response content even on error
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log('Google OAuth: Failed to get access token');
            return null;
        }
        
        $decoded = json_decode($result, true);
        
        if (!isset($decoded['access_token'])) {
            error_log('Google OAuth: Access token not found in response: ' . $result);
            return null;
        }
        
        return $decoded['access_token'];
    }
    
    private function getUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        
        $options = [
            'http' => [
                'header' => "Authorization: Bearer $accessToken\r\nAccept: application/json\r\n",
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log('Google OAuth: Failed to get user info');
            return null;
        }
        
        $userInfo = json_decode($result, true);
        
        if (empty($userInfo) || !isset($userInfo['email'])) {
            error_log('Google OAuth: Invalid user info response: ' . $result);
            return null;
        }
        
        return $userInfo;
    }
} 