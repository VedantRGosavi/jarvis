<?php
namespace App\Utils;

use App\Utils\Database;
use App\Utils\Auth;

abstract class OAuthProvider {
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->loadConfig();
    }
    
    abstract protected function loadConfig();
    abstract public function getAuthorizationUrl();
    abstract public function handleCallback($code);
    
    protected function createOrUpdateUser($providerUserId, $email, $name, $provider) {
        // Check if user exists with this provider
        $user = $this->db->query(
            "SELECT * FROM oauth_accounts WHERE provider = ? AND provider_user_id = ?", 
            [$provider, $providerUserId]
        )->fetch();
        
        // If OAuth account exists, get the user
        if ($user) {
            $userData = $this->db->query("SELECT * FROM users WHERE id = ?", [$user['user_id']])->fetch();
            return $userData;
        }
        
        // Check if user exists with this email
        $userData = $this->db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
        
        // If no user, create one
        if (!$userData) {
            $timestamp = date('Y-m-d H:i:s');
            
            // Create random password for OAuth users
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            $this->db->query(
                "INSERT INTO users (name, email, password, created_at, last_login, subscription_status) 
                VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $email, $hashedPassword, $timestamp, $timestamp, 'none']
            );
            
            $userId = $this->db->lastInsertId();
            
            // Insert default user settings
            $this->db->query(
                "INSERT INTO user_settings (user_id, created_at, updated_at) VALUES (?, ?, ?)",
                [$userId, $timestamp, $timestamp]
            );
            
            $userData = $this->db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();
        } else {
            // Update last login
            $timestamp = date('Y-m-d H:i:s');
            $this->db->query(
                "UPDATE users SET last_login = ? WHERE id = ?",
                [$timestamp, $userData['id']]
            );
        }
        
        // Create or update OAuth account
        $this->db->query(
            "INSERT OR REPLACE INTO oauth_accounts (user_id, provider, provider_user_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?)",
            [$userData['id'], $provider, $providerUserId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );
        
        return $userData;
    }
    
    protected function generateToken($userId) {
        return Auth::generateToken($userId);
    }

    /**
     * Check if the provider is properly configured
     *
     * @return bool True if all required configuration values are set
     */
    public function isConfigured()
    {
        // Base check to see if client ID and redirect URI are configured
        // Client secret is checked if the provider uses it
        $clientIdConfigured = !empty($this->clientId) && $this->clientId !== 'your_' . strtolower($this->providerName) . '_client_id';
        $redirectUriConfigured = !empty($this->redirectUri);
        
        // Different providers have different requirements
        if ($this->providerName === 'steam') {
            // Steam uses API key instead of client secret
            $apiKey = getenv('STEAM_API_KEY');
            $apiKeyConfigured = !empty($apiKey) && $apiKey !== 'your_steam_api_key';
            return $clientIdConfigured && $redirectUriConfigured && $apiKeyConfigured;
        } else if ($this->providerName === 'playstation') {
            // PlayStation can use either client secret or NPSSO token
            $clientSecretConfigured = !empty($this->clientSecret) && 
                $this->clientSecret !== 'your_' . strtolower($this->providerName) . '_client_secret';
            
            // Check for NPSSO token
            $npsso = getenv('PLAYSTATION_NPSSO');
            $npssoConfigured = !empty($npsso);
            
            // We need either client secret or NPSSO token, but not necessarily both
            $credentialsConfigured = $clientSecretConfigured || $npssoConfigured;
            
            return $clientIdConfigured && $redirectUriConfigured && $credentialsConfigured;
        } else {
            // Most OAuth providers use client secret
            $clientSecretConfigured = !empty($this->clientSecret) && 
                $this->clientSecret !== 'your_' . strtolower($this->providerName) . '_client_secret';
            return $clientIdConfigured && $clientSecretConfigured && $redirectUriConfigured;
        }
    }

    /**
     * Make an HTTP GET request
     *
     * @param string $url The URL to request
     * @param array $params Additional query parameters
     * @param array $headers Additional headers
     * @param bool $followRedirect Whether to follow redirects or capture them
     * @return array|false The response data or false on failure
     */
    protected function httpGet($url, $params = [], $headers = [], $captureRedirect = false) {
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, $captureRedirect);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($captureRedirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($captureRedirect && ($httpCode >= 300 && $httpCode < 400)) {
            $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);
            return ['redirect_url' => $redirectUrl];
        }
        
        curl_close($ch);
        
        if ($response === false) {
            error_log('HTTP GET Error: ' . curl_error($ch));
            return false;
        }
        
        // Try to decode as JSON
        $jsonData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
        
        // If not JSON, return as is
        return $response;
    }
    
    /**
     * Make an HTTP POST request
     *
     * @param string $url The URL to request
     * @param array $data The data to send
     * @param array $headers Additional headers
     * @return array|false The response data or false on failure
     */
    protected function httpPost($url, $data = [], $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === false) {
            error_log('HTTP POST Error: ' . curl_error($ch));
            return false;
        }
        
        // Try to decode as JSON
        $jsonData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
        
        // If not JSON, return as is
        return $response;
    }
} 