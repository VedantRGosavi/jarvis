<?php
namespace App\Models;

use App\Utils\Database;
use App\Utils\Auth;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getSystemInstance();
    }
    
    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $user = $this->db->fetchOne($stmt, [$email]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        return Auth::generateToken($user['id']);
    }
    
    public function register($email, $password, $username) {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        if ($this->db->fetchOne($stmt, [$email])) {
            return false;
        }
        
        // Create user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, username, password_hash, created_at) 
             VALUES (?, ?, ?, datetime('now'))"
        );
        
        if (!$this->db->exec($stmt, [$email, $username, $passwordHash])) {
            return false;
        }
        
        return Auth::generateToken($this->db->lastInsertId());
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function updateStripeCustomerId($userId, $stripeCustomerId) {
        $stmt = $this->db->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        return $stmt->execute([$stripeCustomerId, $userId]);
    }
    
    public function removeStripeCustomerId($stripeCustomerId) {
        $stmt = $this->db->prepare("UPDATE users SET stripe_customer_id = NULL WHERE stripe_customer_id = ?");
        return $stmt->execute([$stripeCustomerId]);
    }
    
    public function updateCustomerDetails($userId, $email, $name) {
        $stmt = $this->db->prepare("UPDATE users SET email = ?, name = ? WHERE id = ?");
        return $stmt->execute([$email, $name, $userId]);
    }
    
    public function updateSubscription($userId, $status, $endDate = null) {
        if ($endDate) {
            $stmt = $this->db->prepare("UPDATE users SET subscription_status = ?, subscription_end = ? WHERE id = ?");
            return $stmt->execute([$status, $endDate, $userId]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET subscription_status = ? WHERE id = ?");
            return $stmt->execute([$status, $userId]);
        }
    }
    
    public function getByStripeCustomerId($stripeCustomerId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE stripe_customer_id = ?");
        $stmt->execute([$stripeCustomerId]);
        return $stmt->fetch();
    }
    
    public function getSettings($userId) {
        $stmt = $this->db->prepare(
            "SELECT settings FROM user_settings WHERE user_id = ?"
        );
        $result = $this->db->fetchOne($stmt, [$userId]);
        return $result ? json_decode($result['settings'], true) : [];
    }
    
    public function updateSettings($userId, $settings) {
        $settingsJson = json_encode($settings);
        
        // Check if settings exist
        $stmt = $this->db->prepare("SELECT 1 FROM user_settings WHERE user_id = ?");
        $exists = $this->db->fetchOne($stmt, [$userId]);
        
        if ($exists) {
            $stmt = $this->db->prepare(
                "UPDATE user_settings 
                 SET settings = ?, updated_at = datetime('now') 
                 WHERE user_id = ?"
            );
            return $this->db->exec($stmt, [$settingsJson, $userId]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO user_settings (user_id, settings, created_at, updated_at) 
                 VALUES (?, ?, datetime('now'), datetime('now'))"
            );
            return $this->db->exec($stmt, [$userId, $settingsJson]);
        }
    }
    
    public function getGameProgress($userId, $gameId) {
        $gameDb = Database::getGameInstance($gameId);
        $stmt = $gameDb->prepare(
            "SELECT quest_id, step_id, completed, status, updated_at 
             FROM user_progress 
             WHERE user_id = ? 
             ORDER BY updated_at DESC"
        );
        return $gameDb->fetchAll($stmt, [$userId]);
    }
    
    public function updateGameProgress($userId, $gameId, $questId, $stepId = null, $completed = 0, $status = 'in_progress') {
        $gameDb = Database::getGameInstance($gameId);
        
        // Check if progress exists
        $stmt = $gameDb->prepare(
            "SELECT 1 FROM user_progress 
             WHERE user_id = ? AND quest_id = ? AND (step_id = ? OR (? IS NULL AND step_id IS NULL))"
        );
        $exists = $gameDb->fetchOne($stmt, [$userId, $questId, $stepId, $stepId]);
        
        if ($exists) {
            $stmt = $gameDb->prepare(
                "UPDATE user_progress 
                 SET completed = ?, status = ?, updated_at = datetime('now') 
                 WHERE user_id = ? AND quest_id = ? AND (step_id = ? OR (? IS NULL AND step_id IS NULL))"
            );
            return $gameDb->exec($stmt, [$completed, $status, $userId, $questId, $stepId, $stepId]);
        } else {
            $stmt = $gameDb->prepare(
                "INSERT INTO user_progress (user_id, quest_id, step_id, completed, status, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
            );
            return $gameDb->exec($stmt, [$userId, $questId, $stepId, $completed, $status]);
        }
    }
    
    public function getBookmarks($userId, $gameId) {
        $gameDb = Database::getGameInstance($gameId);
        $stmt = $gameDb->prepare(
            "SELECT id, resource_type, resource_id, display_name, bookmark_group, created_at 
             FROM user_bookmarks 
             WHERE user_id = ? 
             ORDER BY created_at DESC"
        );
        return $gameDb->fetchAll($stmt, [$userId]);
    }
    
    public function addBookmark($userId, $gameId, $resourceType, $resourceId, $displayName = null, $group = 'default') {
        $gameDb = Database::getGameInstance($gameId);
        
        // Check if bookmark exists
        $stmt = $gameDb->prepare(
            "SELECT 1 FROM user_bookmarks 
             WHERE user_id = ? AND resource_type = ? AND resource_id = ?"
        );
        if ($gameDb->fetchOne($stmt, [$userId, $resourceType, $resourceId])) {
            return false;
        }
        
        $stmt = $gameDb->prepare(
            "INSERT INTO user_bookmarks (user_id, resource_type, resource_id, display_name, bookmark_group, created_at) 
             VALUES (?, ?, ?, ?, ?, datetime('now'))"
        );
        return $gameDb->exec($stmt, [$userId, $resourceType, $resourceId, $displayName, $group]);
    }
    
    public function removeBookmark($userId, $bookmarkId) {
        $gameDb = Database::getGameInstance($gameId);
        $stmt = $gameDb->prepare(
            "DELETE FROM user_bookmarks 
             WHERE id = ? AND user_id = ?"
        );
        return $gameDb->exec($stmt, [$bookmarkId, $userId]);
    }
    
    /**
     * Check if a user has access to a specific game
     * 
     * @param int $userId User ID
     * @param string $gameId Game identifier
     * @return bool True if user has access, false otherwise
     */
    public function checkGameAccess($userId, $gameId) {
        // Check if user has a subscription
        $stmt = $this->db->prepare(
            "SELECT subscription_status FROM users WHERE id = ?"
        );
        $user = $this->db->fetchOne($stmt, [$userId]);
        
        if (!$user) {
            return false;
        }
        
        // If user has active subscription, they have access to all games
        if ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'trialing') {
            return true;
        }
        
        // Check for individual game purchase
        $stmt = $this->db->prepare(
            "SELECT 1 FROM purchases 
             WHERE user_id = ? AND game_id = ? AND status = 'completed'"
        );
        $purchase = $this->db->fetchOne($stmt, [$userId, $gameId]);
        
        return $purchase ? true : false;
    }
}
