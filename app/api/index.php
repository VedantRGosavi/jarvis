<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';

use App\Utils\Auth;
use App\Utils\Response;

// Initialize Auth
Auth::init();

// Parse API path
$request_uri = $_SERVER['REQUEST_URI'];
$api_prefix = '/api/';
$api_path = substr($request_uri, strlen($api_prefix));
$api_segments = explode('/', trim($api_path, '/'));

if (empty($api_segments[0])) {
    Response::error('Invalid API endpoint', 404);
    exit;
}

// Route to appropriate API handler
$handler = $api_segments[0];
$handler_file = BASE_PATH . "/app/api/{$handler}.php";

if (file_exists($handler_file)) {
    require_once $handler_file;
} else {
    Response::error('API endpoint not found', 404);
}
