<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();

    // Ensure all variables are available via getenv() too
    foreach ($_ENV as $key => $value) {
        putenv("$key=$value");
    }
}

// Load configuration
require_once BASE_PATH . '/app/config/app.php';

// Set proper content headers for all responses
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

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
        'html' => 'text/html; charset=UTF-8',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'txt' => 'text/plain',
    ];

    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    }

    // Set appropriate caching headers based on file type
    if (in_array($extension, ['js', 'css', 'png', 'jpg', 'jpeg', 'svg', 'ico'])) {
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
    } else {
        header('Cache-Control: no-cache, must-revalidate');
    }

    readfile($file_path);
    exit;
}

// Default to serving the SPA entry point - with proper HTML content type
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
readfile(BASE_PATH . '/public/index.html');
