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
} 