<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';

use App\Utils\Response;
use App\Models\User;

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// This endpoint is only accessible via GET
if ($method !== 'GET') {
    Response::error('Method not allowed', 405);
    exit;
}

// Verify authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token || !\App\Utils\Auth::validateToken($token)) {
    Response::error('Invalid or expired token', 401);
    exit;
}

// Get user ID from token
$userId = \App\Utils\Auth::getUserIdFromToken($token);

// Get user data
$user = new User();
$userData = $user->getById($userId);

if (!$userData) {
    Response::error('User not found', 404);
    exit;
}

// Check if user has access to download
if (!in_array($userData['subscription_status'], ['trial', 'active', 'admin'])) {
    Response::error('Subscription required to download', 403);
    exit;
}

// Define different versions available
$action = $api_segments[1] ?? 'latest';
$platform = $_GET['platform'] ?? 'windows';

// Define the download files locations (these should be outside public web directory)
$downloadPaths = [
    'windows' => [
        'latest' => BASE_PATH . '/downloads/FridayAI-Win-latest.zip',
        'beta' => BASE_PATH . '/downloads/FridayAI-Win-beta.zip',
    ],
    'mac' => [
        'latest' => BASE_PATH . '/downloads/FridayAI-Mac-latest.dmg',
        'beta' => BASE_PATH . '/downloads/FridayAI-Mac-beta.dmg',
    ],
    'linux' => [
        'latest' => BASE_PATH . '/downloads/FridayAI-Linux-latest.tar.gz',
        'beta' => BASE_PATH . '/downloads/FridayAI-Linux-beta.tar.gz',
    ]
];

// Check if platform is supported
if (!isset($downloadPaths[$platform])) {
    Response::error('Unsupported platform', 400);
    exit;
}

// Check if version exists
if (!isset($downloadPaths[$platform][$action])) {
    Response::error('Version not found', 404);
    exit;
}

$filePath = $downloadPaths[$platform][$action];

// Check if file exists
if (!file_exists($filePath)) {
    Response::error('Download file not available', 404);
    exit;
}

// Log the download
try {
    $db = new \App\Utils\Database();
    $db->query(
        "INSERT INTO downloads (user_id, platform, version, created_at) VALUES (?, ?, ?, ?)",
        [$userId, $platform, $action, date('Y-m-d H:i:s')]
    );
} catch (\Exception $e) {
    // Log error but continue with download
    error_log("Failed to log download: " . $e->getMessage());
}

// Get file information
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$fileType = mime_content_type($filePath);

// Prepare headers for file download
header('Content-Description: File Transfer');
header('Content-Type: ' . $fileType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Clear output buffer
ob_clean();
flush();

// Output file data
readfile($filePath);
exit; 