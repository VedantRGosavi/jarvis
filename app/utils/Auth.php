<?php
namespace App\Utils;

class Auth {
    private static $secretKey;
    private static $tokenExpiration = 86400; // 24 hours
    
    public static function init() {
        self::$secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-key-change-in-production';
    }
    
    public static function generateToken($userId) {
        $issuedAt = time();
        $expirationTime = $issuedAt + self::$tokenExpiration;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId
        ];
        
        return self::encodeToken($payload);
    }
    
    public static function validateToken($token) {
        try {
            $payload = self::decodeToken($token);
            
            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function getUserIdFromToken($token) {
        try {
            $payload = self::decodeToken($token);
            return $payload['user_id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private static function encodeToken($payload) {
        // Header
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $header = self::base64UrlEncode(json_encode($header));
        
        // Payload
        $payload = self::base64UrlEncode(json_encode($payload));
        
        // Signature
        $signature = hash_hmac('sha256', "$header.$payload", self::$secretKey, true);
        $signature = self::base64UrlEncode($signature);
        
        return "$header.$payload.$signature";
    }
    
    private static function decodeToken($token) {
        list($header, $payload, $signature) = explode('.', $token);
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', "$header.$payload", self::$secretKey, true);
        $expectedSignature = self::base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid signature');
        }
        
        return json_decode(self::base64UrlDecode($payload), true);
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

// Initialize Auth class with secret key
Auth::init();
