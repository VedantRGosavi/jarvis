<?php
/**
 * Game API Endpoints
 * RESTful API for serving game data to the frontend overlay
 */

// Set base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Include required files
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/utils/Database.php';
require_once BASE_PATH . '/app/utils/Response.php';

use App\Utils\Database;
use App\Utils\Response;

// Parse URL to determine endpoint
$request = parse_url($_SERVER['REQUEST_URI']);
$path = $request['path'];
$parts = explode('/', trim($path, '/'));

// Remove 'api' and 'games' from path parts
if (count($parts) > 1 && $parts[0] === 'api' && $parts[1] === 'games') {
    array_shift($parts); // Remove 'api'
    array_shift($parts); // Remove 'games'
} else {
    Response::json(['error' => 'Invalid API endpoint'], 404);
    exit;
}

// Get game identifier
if (empty($parts[0])) {
    Response::json(['error' => 'Game identifier is required'], 400);
    exit;
}

$game = $parts[0];
$allowedGames = ['elden_ring', 'baldurs_gate3'];

// Validate game identifier
if (!in_array($game, $allowedGames)) {
    Response::json(['error' => 'Invalid game identifier'], 400);
    exit;
}

// Remove game from path parts
array_shift($parts);

// Get resource type (locations, items, npcs, quests)
$resourceType = !empty($parts[0]) ? $parts[0] : null;
$allowedResources = ['locations', 'items', 'npcs', 'quests', 'search'];

// Validate resource type
if (!$resourceType || !in_array($resourceType, $allowedResources)) {
    Response::json(['error' => 'Invalid resource type'], 400);
    exit;
}

// Remove resource type from path parts
array_shift($parts);

// Get resource ID if provided
$resourceId = !empty($parts[0]) ? $parts[0] : null;

try {
    // Get database instance
    $db = Database::getGameInstance($game);
    
    // Process request based on resource type and HTTP method
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($db, $resourceType, $resourceId);
            break;
        
        default:
            Response::json(['error' => 'Method not allowed'], 405);
            break;
    }
} catch (Exception $e) {
    Response::json(['error' => $e->getMessage()], 500);
}

/**
 * Handle GET requests for game data
 * 
 * @param Database $db Database instance
 * @param string $resourceType Resource type (locations, items, npcs, quests, search)
 * @param string|null $resourceId Resource identifier
 */
function handleGetRequest($db, $resourceType, $resourceId) {
    // Parse query parameters
    $params = $_GET;
    
    if ($resourceType === 'search') {
        // Handle search requests
        handleSearch($db, $params);
        return;
    }
    
    // Get table name based on resource type
    $tableName = $resourceType;
    
    // If resource ID is provided, get specific resource
    if ($resourceId) {
        switch ($resourceType) {
            case 'quests':
                // For quests, also fetch quest steps
                $quest = getQuest($db, $resourceId);
                
                if (!$quest) {
                    Response::json(['error' => 'Quest not found'], 404);
                    return;
                }
                
                $quest['steps'] = getQuestSteps($db, $resourceId);
                Response::json($quest);
                return;
                
            case 'locations':
                // Get single location with any related data
                $location = getLocation($db, $resourceId, $params);
                
                if (!$location) {
                    Response::json(['error' => 'Location not found'], 404);
                    return;
                }
                
                Response::json($location);
                return;
                
            case 'items':
                // Get single item with any related data
                $item = getItem($db, $resourceId, $params);
                
                if (!$item) {
                    Response::json(['error' => 'Item not found'], 404);
                    return;
                }
                
                Response::json($item);
                return;
                
            case 'npcs':
                // Get single NPC with any related data
                $npc = getNpc($db, $resourceId, $params);
                
                if (!$npc) {
                    Response::json(['error' => 'NPC not found'], 404);
                    return;
                }
                
                Response::json($npc);
                return;
        }
    } else {
        // Get list of resources based on type
        switch ($resourceType) {
            case 'quests':
                $quests = getQuests($db, $params);
                Response::json($quests);
                return;
                
            case 'locations':
                $locations = getLocations($db, $params);
                Response::json($locations);
                return;
                
            case 'items':
                $items = getItems($db, $params);
                Response::json($items);
                return;
                
            case 'npcs':
                $npcs = getNpcs($db, $params);
                Response::json($npcs);
                return;
        }
    }
    
    // If we got here, the resource wasn't handled
    Response::json(['error' => 'Resource not found'], 404);
}

/**
 * Handle search requests
 * 
 * @param Database $db Database instance
 * @param array $params Query parameters
 */
