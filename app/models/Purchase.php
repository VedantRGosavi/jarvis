<?php

namespace App\Models;

use App\Utils\Database;

class Purchase {
    private $db;

    public function __construct() {
        $this->db = Database::getSystemInstance();
    }

    public function createPurchase($userId, $productId, $paymentIntentId, $status, $amount) {
        $stmt = $this->db->prepare(
            "INSERT INTO purchases
            (user_id, game_id, payment_intent_id, status, amount, created_at)
            VALUES (:user_id, :game_id, :payment_intent_id, :status, :amount, datetime('now'))"
        );

        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':game_id', $productId, SQLITE3_TEXT);
        $stmt->bindValue(':payment_intent_id', $paymentIntentId, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_INTEGER);

        return $stmt->execute();
    }

    public function updatePurchaseStatus($paymentIntentId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE purchases
            SET status = :status,
                completed_at = CASE WHEN :status = 'completed' THEN datetime('now') ELSE completed_at END
            WHERE payment_intent_id = :payment_intent_id"
        );

        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':payment_intent_id', $paymentIntentId, SQLITE3_TEXT);

        return $stmt->execute();
    }

    public function getPurchase($paymentIntentId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM purchases WHERE payment_intent_id = :payment_intent_id"
        );
        $stmt->bindValue(':payment_intent_id', $paymentIntentId, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function getUserPurchases($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM purchases WHERE user_id = :user_id ORDER BY created_at DESC"
        );
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $purchases = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $purchases[] = $row;
        }
        return $purchases;
    }

    public function getCompletedPurchases($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM purchases
            WHERE user_id = :user_id AND status = 'completed'
            ORDER BY completed_at DESC"
        );
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $purchases = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $purchases[] = $row;
        }
        return $purchases;
    }

    public function hasPurchasedGame($userId, $gameId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM purchases
            WHERE user_id = :user_id AND game_id = :game_id AND status = 'completed'"
        );
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':game_id', $gameId, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'] > 0;
    }
}
