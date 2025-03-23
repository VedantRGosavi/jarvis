<?php
namespace App\Utils;

class SteamOAuth extends OAuthProvider {
    protected $apiKey;
    
    protected function loadConfig() {
        $this->clientId = $_ENV['STEAM_CLIENT_ID'] ?? '';
        $this->redirectUri = $_ENV['STEAM_REDIRECT_URI'] ?? '';
        $this->apiKey = $_ENV['STEAM_API_KEY'] ?? '';
    }
    
    public function getAuthorizationUrl() {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $this->redirectUri,
            'openid.realm' => parse_url($this->redirectUri, PHP_URL_SCHEME) . '://' . parse_url($this->redirectUri, PHP_URL_HOST),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
        ];
        
        return 'https://steamcommunity.com/openid/login?' . http_build_query($params);
    }
    
    public function handleCallback($params) {
        // Validate response
        if (!$this->validateResponse($params)) {
            return [
                'success' => false,
                'error' => 'Invalid Steam authentication response'
            ];
        }
        
        // Extract Steam ID
        $steamId = $this->extractSteamId($params['openid_claimed_id']);
        
        if (empty($steamId)) {
            return [
                'success' => false,
                'error' => 'Failed to extract Steam ID'
            ];
        }
        
        // Get user info
        $userInfo = $this->getUserInfo($steamId);
        
        if (empty($userInfo)) {
            return [
                'success' => false,
                'error' => 'Failed to get user info'
            ];
        }
        
        // Since Steam doesn't provide email, we'll use a placeholder
        $email = "steam_" . $steamId . "@fridayai.me";
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $steamId,
            $email,
            $userInfo['personaname'],
            'steam'
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
    
    private function validateResponse($params) {
        // Build validation URL
        $validationParams = [
            'openid.assoc_handle' => $params['openid_assoc_handle'],
            'openid.signed' => $params['openid_signed'],
            'openid.sig' => $params['openid_sig'],
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'check_authentication'
        ];
        
        // Add signed parameters
        $signedParams = explode(',', $params['openid_signed']);
        foreach ($signedParams as $param) {
            $paramKey = 'openid_' . str_replace('.', '_', $param);
            if (isset($params[$paramKey])) {
                $validationParams['openid.' . $param] = $params[$paramKey];
            }
        }
        
        // Make validation request
        $url = 'https://steamcommunity.com/openid/login';
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($validationParams)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return false;
        }
        
        return strpos($result, 'is_valid:true') !== false;
    }
    
    private function extractSteamId($claimedId) {
        preg_match('/^https?:\/\/steamcommunity\.com\/openid\/id\/(\d+)$/', $claimedId, $matches);
        return $matches[1] ?? null;
    }
    
    private function getUserInfo($steamId) {
        if (empty($this->apiKey)) {
            // Return basic info if no API key
            return [
                'steamid' => $steamId,
                'personaname' => 'Steam User'
            ];
        }
        
        $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key={$this->apiKey}&steamids={$steamId}";
        $result = file_get_contents($url);
        
        if ($result === false) {
            return null;
        }
        
        $data = json_decode($result, true);
        return $data['response']['players'][0] ?? null;
    }
} 