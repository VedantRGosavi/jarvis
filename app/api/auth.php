<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/models/User.php';

use App\Utils\Response;
use App\Models\User;

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate auth handler
$action = $api_segments[1] ?? '';

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }
        
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password required', 400);
            break;
        }
        
        $user = new User();
        $result = $user->authenticate($data['email'], $data['password']);
        
        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } else {
            Response::error('Invalid credentials', 401);
        }
        break;
        
    case 'register':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }
        
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            Response::error('Name, email and password required', 400);
            break;
        }
        
        $user = new User();
        $result = $user->register($data['name'], $data['email'], $data['password']);
        
        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } else {
            Response::error($result['message'], 400);
        }
        break;
        
    case 'verify':
        if ($method !== 'GET') {
            Response::error('Method not allowed', 405);
            break;
        }
        
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        if (!$token || !\App\Utils\Auth::validateToken($token)) {
            Response::error('Invalid token', 401);
            break;
        }
        
        $userId = \App\Utils\Auth::getUserIdFromToken($token);
        $user = new User();
        $userData = $user->getById($userId);
        
        if (!$userData) {
            Response::error('User not found', 404);
            break;
        }
        
        Response::success(['user' => $userData]);
        break;
        
    default:
        Response::error('Auth endpoint not found', 404);
}
