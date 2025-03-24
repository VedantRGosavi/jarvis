<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';

use App\Utils\Response;
use App\Utils\Auth;
use App\Models\User;

// Validate authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token || !Auth::validateToken($token)) {
    Response::error('Unauthorized', 401);
    exit;
}

// Get authenticated user
$userId = Auth::getUserIdFromToken($token);
$userModel = new User();
$user = $userModel->getById($userId);

if (!$user) {
    Response::error('User not found', 404);
    exit;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $api_segments[1] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// Route to appropriate handler
switch ($action) {
    case 'settings':
        if ($method === 'GET') {
            // Get user settings
            $settings = $userModel->getSettings($userId);
            Response::success(['settings' => $settings]);
        } elseif ($method === 'POST') {
            // Update user settings
            if (!isset($data['settings']) || !is_array($data['settings'])) {
                Response::error('Invalid settings data', 400);
                break;
            }

            $result = $userModel->updateSettings($userId, $data['settings']);
            if ($result) {
                Response::success(['message' => 'Settings updated']);
            } else {
                Response::error('Failed to update settings', 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'progress':
        if ($method === 'GET') {
            // Get user progress for a specific game
            $gameId = $_GET['game_id'] ?? null;
            if (!$gameId) {
                Response::error('Game ID required', 400);
                break;
            }

            $progress = $userModel->getGameProgress($userId, $gameId);
            Response::success(['progress' => $progress]);
        } elseif ($method === 'POST') {
            // Update user progress
            if (!isset($data['game_id']) || !isset($data['quest_id'])) {
                Response::error('Game ID and Quest ID required', 400);
                break;
            }

            $result = $userModel->updateGameProgress(
                $userId,
                $data['game_id'],
                $data['quest_id'],
                $data['step_id'] ?? null,
                $data['completed'] ?? 0,
                $data['status'] ?? 'in_progress'
            );

            if ($result) {
                Response::success(['message' => 'Progress updated']);
            } else {
                Response::error('Failed to update progress', 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    case 'bookmarks':
        if ($method === 'GET') {
            // Get user bookmarks for a specific game
            $gameId = $_GET['game_id'] ?? null;
            if (!$gameId) {
                Response::error('Game ID required', 400);
                break;
            }

            $bookmarks = $userModel->getBookmarks($userId, $gameId);
            Response::success(['bookmarks' => $bookmarks]);
        } elseif ($method === 'POST') {
            // Add bookmark
            if (!isset($data['game_id']) || !isset($data['resource_type']) || !isset($data['resource_id'])) {
                Response::error('Game ID, resource type and resource ID required', 400);
                break;
            }

            $result = $userModel->addBookmark(
                $userId,
                $data['game_id'],
                $data['resource_type'],
                $data['resource_id'],
                $data['display_name'] ?? null,
                $data['group'] ?? 'default'
            );

            if ($result) {
                Response::success(['message' => 'Bookmark added']);
            } else {
                Response::error('Failed to add bookmark', 500);
            }
        } elseif ($method === 'DELETE') {
            // Remove bookmark
            if (!isset($data['bookmark_id'])) {
                Response::error('Bookmark ID required', 400);
                break;
            }

            $result = $userModel->removeBookmark($userId, $data['bookmark_id']);
            if ($result) {
                Response::success(['message' => 'Bookmark removed']);
            } else {
                Response::error('Failed to remove bookmark', 500);
            }
        } else {
            Response::error('Method not allowed', 405);
        }
        break;

    default:
        Response::error('User endpoint not found', 404);
}