function handleSearch($db, $params) {
    // Get search query
    $query = isset($params['q']) ? $params['q'] : '';
    
    if (empty($query)) {
        Response::json(['error' => 'Search query is required'], 400);
        return;
    }
    
    // Get content type filter if provided
    $contentType = isset($params['type']) ? $params['type'] : null;
    $allowedTypes = ['quest', 'location', 'item', 'npc'];
    
    if ($contentType && !in_array($contentType, $allowedTypes)) {
        Response::json(['error' => 'Invalid content type'], 400);
        return;
    }
    
    // Prepare search query
    $sql = "SELECT * FROM search_index WHERE search_index MATCH ?";
    $queryParams = [$query];
    
    // Add content type filter if provided
    if ($contentType) {
        $sql .= " AND content_type = ?";
        $queryParams[] = $contentType;
    }
    
    // Add limit
    $limit = isset($params['limit']) ? (int) $params['limit'] : 20;
    $sql .= " LIMIT ?";
    $queryParams[] = $limit;
    
    try {
        $results = $db->fetchAll($sql, $queryParams);
        Response::json($results);
    } catch (Exception $e) {
        Response::json(['error' => 'Search failed: ' . $e->getMessage()], 500);
    }
}

/**
 * Get list of quests
 * 
 * @param Database $db Database instance
 * @param array $params Query parameters
 * @return array List of quests
 */
