<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Forward to the actual app API handler
require_once BASE_PATH . '/app/api/data.php';
