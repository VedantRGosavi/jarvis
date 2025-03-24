<?php
namespace App\Utils;

use SQLite3;
use Exception;

class Database {
    private static $systemInstance = null;
    private static $gameInstances = [];
    private $db = null;

    // Private constructor for singleton pattern
    private function __construct($databasePath) {
        $this->db = new SQLite3($databasePath);
        $this->db->enableExceptions(true);
    }

    // Get system database instance
    public static function getSystemInstance() {
        if (self::$systemInstance === null) {
            self::$systemInstance = new self(BASE_PATH . '/data/system.sqlite');
        }
        return self::$systemInstance;
    }

    // Get game database instance
    public static function getGameInstance($game) {
        // Validate game ID to prevent directory traversal
        if (!in_array($game, ['elden_ring', 'baldurs_gate3'])) {
            throw new Exception("Invalid game identifier");
        }

        if (!isset(self::$gameInstances[$game])) {
            self::$gameInstances[$game] = new self(BASE_PATH . "/data/game_data/{$game}.sqlite");
        }
        return self::$gameInstances[$game];
    }

    // Query execution methods
    public function query($sql) {
        return $this->db->query($sql);
    }

    public function prepare($sql) {
        return $this->db->prepare($sql);
    }

    public function exec($sql, $params = []) {
        if (is_string($sql)) {
            return $this->db->exec($sql);
        }

        // If it's a prepared statement
        $stmt = $sql;

        if (!$stmt) {
            throw new \Exception("Invalid statement");
        }

        // Bind parameters by index (1-based)
        foreach ($params as $index => $value) {
            $paramIndex = $index + 1;
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($paramIndex, $value, $type);
        }

        $result = $stmt->execute();
        if (!$result) {
            throw new \Exception("Unable to execute statement: " . $this->db->lastErrorMsg());
        }
        return $result;
    }

    public function lastErrorMsg() {
        return $this->db->lastErrorMsg();
    }

    // Execute a prepared statement with parameters
    public function execPrepared($stmt, $params = []) {
        if (is_string($stmt)) {
            $stmt = $this->db->prepare($stmt);
        }

        // Bind parameters
        if (is_array($params)) {
            foreach ($params as $index => $value) {
                $paramIndex = is_int($index) ? $index + 1 : $index;
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($paramIndex, $value, $type);
            }
        }

        // Execute statement
        $result = $stmt->execute();
        return $result;
    }

    // Fetch methods
    public function fetchAll($sql, $params = []) {
        $stmt = is_string($sql) ? $this->db->prepare($sql) : $sql;

        if (!$stmt) {
            throw new \Exception("Failed to prepare statement");
        }

        // Bind parameters
        foreach ($params as $index => $value) {
            $paramIndex = $index + 1;
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($paramIndex, $value, $type);
        }

        $result = $stmt->execute();

        if (!$result) {
            throw new \Exception("Failed to execute statement");
        }

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function fetchOne($sql, $params = []) {
        $stmt = is_string($sql) ? $this->db->prepare($sql) : $sql;

        if (!$stmt) {
            throw new \Exception("Failed to prepare statement");
        }

        // Bind parameters
        foreach ($params as $index => $value) {
            $paramIndex = $index + 1;
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($paramIndex, $value, $type);
        }

        $result = $stmt->execute();

        if (!$result) {
            throw new \Exception("Failed to execute statement");
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        // Return false if no rows found instead of empty array
        return $row === false ? false : $row;
    }

    /**
     * Get the ID of the last inserted row
     *
     * @return int The last insert ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
}
