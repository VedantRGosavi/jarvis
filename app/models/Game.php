<?php
namespace App\Models;

use App\Utils\Database;

class Game {
    private $allowedGames = ['elden_ring', 'baldurs_gate3'];
    private $allowedTypes = ['quests', 'items', 'locations', 'npcs'];
    
    public function validateGame($gameId) {
        return in_array($gameId, $this->allowedGames);
    }
    
    private function getDb($gameId) {
        if (!$this->validateGame($gameId)) {
            throw new \Exception('Invalid game ID');
        }
        return Database::getGameInstance($gameId);
    }
    
    public function getQuests($gameId, $category = null, $searchTerm = null) {
        $db = $this->getDb($gameId);
        $params = [];
        $sql = "SELECT id, title, description, category, level, prerequisites FROM quests WHERE 1=1";
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($searchTerm) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY level ASC, title ASC";
        $stmt = $db->prepare($sql);
        return $db->fetchAll($stmt, $params);
    }
    
    public function getQuestDetails($gameId, $questId) {
        $db = $this->getDb($gameId);
        
        // Get quest basic info
        $stmt = $db->prepare(
            "SELECT q.*, GROUP_CONCAT(DISTINCT r.item_id) as rewards, GROUP_CONCAT(DISTINCT p.quest_id) as prerequisites 
             FROM quests q 
             LEFT JOIN quest_rewards r ON q.id = r.quest_id 
             LEFT JOIN quest_prerequisites p ON q.id = p.quest_id 
             WHERE q.id = ? 
             GROUP BY q.id"
        );
        $quest = $db->fetchOne($stmt, [$questId]);
        
        if (!$quest) {
            return null;
        }
        
        // Get quest steps
        $stmt = $db->prepare(
            "SELECT id, description, location_id, npc_id, optional 
             FROM quest_steps 
             WHERE quest_id = ? 
             ORDER BY step_order ASC"
        );
        $quest['steps'] = $db->fetchAll($stmt, [$questId]);
        
        // Convert string lists to arrays
        $quest['rewards'] = $quest['rewards'] ? explode(',', $quest['rewards']) : [];
        $quest['prerequisites'] = $quest['prerequisites'] ? explode(',', $quest['prerequisites']) : [];
        
        return $quest;
    }
    
    public function getItems($gameId, $category = null, $searchTerm = null) {
        $db = $this->getDb($gameId);
        $params = [];
        $sql = "SELECT id, name, description, category, rarity, level FROM items WHERE 1=1";
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($searchTerm) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY level ASC, name ASC";
        $stmt = $db->prepare($sql);
        return $db->fetchAll($stmt, $params);
    }
    
    public function getItemDetails($gameId, $itemId) {
        $db = $this->getDb($gameId);
        
        // Get item basic info
        $stmt = $db->prepare(
            "SELECT i.*, GROUP_CONCAT(DISTINCT l.location_id) as locations 
             FROM items i 
             LEFT JOIN item_locations l ON i.id = l.item_id 
             WHERE i.id = ? 
             GROUP BY i.id"
        );
        $item = $db->fetchOne($stmt, [$itemId]);
        
        if (!$item) {
            return null;
        }
        
        // Get item stats if they exist
        $stmt = $db->prepare(
            "SELECT stat_name, value 
             FROM item_stats 
             WHERE item_id = ?"
        );
        $item['stats'] = $db->fetchAll($stmt, [$itemId]);
        
        // Convert string lists to arrays
        $item['locations'] = $item['locations'] ? explode(',', $item['locations']) : [];
        
        return $item;
    }
    
    public function getLocations($gameId, $category = null, $searchTerm = null) {
        $db = $this->getDb($gameId);
        $params = [];
        $sql = "SELECT id, name, description, category, region, level FROM locations WHERE 1=1";
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($searchTerm) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY region ASC, name ASC";
        $stmt = $db->prepare($sql);
        return $db->fetchAll($stmt, $params);
    }
    
