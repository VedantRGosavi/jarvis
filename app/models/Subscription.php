<?php

namespace App\Models;

use App\Utils\Database;

class Subscription {
    private $db;

    public function __construct() {
        $this->db = Database::getSystemInstance();
    }

    public function createSubscription($userId, $stripeSubscriptionId, $status, $currentPeriodEnd) {
        $stmt = $this->db->prepare(
            "INSERT INTO subscriptions 
            (user_id, stripe_subscription_id, status, current_period_end, created_at, updated_at) 
            VALUES (:user_id, :stripe_subscription_id, :status, :current_period_end, datetime('now'), datetime('now'))"
        );
        
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':stripe_subscription_id', $stripeSubscriptionId, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':current_period_end', $currentPeriodEnd, SQLITE3_TEXT);
        
        return $stmt->execute();
    }

    public function updateSubscriptionStatus($stripeSubscriptionId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions 
            SET status = :status, updated_at = datetime('now') 
            WHERE stripe_subscription_id = :stripe_subscription_id"
        );
        
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':stripe_subscription_id', $stripeSubscriptionId, SQLITE3_TEXT);
        
        return $stmt->execute();
    }

    public function getUserIdFromSubscription($stripeSubscriptionId) {
        $stmt = $this->db->prepare(
            "SELECT user_id FROM subscriptions WHERE stripe_subscription_id = :stripe_subscription_id"
        );
        $stmt->bindValue(':stripe_subscription_id', $stripeSubscriptionId, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['user_id'] : null;
    }

    public function getSubscription($stripeSubscriptionId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions WHERE stripe_subscription_id = :stripe_subscription_id"
        );
        $stmt->bindValue(':stripe_subscription_id', $stripeSubscriptionId, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function getActiveSubscriptionByUserId($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions 
            WHERE user_id = :user_id AND status IN ('active', 'trialing') 
            ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function cancelSubscription($stripeSubscriptionId) {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions 
            SET status = 'cancelled', updated_at = datetime('now') 
            WHERE stripe_subscription_id = :stripe_subscription_id"
        );
        $stmt->bindValue(':stripe_subscription_id', $stripeSubscriptionId, SQLITE3_TEXT);
        return $stmt->execute();
    }
} 