<?php
namespace App\Utils;

class Response {
    public static function success($data = null, $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    public static function error($message, $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
} 