    public function getLocationDetails($gameId, $locationId) {
        $db = $this->getDb($gameId);
        
        // Get location basic info
        $stmt = $db->prepare(
            "SELECT l.*, GROUP_CONCAT(DISTINCT n.npc_id) as npcs 
             FROM locations l 
             LEFT JOIN npc_locations n ON l.id = n.location_id 
             WHERE l.id = ? 
             GROUP BY l.id"
        );
        $location = $db->fetchOne($stmt, [$locationId]);
        
        if (!$location) {
            return null;
        }
        
        // Get connected locations
        $stmt = $db->prepare(
            "SELECT l.id, l.name, c.direction 
             FROM location_connections c 
             JOIN locations l ON c.connected_location_id = l.id 
             WHERE c.location_id = ?"
        );
        $location['connections'] = $db->fetchAll($stmt, [$locationId]);
        
        // Convert string lists to arrays
        $location['npcs'] = $location['npcs'] ? explode(',', $location['npcs']) : [];
        
        return $location;
    }
    
    public function getNpcs($gameId, $category = null, $searchTerm = null) {
        $db = $this->getDb($gameId);
        $params = [];
        $sql = "SELECT id, name, description, category, faction FROM npcs WHERE 1=1";
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($searchTerm) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY name ASC";
        $stmt = $db->prepare($sql);
        return $db->fetchAll($stmt, $params);
    }
    
    public function getNpcDetails($gameId, $npcId) {
        $db = $this->getDb($gameId);
        
        // Get NPC basic info
        $stmt = $db->prepare(
            "SELECT n.*, GROUP_CONCAT(DISTINCT l.location_id) as locations 
             FROM npcs n 
             LEFT JOIN npc_locations l ON n.id = l.npc_id 
             WHERE n.id = ? 
             GROUP BY n.id"
        );
        $npc = $db->fetchOne($stmt, [$npcId]);
        
        if (!$npc) {
            return null;
        }
        
        // Get NPC quests
        $stmt = $db->prepare(
            "SELECT q.id, q.title, q.level 
             FROM quests q 
             JOIN quest_steps s ON q.id = s.quest_id 
             WHERE s.npc_id = ? 
             GROUP BY q.id 
             ORDER BY q.level ASC"
        );
        $npc['quests'] = $db->fetchAll($stmt, [$npcId]);
        
        // Convert string lists to arrays
        $npc['locations'] = $npc['locations'] ? explode(',', $npc['locations']) : [];
        
        return $npc;
    }
    
    public function search($gameId, $searchTerm, $types = null) {
        $db = $this->getDb($gameId);
        $results = [];
        
        // Validate and filter types
        if ($types) {
            $types = array_intersect($types, $this->allowedTypes);
        } else {
            $types = $this->allowedTypes;
        }
        
        foreach ($types as $type) {
            $params = ["%$searchTerm%", "%$searchTerm%"];
            
            switch ($type) {
                case 'quests':
                    $stmt = $db->prepare(
                        "SELECT 'quest' as type, id, title as name, description, category, level 
                         FROM quests 
                         WHERE title LIKE ? OR description LIKE ? 
                         ORDER BY level ASC 
                         LIMIT 10"
                    );
                    break;
                    
                case 'items':
                    $stmt = $db->prepare(
                        "SELECT 'item' as type, id, name, description, category, rarity, level 
                         FROM items 
                         WHERE name LIKE ? OR description LIKE ? 
                         ORDER BY level ASC 
                         LIMIT 10"
                    );
                    break;
                    
                case 'locations':
                    $stmt = $db->prepare(
                        "SELECT 'location' as type, id, name, description, category, region, level 
                         FROM locations 
                         WHERE name LIKE ? OR description LIKE ? 
                         ORDER BY level ASC 
                         LIMIT 10"
                    );
                    break;
                    
                case 'npcs':
                    $stmt = $db->prepare(
                        "SELECT 'npc' as type, id, name, description, category, faction 
                         FROM npcs 
                         WHERE name LIKE ? OR description LIKE ? 
                         ORDER BY name ASC 
                         LIMIT 10"
                    );
                    break;
            }
            
            $results[$type] = $db->fetchAll($stmt, $params);
        }
        
        return $results;
    }
    
    public function getCategories($gameId, $type) {
        if (!in_array($type, $this->allowedTypes)) {
            throw new \Exception('Invalid data type');
        }
        
        $db = $this->getDb($gameId);
        $tableName = $type;
        
        $stmt = $db->prepare(
            "SELECT DISTINCT category 
             FROM $tableName 
             WHERE category IS NOT NULL 
             ORDER BY category ASC"
        );
        
        $categories = $db->fetchAll($stmt);
        return array_column($categories, 'category');
    }
} 