function getQuests($db, $params) {
    // Build SQL query
    $sql = "SELECT * FROM quests";
    $queryParams = [];
    
    // Add filters based on params
    $whereConditions = [];
    
    // Filter by main story
    if (isset($params['is_main_story'])) {
        $whereConditions[] = "is_main_story = ?";
        $queryParams[] = (int) $params['is_main_story'];
    }
    
    // Filter by difficulty
    if (isset($params['difficulty'])) {
        $whereConditions[] = "difficulty = ?";
        $queryParams[] = $params['difficulty'];
    }
    
    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY name ASC";
    
    try {
        return $db->fetchAll($sql, $queryParams);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a specific quest by ID
 * 
 * @param Database $db Database instance
 * @param string $questId Quest identifier
 * @return array|null Quest data or null if not found
 */
function getQuest($db, $questId) {
    $sql = "SELECT * FROM quests WHERE quest_id = ?";
    
    try {
        return $db->fetchOne($sql, [$questId]);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get steps for a specific quest
 * 
 * @param Database $db Database instance
 * @param string $questId Quest identifier
 * @return array List of quest steps
 */
function getQuestSteps($db, $questId) {
    $sql = "SELECT * FROM quest_steps WHERE quest_id = ? ORDER BY step_number ASC";
    
    try {
        return $db->fetchAll($sql, [$questId]);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get list of locations
 * 
 * @param Database $db Database instance
 * @param array $params Query parameters
 * @return array List of locations
 */
function getLocations($db, $params) {
    // Build SQL query
    $sql = "SELECT * FROM locations";
    $queryParams = [];
    
    // Add filters based on params
    $whereConditions = [];
    
    // Filter by region
    if (isset($params['region'])) {
        $whereConditions[] = "region = ?";
        $queryParams[] = $params['region'];
    }
    
    // Filter by parent location
    if (isset($params['parent_location_id'])) {
        $whereConditions[] = "parent_location_id = ?";
        $queryParams[] = $params['parent_location_id'];
    }
    
    // Filter by specific ID
    if (isset($params['id'])) {
        $whereConditions[] = "location_id = ?";
        $queryParams[] = $params['id'];
    }
    
    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY name ASC";
    
    try {
        return $db->fetchAll($sql, $queryParams);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a specific location by ID
 * 
 * @param Database $db Database instance
 * @param string $locationId Location identifier
 * @param array $params Query parameters
 * @return array|null Location data or null if not found
 */
function getLocation($db, $locationId, $params) {
    $sql = "SELECT * FROM locations WHERE location_id = ?";
    
    try {
        $location = $db->fetchOne($sql, [$locationId]);
        
        if (!$location) {
            return null;
        }
        
        // Include additional data if requested
        if (isset($params['include'])) {
            $includes = explode(',', $params['include']);
            
            // Include points of interest
            if (in_array('points_of_interest', $includes) && !empty($location['points_of_interest'])) {
                // Already included in the location data
            }
            
            // Include NPCs at this location
            if (in_array('npcs', $includes)) {
                $sql = "SELECT n.* FROM npcs n 
                        JOIN npc_locations nl ON n.npc_id = nl.npc_id 
                        WHERE nl.location_id = ?";
                $location['npcs'] = $db->fetchAll($sql, [$locationId]);
            }
            
            // Include parent location details
            if (in_array('parent', $includes) && !empty($location['parent_location_id'])) {
                $sql = "SELECT * FROM locations WHERE location_id = ?";
                $location['parent_location'] = $db->fetchOne($sql, [$location['parent_location_id']]);
            }
        }
        
        return $location;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get list of items
 * 
 * @param Database $db Database instance
 * @param array $params Query parameters
 * @return array List of items
 */
function getItems($db, $params) {
    // Build SQL query
    $sql = "SELECT * FROM items";
    $queryParams = [];
    
    // Add filters based on params
    $whereConditions = [];
    
    // Filter by type
    if (isset($params['type'])) {
        $whereConditions[] = "type = ?";
        $queryParams[] = $params['type'];
    }
    
    // Filter by subtype
    if (isset($params['subtype'])) {
        $whereConditions[] = "subtype = ?";
        $queryParams[] = $params['subtype'];
    }
    
    // Filter by rarity
    if (isset($params['rarity'])) {
        $whereConditions[] = "rarity = ?";
        $queryParams[] = $params['rarity'];
    }
    
    // Filter by quest related
    if (isset($params['quest_related'])) {
        $whereConditions[] = "quest_related = ?";
        $queryParams[] = (int) $params['quest_related'];
    }
    
    // Filter by specific ID
    if (isset($params['id'])) {
        $whereConditions[] = "item_id = ?";
        $queryParams[] = $params['id'];
    }
    
    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY name ASC";
    
    try {
        return $db->fetchAll($sql, $queryParams);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a specific item by ID
 * 
 * @param Database $db Database instance
 * @param string $itemId Item identifier
 * @param array $params Query parameters
 * @return array|null Item data or null if not found
 */
function getItem($db, $itemId, $params) {
    $sql = "SELECT * FROM items WHERE item_id = ?";
    
    try {
        $item = $db->fetchOne($sql, [$itemId]);
        
        if (!$item) {
            return null;
        }
        
        // Include additional data if requested
        if (isset($params['include'])) {
            $includes = explode(',', $params['include']);
            
            // Include related quests
            if (in_array('quests', $includes) && !empty($item['related_quests'])) {
                $relatedQuests = explode(',', $item['related_quests']);
                $placeholders = implode(',', array_fill(0, count($relatedQuests), '?'));
                
                $sql = "SELECT * FROM quests WHERE quest_id IN ($placeholders)";
                $item['quests'] = $db->fetchAll($sql, $relatedQuests);
            }
        }
        
        return $item;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get list of NPCs
 * 
 * @param Database $db Database instance
 * @param array $params Query parameters
 * @return array List of NPCs
 */
function getNpcs($db, $params) {
    // Build SQL query
    $sql = "SELECT * FROM npcs";
    $queryParams = [];
    
    // Add filters based on params
    $whereConditions = [];
    
    // Filter by role
    if (isset($params['role'])) {
        $whereConditions[] = "role = ?";
        $queryParams[] = $params['role'];
    }
    
    // Filter by faction
    if (isset($params['faction'])) {
        $whereConditions[] = "faction = ?";
        $queryParams[] = $params['faction'];
    }
    
    // Filter by hostility
    if (isset($params['is_hostile'])) {
        $whereConditions[] = "is_hostile = ?";
        $queryParams[] = (int) $params['is_hostile'];
    }
    
    // Filter by merchant
    if (isset($params['is_merchant'])) {
        $whereConditions[] = "is_merchant = ?";
        $queryParams[] = (int) $params['is_merchant'];
    }
    
    // Filter by location
    if (isset($params['location_id'])) {
        $sql = "SELECT n.* FROM npcs n JOIN npc_locations nl ON n.npc_id = nl.npc_id";
        $whereConditions[] = "nl.location_id = ?";
        $queryParams[] = $params['location_id'];
    }
    
    // Filter by specific ID
    if (isset($params['id'])) {
        $whereConditions[] = "npc_id = ?";
        $queryParams[] = $params['id'];
    }
    
    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY name ASC";
    
    try {
        return $db->fetchAll($sql, $queryParams);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a specific NPC by ID
 * 
 * @param Database $db Database instance
 * @param string $npcId NPC identifier
 * @param array $params Query parameters
 * @return array|null NPC data or null if not found
 */
function getNpc($db, $npcId, $params) {
    $sql = "SELECT * FROM npcs WHERE npc_id = ?";
    
    try {
        $npc = $db->fetchOne($sql, [$npcId]);
        
        if (!$npc) {
            return null;
        }
        
        // Include additional data if requested
        if (isset($params['include'])) {
            $includes = explode(',', $params['include']);
            
            // Include locations
            if (in_array('locations', $includes)) {
                $sql = "SELECT l.* FROM locations l 
                        JOIN npc_locations nl ON l.location_id = nl.location_id 
                        WHERE nl.npc_id = ?";
                $npc['locations'] = $db->fetchAll($sql, [$npcId]);
            }
            
            // Include quests
            if (in_array('quests', $includes) && !empty($npc['gives_quests'])) {
                $questIds = explode(',', $npc['gives_quests']);
                $placeholders = implode(',', array_fill(0, count($questIds), '?'));
                
                $sql = "SELECT * FROM quests WHERE quest_id IN ($placeholders)";
                $npc['quests'] = $db->fetchAll($sql, $questIds);
            }
        }
        
        return $npc;
    } catch (Exception $e) {
        return null;
    }
} 