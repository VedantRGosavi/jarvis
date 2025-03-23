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
    
    public function exec($sql) {
        return $this->db->exec($sql);
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
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $param => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($param, $value, $type);
        }
        
        $result = $stmt->execute();
        
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $param => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($param, $value, $type);
        }
        
        $result = $stmt->execute();
        
        return $result->fetchArray(SQLITE3_ASSOC);
    }
}
