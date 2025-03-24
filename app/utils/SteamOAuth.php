<?php
namespace App\Utils;

class SteamOAuth extends OAuthProvider {
    protected $apiKey;
    protected $providerName = 'steam';

    protected function loadConfig() {
        $this->clientId = $_ENV['STEAM_CLIENT_ID'] ?? getenv('STEAM_CLIENT_ID') ?? '';
        $this->redirectUri = $_ENV['STEAM_REDIRECT_URI'] ?? getenv('STEAM_REDIRECT_URI') ?? '';
        $this->apiKey = $_ENV['STEAM_API_KEY'] ?? getenv('STEAM_API_KEY') ?? '';
    }

    public function getAuthorizationUrl() {
        // Generate and store a state parameter for CSRF protection
        $state = $this->generateState();
        $this->storeState($state);

        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $this->redirectUri . '?state=' . $state,
            'openid.realm' => parse_url($this->redirectUri, PHP_URL_SCHEME) . '://' . parse_url($this->redirectUri, PHP_URL_HOST),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
        ];

        return 'https://steamcommunity.com/openid/login?' . http_build_query($params);
    }

    /**
     * Validate a callback from Steam OpenID
     *
     * @param array $params Parameters from the callback
     * @return string|false SteamID if valid, false if not
     */
    public function validateCallback($params) {
        // Check state parameter for CSRF protection
        if (isset($params['state'])) {
            if (!$this->verifyState($params['state'])) {
                error_log('Steam OAuth: State parameter mismatch');
                return false;
            }
        }

        // Validate response
        if (!$this->validateResponse($params)) {
            error_log('Steam OAuth: Failed to validate response');
            return false;
        }

        // Extract Steam ID
        $steamId = $this->extractSteamId($params['openid_claimed_id'] ?? '');

        if (empty($steamId)) {
            error_log('Steam OAuth: Failed to extract Steam ID from: ' . ($params['openid_claimed_id'] ?? 'not provided'));
            return false;
        }

        // Store the Steam ID for later use
        $this->steamId = $steamId;

        return $steamId;
    }

    /**
     * Get access token (not used for Steam, but required for the interface)
     *
     * @param string $code Authorization code (not used)
     * @param string $state CSRF state (not used)
     * @return string Always returns 'steam_auth' as Steam doesn't use OAuth 2.0
     */
    public function getAccessToken($code = null, $state = null) {
        // Steam doesn't use OAuth 2.0 with access tokens
        // This method is implemented for interface compatibility
        return 'steam_auth';
    }

    /**
     * Get user information from Steam API
     *
     * @return array|null User information or null if failed
     */
    public function getUserInfo() {
        if (empty($this->steamId)) {
            error_log('Steam OAuth: No Steam ID available');
            return null;
        }

        if (empty($this->apiKey)) {
            error_log('Steam OAuth: No API key provided, returning basic user info');
            // Return basic info if no API key
            return [
                'steamid' => $this->steamId,
                'personaname' => 'Steam User',
                'id' => $this->steamId
            ];
        }

        $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key={$this->apiKey}&steamids={$this->steamId}";

        $result = $this->httpGet($url);

        if ($result === false) {
            error_log('Steam OAuth: Failed to get player summary from Steam API');
            return null;
        }

        $player = $result['response']['players'][0] ?? null;

        if (empty($player)) {
            error_log('Steam OAuth: No player data found in API response');
            return null;
        }

        // Store the raw response
        $this->rawResponse = $result;

        // Format the user info to be consistent with other providers
        return [
            'id' => $player['steamid'],
            'name' => $player['personaname'],
            'picture' => $player['avatarfull'] ?? null,
            'profile' => $player['profileurl'] ?? null,
            'steamid' => $player['steamid']
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

    private function validateResponse($params) {
        // Check if required parameters exist
        if (!isset($params['openid_assoc_handle']) ||
            !isset($params['openid_signed']) ||
            !isset($params['openid_sig'])) {
            error_log('Steam OAuth: Missing required parameters');
            return false;
        }

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
        $result = $this->httpPost($url, $validationParams);

        // For Steam, the response is a string not JSON
        if (is_string($result)) {
            return strpos($result, 'is_valid:true') !== false;
        }

        return false;
    }

    private function extractSteamId($claimedId) {
        if (empty($claimedId)) {
            return null;
        }

        preg_match('/^https?:\/\/steamcommunity\.com\/openid\/id\/(\d+)$/', $claimedId, $matches);
        return $matches[1] ?? null;
    }

    public function handleCallback($code) {
        // For compatibility with the older interface
        // This handles OpenID parameters, not an authorization code
        $steamId = $this->validateCallback($_GET);

        if (!$steamId) {
            return [
                'success' => false,
                'error' => 'Steam authentication failed'
            ];
        }

        // Get user info
        $userInfo = $this->getUserInfo();

        if (empty($userInfo)) {
            // Use basic information if API doesn't work
            $userInfo = [
                'steamid' => $steamId,
                'personaname' => 'Steam User',
                'id' => $steamId
            ];
        }

        // Since Steam doesn't provide email, we'll use a placeholder
        $email = "steam_" . $steamId . "@fridayai.me";

        // Create or update user
        $userData = $this->createOrUpdateUser(
            $steamId,
            $email,
            $userInfo['personaname'] ?? 'Steam User',
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
                'subscription_status' => $userData['subscription_status'],
                'avatar' => $userInfo['avatarfull'] ?? null
            ]
        ];
    }
}
