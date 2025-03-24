<?php
namespace App\Utils;

class PlayStationOAuth extends OAuthProvider {
    protected $providerName = 'playstation';
    protected $rawResponse = null;
    protected $idToken = null;
    protected $npsso = null;
    
    protected function loadConfig() {
        $this->clientId = getenv('PLAYSTATION_CLIENT_ID') ?? '';
        $this->clientSecret = getenv('PLAYSTATION_CLIENT_SECRET') ?? '';
        $this->redirectUri = getenv('PLAYSTATION_REDIRECT_URI') ?? '';
        $this->npsso = getenv('PLAYSTATION_NPSSO') ?? '';
    }
    
    public function getAuthorizationUrl() {
        $state = $this->generateState();
        $this->storeState($state);
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'psn:s2s openid user:account.profile.read user:account.settings.privacy.read',
            'state' => $state
        ];
        
        return 'https://ca.account.sony.com/api/authz/v3/oauth/authorize?' . http_build_query($params);
    }
    
    public function getAccessToken($code, $state = null) {
        // Verify state parameter if present
        if ($state !== null && !$this->verifyState($state)) {
            error_log('PlayStation OAuth: Invalid state parameter');
            return null;
        }
        
        $url = 'https://ca.account.sony.com/api/authz/v3/oauth/token';
        
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $result = $this->httpPost($url, $data, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        if ($result === false) {
            error_log('PlayStation OAuth: Failed to get access token');
            return null;
        }
        
        // Store the raw response for debugging
        $this->rawResponse = $result;
        
        // Extract the id token if present
        if (isset($result['id_token'])) {
            $this->idToken = $result['id_token'];
        }
        
        // Log error for debugging if needed
        if (!isset($result['access_token'])) {
            error_log('PlayStation OAuth: Access token not found in response: ' . json_encode($result));
            return null;
        }
        
        return $result['access_token'];
    }
    
    public function getUserInfo() {
        // If we don't have a token, we can't get user info
        if (empty($this->rawResponse['access_token'])) {
            error_log('PlayStation OAuth: No access token available');
            return null;
        }
        
        $accessToken = $this->rawResponse['access_token'];
        $url = 'https://ca.account.sony.com/api/v1/accounts/me';
        
        $result = $this->httpGet($url, [], [
            "Authorization: Bearer $accessToken",
            "Accept: application/json"
        ]);
        
        if ($result === false) {
            error_log('PlayStation OAuth: Failed to get user info');
            return $this->getBasicProfile($accessToken);
        }
        
        // If we couldn't get user data, try getting just the basic profile
        if (empty($result) || !isset($result['online_id'])) {
            return $this->getBasicProfile($accessToken);
        }
        
        // Format the user info to be consistent with other providers
        return [
            'id' => $result['account_id'] ?? $result['user_id'] ?? uniqid('psn_'),
            'name' => $result['online_id'] ?? 'PSN User',
            'email' => $result['email'] ?? $result['online_id'] . '@psn.fridayai.me',
            'online_id' => $result['online_id'] ?? 'PSN User',
            'country' => $result['country'] ?? 'Unknown',
            'language' => $result['language'] ?? 'en'
        ];
    }
    
    private function getBasicProfile($accessToken) {
        $url = 'https://ca.account.sony.com/api/v1/accounts/profile';
        
        $result = $this->httpGet($url, [], [
            "Authorization: Bearer $accessToken",
            "Accept: application/json"
        ]);
        
        if ($result === false) {
            error_log('PlayStation OAuth: Failed to get basic profile');
            return [
                'id' => uniqid('psn_'), 
                'name' => 'PSN User',
                'online_id' => 'PSN User'
            ];
        }
        
        // If no profile found, return default values
        if (empty($result)) {
            error_log('PlayStation OAuth: Empty profile response');
            return [
                'id' => uniqid('psn_'), 
                'name' => 'PSN User',
                'online_id' => 'PSN User'
            ];
        }
        
        // Format the basic profile info
        return [
            'id' => $result['account_id'] ?? $result['user_id'] ?? uniqid('psn_'),
            'name' => $result['online_id'] ?? 'PSN User',
            'online_id' => $result['online_id'] ?? 'PSN User'
        ];
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
        $userInfo = $this->getUserInfo();
        
        if (empty($userInfo)) {
            return [
                'success' => false,
                'error' => 'Failed to get user info'
            ];
        }
        
        // Create a user email from PSN ID if not provided
        $email = $userInfo['email'] ?? $userInfo['online_id'] . '@psn.fridayai.me';
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $userInfo['id'],
            $email,
            $userInfo['name'] ?? $userInfo['online_id'] ?? 'PSN User',
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
    
    /**
     * Get the raw response from the provider
     *
     * @return array|null
     */
    public function getRawResponse() {
        return $this->rawResponse;
    }
    
    /**
     * Get the ID token (OpenID Connect)
     *
     * @return string|null
     */
    public function getIdToken() {
        return $this->idToken;
    }
    
    /**
     * Exchange NPSSO token for authorization code
     * 
     * @return string|null
     */
    public function exchangeNpssoForCode() {
        if (empty($this->npsso)) {
            error_log('PlayStation OAuth: NPSSO token not configured');
            return null;
        }
        
        $url = 'https://ca.account.sony.com/api/authz/v3/oauth/authorize';
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'psn:s2s openid user:account.profile.read user:account.settings.privacy.read'
        ];
        
        $headers = [
            'Cookie: npsso=' . $this->npsso,
        ];
        
        // First make a GET request to get cookies and CSRF token
        $response = $this->httpGet($url . '?' . http_build_query($params), [], $headers, true);
        
        if ($response === false || empty($response)) {
            error_log('PlayStation OAuth: Failed to get CSRF token');
            return null;
        }
        
        // Extract the authorization code from the redirect URL
        if (isset($response['redirect_url']) && strpos($response['redirect_url'], 'code=') !== false) {
            $parts = parse_url($response['redirect_url']);
            parse_str($parts['query'] ?? '', $query);
            return $query['code'] ?? null;
        }
        
        error_log('PlayStation OAuth: Authorization code not found in response');
        return null;
    }
    
    /**
     * Exchange authorization code for access token directly from NPSSO
     * 
     * @return array|null
     */
    public function exchangeNpssoForAccessToken() {
        $code = $this->exchangeNpssoForCode();
        
        if (empty($code)) {
            error_log('PlayStation OAuth: Failed to exchange NPSSO for code');
            return null;
        }
        
        $accessToken = $this->getAccessToken($code);
        
        if (empty($accessToken)) {
            error_log('PlayStation OAuth: Failed to exchange code for access token');
            return null;
        }
        
        return $this->rawResponse;
    }
    
    /**
     * Simplified method to authenticate a user using NPSSO token
     * 
     * @return array
     */
    public function authenticateWithNpsso() {
        $authorization = $this->exchangeNpssoForAccessToken();
        
        if (empty($authorization) || !isset($authorization['access_token'])) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with NPSSO token'
            ];
        }
        
        // Get user info using the token
        $userInfo = $this->getUserInfo();
        
        if (empty($userInfo)) {
            return [
                'success' => false,
                'error' => 'Failed to get user info'
            ];
        }
        
        // Create a user email from PSN ID if not provided
        $email = $userInfo['email'] ?? $userInfo['online_id'] . '@psn.fridayai.me';
        
        // Create or update user
        $userData = $this->createOrUpdateUser(
            $userInfo['id'],
            $email,
            $userInfo['name'] ?? $userInfo['online_id'] ?? 'PSN User',
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
            ],
            'playstation_tokens' => [
                'access_token' => $authorization['access_token'],
                'refresh_token' => $authorization['refresh_token'] ?? null,
                'expires_in' => $authorization['expires_in'] ?? 3600
            ]
        ];
    }
} 