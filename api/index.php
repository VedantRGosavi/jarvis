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
if (file_exists(BASE_PATH . '/app/config/app.php')) {
    require_once BASE_PATH . '/app/config/app.php';
}

// API route handling
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

if (strpos($request_uri, '/api/') === 0) {
    if (file_exists(BASE_PATH . '/app/api/index.php')) {
        require BASE_PATH . '/app/api/index.php';
        exit;
    }
}

// If no API route matched, check for public files
include_once BASE_PATH . '/public/index.html';
