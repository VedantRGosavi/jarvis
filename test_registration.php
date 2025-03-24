<?php
define('BASE_PATH', __DIR__);
require_once 'app/utils/Response.php';
require_once 'app/utils/Auth.php';
require_once 'app/utils/Database.php';
require_once 'app/models/User.php';

try {
    // Initialize Auth class
    \App\Utils\Auth::init();

    $user = new \App\Models\User();
    $result = $user->register('Test User', 'test' . time() . '@example.com', 'test123');

    echo "Registration result:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
