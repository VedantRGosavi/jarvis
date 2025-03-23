<?php
// API request handling
$api_path = str_replace('/api/', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$api_segments = explode('/', $api_path);

// Determine API endpoint
$endpoint = $api_segments[0] ?? '';

// Handle payment pages
if ($endpoint === 'payment') {
    $action = $api_segments[1] ?? '';
    switch ($action) {
        case 'success':
            require BASE_PATH . '/app/views/payment/success.php';
            exit;
        case 'cancel':
            require BASE_PATH . '/app/views/payment/cancel.php';
            exit;
    }
}

// Route to appropriate handler
switch ($endpoint) {
    case 'auth':
        require BASE_PATH . '/app/api/auth.php';
        break;
    case 'games':
        require BASE_PATH . '/app/api/games.php';
        break;
    case 'users':
        require BASE_PATH . '/app/api/users.php';
        break;
    case 'payments':
        require BASE_PATH . '/app/api/payments.php';
        break;
    case 'webhook':
        require BASE_PATH . '/app/api/webhook.php';
        break;
    case 'download':
        require BASE_PATH . '/app/api/download.php';
        break;
    default:
        // Handle 404 for API
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API endpoint not found']);
} 