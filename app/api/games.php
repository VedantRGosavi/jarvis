<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/Game.php';
require_once BASE_PATH . '/app/models/User.php';

use App\Utils\Response;
use App\Utils\Auth;
use App\Models\Game;
use App\Models\User;

// Validate authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$isAuthenticated = false;
$userId = null;

if ($token && Auth::validateToken($token)) {
    $isAuthenticated = true;
    $userId = Auth::getUserIdFromToken($token);
} else {
    Response::error('Unauthorized', 401);
    exit;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$gameId = $api_segments[1] ?? '';
$resource = $api_segments[2] ?? '';
$resourceId = $api_segments[3] ?? '';

// Initialize models
$gameModel = new Game();
$userModel = new User();

// Validate game ID
if (!$gameModel->validateGame($gameId)) {
    Response::error('Invalid game ID', 400);
    exit;
}

// Check user access to requested game
$userAccess = $userModel->checkGameAccess($userId, $gameId);
if (!$userAccess) {
    Response::error('Access denied to this game data', 403);
    exit;
}

// Route to appropriate handler based on resource type
switch ($resource) {
    case 'quests':
        if ($resourceId) {
            // Get specific quest
            $questData = $gameModel->getQuestDetails($gameId, $resourceId);
            if ($questData) {
                Response::success(['quest' => $questData]);
            } else {
                Response::error('Quest not found', 404);
            }
        } else {
            // List all quests
            $category = $_GET['category'] ?? null;
            $searchTerm = $_GET['search'] ?? null;
            $quests = $gameModel->getQuests($gameId, $category, $searchTerm);
            Response::success(['quests' => $quests]);
        }
        break;
        
    case 'items':
        if ($resourceId) {
            // Get specific item
            $itemData = $gameModel->getItemDetails($gameId, $resourceId);
            if ($itemData) {
                Response::success(['item' => $itemData]);
            } else {
                Response::error('Item not found', 404);
            }
        } else {
            // List all items
            $category = $_GET['category'] ?? null;
            $searchTerm = $_GET['search'] ?? null;
            $items = $gameModel->getItems($gameId, $category, $searchTerm);
            Response::success(['items' => $items]);
        }
        break;
        
    case 'locations':
        if ($resourceId) {
            // Get specific location
            $locationData = $gameModel->getLocationDetails($gameId, $resourceId);
            if ($locationData) {
                Response::success(['location' => $locationData]);
            } else {
                Response::error('Location not found', 404);
            }
        } else {
            // List all locations
            $category = $_GET['category'] ?? null;
            $searchTerm = $_GET['search'] ?? null;
            $locations = $gameModel->getLocations($gameId, $category, $searchTerm);
            Response::success(['locations' => $locations]);
        }
        break;
        
    case 'npcs':
        if ($resourceId) {
            // Get specific NPC
            $npcData = $gameModel->getNpcDetails($gameId, $resourceId);
            if ($npcData) {
                Response::success(['npc' => $npcData]);
            } else {
                Response::error('NPC not found', 404);
            }
        } else {
            // List all NPCs
            $category = $_GET['category'] ?? null;
            $searchTerm = $_GET['search'] ?? null;
            $npcs = $gameModel->getNpcs($gameId, $category, $searchTerm);
            Response::success(['npcs' => $npcs]);
        }
        break;
        
    case 'search':
        // Search across game data
        $query = $_GET['q'] ?? '';
        if (!$query) {
            Response::error('Search query required', 400);
            break;
        }
        
        $types = isset($_GET['types']) ? explode(',', $_GET['types']) : null;
        $results = $gameModel->search($gameId, $query, $types);
        Response::success(['results' => $results]);
        break;
        
    case 'categories':
        // Get categories for a specific type
        $type = $_GET['type'] ?? null;
        if (!$type || !in_array($type, $gameModel->allowedTypes)) {
            Response::error('Valid type required (quests, items, locations, npcs)', 400);
            break;
        }
        
        $categories = $gameModel->getCategories($gameId, $type);
        Response::success(['categories' => $categories]);
        break;
        
    default:
        // Get game overview data
        $gameData = [
            'id' => $gameId,
            'name' => $gameId === 'elden_ring' ? 'Elden Ring' : "Baldur's Gate 3",
        ];
        Response::success(['game' => $gameData]);
} 