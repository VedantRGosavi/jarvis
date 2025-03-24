<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/utils/Security.php';

use App\Utils\Response;
use App\Models\User;
use App\Utils\Security;

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// This endpoint is only accessible via GET
if ($method !== 'GET') {
    Response::error('Method not allowed', 405);
    exit;
}

// Check for CSRF token if coming from download page
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/download.html') !== false) {
    $csrfToken = $_GET['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($csrfToken)) {
        Response::error('Invalid CSRF token', 403);
        exit;
    }
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

// Rate limiting - check if user has exceeded download limits
try {
    $db = new \App\Utils\Database();
    $recentDownloads = $db->query(
        "SELECT COUNT(*) as count FROM downloads WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$userId]
    )->fetch(\PDO::FETCH_ASSOC);

    if ($recentDownloads && $recentDownloads['count'] > 10) {
        Response::error('Download rate limit exceeded. Please try again later.', 429);
        exit;
    }
} catch (\Exception $e) {
    // Log error but continue - don't block download for logging errors
    error_log("Rate limiting check failed: " . $e->getMessage());
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

// File checksums for verification
$fileChecksums = [
    'windows' => [
        'latest' => 'sha256:' . hash_file('sha256', $downloadPaths['windows']['latest']),
        'beta' => 'sha256:' . hash_file('sha256', $downloadPaths['windows']['beta']),
    ],
    'mac' => [
        'latest' => 'sha256:' . hash_file('sha256', $downloadPaths['mac']['latest']),
        'beta' => 'sha256:' . hash_file('sha256', $downloadPaths['mac']['beta']),
    ],
    'linux' => [
        'latest' => 'sha256:' . hash_file('sha256', $downloadPaths['linux']['latest']),
        'beta' => 'sha256:' . hash_file('sha256', $downloadPaths['linux']['beta']),
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

// Get client info for analytics
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Log the download
try {
    $db = new \App\Utils\Database();
    $db->query(
        "INSERT INTO downloads (user_id, platform, version, created_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $platform, $action, date('Y-m-d H:i:s'), $ipAddress, $userAgent]
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
header('Content-MD5: ' . base64_encode(md5_file($filePath, true))); // Add content verification
header('X-Content-Type-Options: nosniff');
header('X-Checksum: ' . $fileChecksums[$platform][$action]);
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Clear output buffer
ob_clean();
flush();

// Output file data
readfile($filePath);
exit;
