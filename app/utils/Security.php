<?php
namespace App\Utils;

/**
 * Security Utility Class
 * Provides security-related functionality like CSRF protection
 */
class Security {
    /**
     * Generate a CSRF token
     *
     * @return string The generated token
     */
    public static function generateCSRFToken(): string {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token
     *
     * @param string $token The token to validate
     * @return bool Whether the token is valid
     */
    public static function validateCSRFToken(?string $token): bool {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Refresh the CSRF token
     *
     * @return string The new token
     */
    public static function refreshCSRFToken(): string {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Sanitize input data
     *
     * @param mixed $data The data to sanitize
     * @return mixed The sanitized data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
            return $data;
        }

        // For strings, sanitize
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }

        // Return other data types as is
        return $data;
    }

    /**
     * Generate file checksum
     *
     * @param string $filePath Path to the file
     * @param string $algorithm Hash algorithm to use (default: sha256)
     * @return string The file checksum
     */
    public static function generateFileChecksum(string $filePath, string $algorithm = 'sha256'): string {
        if (!file_exists($filePath)) {
            return '';
        }

        return hash_file($algorithm, $filePath);
    }

    /**
     * Verify file checksum
     *
     * @param string $filePath Path to the file
     * @param string $expectedHash Expected hash value
     * @param string $algorithm Hash algorithm used (default: sha256)
     * @return bool Whether the checksum matches
     */
    public static function verifyFileChecksum(string $filePath, string $expectedHash, string $algorithm = 'sha256'): bool {
        if (!file_exists($filePath)) {
            return false;
        }

        $actualHash = hash_file($algorithm, $filePath);
        return hash_equals($expectedHash, $actualHash);
    }
}
