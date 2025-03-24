<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DownloadAnalytics.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Security.php';

use App\Utils\Security;

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Authorization');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Check for valid JWT token
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/^Bearer\s+(.*)$/', $authHeader, $matches)) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized - No valid token provided']);
    exit();
}

$token = $matches[1];

try {
    // Verify JWT token
    $user = new User($conn);
    $payload = $user->verifyToken($token);

    if (!$payload || !isset($payload->user_id)) {
        throw new Exception('Invalid token');
    }

    // Check if user has admin access
    $userData = $user->getUserById($payload->user_id);
    if (!$userData || $userData['subscription_status'] !== 'admin') {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Access denied - Admin privileges required']);
        exit();
    }

    // Initialize the DownloadAnalytics model
    $analytics = new DownloadAnalytics($conn);

    // Get action parameter (summary or records)
    $action = isset($_GET['action']) ? $_GET['action'] : 'summary';

    // Sanitize inputs
    $action = Security::sanitize($action);

    switch ($action) {
        case 'summary':
            // Fetch summary data
            $data = [
                'total_downloads' => $analytics->getTotalDownloads(),
                'successful_downloads' => $analytics->getSuccessfulDownloads(),
                'by_platform' => $analytics->getDownloadsByPlatform(),
                'by_version' => $analytics->getDownloadsByVersion(),
                'timeline' => $analytics->getDownloadsTimeline(30), // Last 30 days
                'total_download_size' => $analytics->getTotalDownloadSize(),
                'browser_stats' => $analytics->getBrowserStats()
            ];
            break;

        case 'records':
            // Get pagination and filter parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

            // Sanitize and prepare filters
            $filters = [];
            if (isset($_GET['platform']) && !empty($_GET['platform'])) {
                $filters['platform'] = Security::sanitize($_GET['platform']);
            }
            if (isset($_GET['version']) && !empty($_GET['version'])) {
                $filters['version'] = Security::sanitize($_GET['version']);
            }
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                $filters['start_date'] = Security::sanitize($_GET['start_date']);
            }
            if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                $filters['end_date'] = Security::sanitize($_GET['end_date']);
            }
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = Security::sanitize($_GET['status']);
            }

            $data = $analytics->getDownloadRecords($filters, $page, $perPage);
            break;

        case 'user_stats':
            // Get user-specific download stats
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

            if (!$userId) {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'User ID is required']);
                exit();
            }

            $data = $analytics->getUserDownloadStats($userId);
            break;

        default:
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid action parameter']);
            exit();
    }

    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]);
    exit();
}
