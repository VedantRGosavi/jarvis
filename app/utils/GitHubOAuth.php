<?php
namespace App\Utils;

class GitHubOAuth extends OAuthProvider {
    protected function loadConfig() {
        $this->clientId = $_ENV['GITHUB_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['GITHUB_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['GITHUB_REDIRECT_URI'] ?? '';
    }
    
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'read:user user:email',
            'allow_signup' => 'true'
        ];
        
        return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
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
        
        // Get email if not provided in user info
        if (!isset($userInfo['email']) || empty($userInfo['email'])) {
            $emails = $this->getUserEmails($token);
            if (!empty($emails)) {
                // Find primary email
                foreach ($emails as $email) {
                    if ($email['primary']) {
                        $userInfo['email'] = $email['email'];
                        break;
                    }
                }
                
                // If no primary email found, just use the first one
                if (empty($userInfo['email'])) {
                    $userInfo['email'] = $emails[0]['email'];
                }
            }
        }
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $userInfo['id'],
            $userInfo['email'],
            $userInfo['name'] ?? $userInfo['login'],
            'github'
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
        $url = 'https://github.com/login/oauth/access_token';
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
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
        $url = 'https://api.github.com/user';
        
        $options = [
            'http' => [
                'header' => "Authorization: token $accessToken\r\nUser-Agent: FridayAI\r\n",
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
    
    private function getUserEmails($accessToken) {
        $url = 'https://api.github.com/user/emails';
        
        $options = [
            'http' => [
                'header' => "Authorization: token $accessToken\r\nUser-Agent: FridayAI\r\n",
                'method' => 'GET'
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return [];
        }
        
        return json_decode($result, true);
    }
} 