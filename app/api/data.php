<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/Game.php';

use App\Utils\Response;
use App\Utils\Auth;
use App\Models\Game;

// Validate authentication for protected endpoints
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$isAuthenticated = false;
$userId = null;

if ($token && Auth::validateToken($token)) {
    $isAuthenticated = true;
    $userId = Auth::getUserIdFromToken($token);
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $api_segments[1] ?? '';
$gameId = $api_segments[2] ?? null;

if (!$gameId) {
    Response::error('Game ID required', 400);
    exit;
}

$gameModel = new Game();

// Route to appropriate handler
switch ($action) {
    case 'quests':
        if ($method === 'GET') {
            // Get quest list or specific quest details
            $questId = $_GET['quest_id'] ?? null;

            if ($questId) {
                $quest = $gameModel->getQuestDetails($gameId, $questId);
                if ($quest) {
                    Response::success(['quest' => $quest]);
                } else {
                    Response::error('Quest not found', 404);
                }
            } else {
                $category = $_GET['category'] ?? null;
                $searchTerm = $_GET['search'] ?? null;
                $quests = $gameModel->getQuests($gameId, $category, $searchTerm);
                Response::success(['quests' => $quests]);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'items':
        if ($method === 'GET') {
            // Get item list or specific item details
            $itemId = $_GET['item_id'] ?? null;

            if ($itemId) {
                $item = $gameModel->getItemDetails($gameId, $itemId);
                if ($item) {
                    Response::success(['item' => $item]);
                } else {
                    Response::error('Item not found', 404);
                }
            } else {
                $category = $_GET['category'] ?? null;
                $searchTerm = $_GET['search'] ?? null;
                $items = $gameModel->getItems($gameId, $category, $searchTerm);
                Response::success(['items' => $items]);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'locations':
        if ($method === 'GET') {
            // Get location list or specific location details
            $locationId = $_GET['location_id'] ?? null;

            if ($locationId) {
                $location = $gameModel->getLocationDetails($gameId, $locationId);
                if ($location) {
                    Response::success(['location' => $location]);
                } else {
                    Response::error('Location not found', 404);
                }
            } else {
                $category = $_GET['category'] ?? null;
                $searchTerm = $_GET['search'] ?? null;
                $locations = $gameModel->getLocations($gameId, $category, $searchTerm);
                Response::success(['locations' => $locations]);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'npcs':
        if ($method === 'GET') {
            // Get NPC list or specific NPC details
            $npcId = $_GET['npc_id'] ?? null;

            if ($npcId) {
                $npc = $gameModel->getNpcDetails($gameId, $npcId);
                if ($npc) {
                    Response::success(['npc' => $npc]);
                } else {
                    Response::error('NPC not found', 404);
                }
            } else {
                $category = $_GET['category'] ?? null;
                $searchTerm = $_GET['search'] ?? null;
                $npcs = $gameModel->getNpcs($gameId, $category, $searchTerm);
                Response::success(['npcs' => $npcs]);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'search':
        if ($method === 'GET') {
            // Global search across all game data
            $searchTerm = $_GET['q'] ?? null;
            if (!$searchTerm) {
                Response::error('Search term required', 400);
                break;
            }

            $types = $_GET['types'] ?? null;
            $typeArray = $types ? explode(',', $types) : null;

            $results = $gameModel->search($gameId, $searchTerm, $typeArray);
            Response::success(['results' => $results]);
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'categories':
        if ($method === 'GET') {
            // Get categories for a specific data type
            $type = $_GET['type'] ?? null;
            if (!$type) {
                Response::error('Data type required', 400);
                break;
            }

            $categories = $gameModel->getCategories($gameId, $type);
            Response::success(['categories' => $categories]);
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    default:
        Response::error('Data endpoint not found', 404);
}
