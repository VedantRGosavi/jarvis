<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Load configuration
require_once BASE_PATH . '/app/config/app.php';

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// API route handling
if (strpos($request_uri, '/api/') === 0) {
    require BASE_PATH . '/app/api/index.php';
    exit;
}

// Static file handling
$file_path = BASE_PATH . '/public' . $request_uri;
if (file_exists($file_path) && is_file($file_path)) {
    // Determine mime type based on extension
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $mime_types = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico' => 'image/x-icon',
    ];
    
    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    }
    
    readfile($file_path);
    exit;
}

// Default to serving the SPA entry point
require_once BASE_PATH . '/public/index.html'; 