<?php
require_once BASE_PATH . '/app/utils/Response.php';
require_once BASE_PATH . '/app/utils/Auth.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/utils/Security.php';
require_once BASE_PATH . '/app/models/DownloadAnalytics.php';
require_once BASE_PATH . '/app/utils/S3Storage.php';

use App\Utils\Response;
use App\Models\User;
use App\Utils\Security;
use App\Utils\S3Storage;

// Start output buffering for clean response
ob_start();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// This endpoint is only accessible via GET
if ($method !== 'GET') {
    Response::error('Method not allowed', 405);
    exit;
}

// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');
header('Content-Security-Policy: default-src \'self\'');

// Add additional security headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; frame-ancestors \'none\'');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

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

// Connect to the database for rate limiting and analytics
try {
    $db = new \App\Utils\Database();
    $conn = $db->getConnection();
} catch (\Exception $e) {
    // Log database connection error but continue - don't block download for DB errors
    error_log("Database connection error: " . $e->getMessage());
    // Fallback to allowing the download
    $conn = null;
}

// Enhanced rate limiting - global rate limit for all users combined
if ($conn) {
    try {
        // Check global rate limit first (prevent DDoS)
        $globalDownloads = $db->query(
            "SELECT COUNT(*) as count FROM downloads WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
        )->fetch(\PDO::FETCH_ASSOC);

        if ($globalDownloads && $globalDownloads['count'] > 60) {
            Response::error('Server is currently busy. Please try again in a few minutes.', 429);
            exit;
        }

        // User-specific rate limit
        $recentDownloads = $db->query(
            "SELECT COUNT(*) as count FROM downloads WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($recentDownloads && $recentDownloads['count'] > 10) {
            Response::error('Download rate limit exceeded. Please try again later.', 429);
            exit;
        }

        // Additional check for concurrent downloads
        $ongoingDownloads = $db->query(
            "SELECT COUNT(*) as count FROM downloads WHERE user_id = ? AND download_status = 'in_progress' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($ongoingDownloads && $ongoingDownloads['count'] > 2) {
            Response::error('Too many concurrent downloads. Please wait for your current downloads to complete.', 429);
            exit;
        }
    } catch (\Exception $e) {
        // Log error but continue - don't block download for logging errors
        error_log("Rate limiting check failed: " . $e->getMessage());
    }
}

// Validate user agent to prevent automated download tools
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (empty($userAgent) || preg_match('/bot|crawl|spider|wget|curl|script/i', $userAgent)) {
    Response::error('Invalid client detected', 403);
    exit;
}

// Define different versions available
$action = $api_segments[1] ?? 'latest';
$platform = $_GET['platform'] ?? 'windows';

// Validate platform
$allowedPlatforms = ['windows', 'mac', 'linux'];
if (!in_array($platform, $allowedPlatforms)) {
    Response::error('Unsupported platform', 400);
    exit;
}

// Validate version
$allowedVersions = ['latest', 'beta'];
if (!in_array($action, $allowedVersions)) {
    Response::error('Invalid version', 400);
    exit;
}

// Define the download files locations (S3 paths)
$s3Keys = [
    'windows' => [
        'latest' => 'downloads/FridayAI-Win-latest.zip',
        'beta' => 'downloads/FridayAI-Win-beta.zip',
    ],
    'mac' => [
        'latest' => 'downloads/FridayAI-Mac-latest.dmg',
        'beta' => 'downloads/FridayAI-Mac-beta.dmg',
    ],
    'linux' => [
        'latest' => 'downloads/FridayAI-Linux-latest.tar.gz',
        'beta' => 'downloads/FridayAI-Linux-beta.tar.gz',
    ]
];

// Get the S3 key based on platform and version
$s3Key = $s3Keys[$platform][$action];

// Initialize S3 storage
$s3Storage = new S3Storage();

// Check if file exists in S3
if (!$s3Storage->fileExists($s3Key)) {
    Response::error('Download file not available', 404);
    exit;
}

// Get expected checksum from checksums file in S3
$expectedChecksum = '';
try {
    // Temporarily download checksums file
    $tempChecksumPath = tempnam(sys_get_temp_dir(), 'checksum');
    $s3Storage->downloadFile('downloads/checksums.txt', $tempChecksumPath);

    // Parse checksums file
    $checksums = file_get_contents($tempChecksumPath);
    $filename = basename($s3Key);
    if (preg_match('/([a-f0-9]{64})\s+' . preg_quote($filename) . '/', $checksums, $matches)) {
        $expectedChecksum = $matches[1];
    }

    // Clean up
    unlink($tempChecksumPath);
} catch (\Exception $e) {
    error_log("Error retrieving checksums: " . $e->getMessage());
    // Continue without checksum verification if checksums file can't be accessed
}

// Get a temporary pre-signed URL for the file
$tempUrl = $s3Storage->getPresignedUrl($s3Key, 300); // URL valid for 5 minutes

if (!$tempUrl) {
    Response::error('Unable to generate download URL', 500);
    exit;
}

// Get client info for analytics
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$referer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// Log the download start
if ($conn) {
    try {
        // Get file metadata from S3
        $result = $s3Storage->s3->headObject([
            'Bucket' => $s3Storage->bucket,
            'Key'    => $s3Key
        ]);
        $fileSize = $result['ContentLength'] ?? 0;

        $db->query(
            "INSERT INTO downloads (user_id, platform, version, file_size, created_at, ip_address, user_agent, referer, download_status)
             VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'in_progress')",
            [$userId, $platform, $action, $fileSize, $ipAddress, $userAgent, $referer]
        );
        $downloadId = $conn->lastInsertId();
    } catch (\Exception $e) {
        // Log error but continue - don't block download for logging errors
        error_log("Failed to log download: " . $e->getMessage());
        $downloadId = null;
    }
}

// Update download status to 'completed' after delivery
register_shutdown_function(function() use ($conn, $downloadId) {
    if ($conn && $downloadId) {
        try {
            $db = new \App\Utils\Database();
            $db->query(
                "UPDATE downloads SET download_status = 'completed', completed_at = NOW() WHERE id = ?",
                [$downloadId]
            );
        } catch (\Exception $e) {
            error_log("Failed to update download status: " . $e->getMessage());
        }
    }
});

// Redirect the user to the pre-signed URL
header('Location: ' . $tempUrl);
exit;

/**
 * Log the errors for troubleshooting
 */
function logDownloadError($message, $filePath, $userId) {
    error_log("[Download Error] User: {$userId}, File: {$filePath}, Error: {$message}");
}
