<?php
namespace App\Utils;

class PlayStationOAuth extends OAuthProvider {
    protected function loadConfig() {
        $this->clientId = $_ENV['PLAYSTATION_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['PLAYSTATION_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['PLAYSTATION_REDIRECT_URI'] ?? '';
    }
    
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'psn:s2s',
            'state' => bin2hex(random_bytes(16))
        ];
        
        // Store state in session for CSRF protection
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://ca.account.sony.com/api/authz/v3/oauth/authorize?' . http_build_query($params);
    }
    
    public function handleCallback($code) {
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
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $userInfo['id'],
            $userInfo['email'],
            $userInfo['online_id'],
            'playstation'
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
                'subscription_status' => $userData['subscription_status']
            ]
        ];
    }
    
    private function getAccessToken($code) {
        $url = 'https://ca.account.sony.com/api/authz/v3/oauth/token';
        
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return null;
        }
        
        $decoded = json_decode($result, true);
        return $decoded['access_token'] ?? null;
    }
    
    private function getUserInfo($accessToken) {
        $url = 'https://ca.account.sony.com/api/v1/accounts/me';
        
        $options = [
            'http' => [
                'header' => "Authorization: Bearer $accessToken\r\n",
                'method' => 'GET'
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return null;
        }
        
        return json_decode($result, true);
    }
} 