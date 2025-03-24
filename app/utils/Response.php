<?php
namespace App\Utils;

/**
 * Response Utility
 * Handles formatting and sending API responses
 */
class Response {
    /**
     * Send a JSON response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param array $headers Additional HTTP headers
     */
    public static function json($data, $statusCode = 200, $headers = []) {
        // Set content type header
        header('Content-Type: application/json');

        // Set status code
        http_response_code($statusCode);

        // Set additional headers
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }

        // JSON encode with error handling
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            // Log the error
            error_log("JSON encoding error: " . json_last_error_msg());
            // Send a fallback response
            echo json_encode([
                'success' => false,
                'message' => 'Server error: Unable to encode response data'
            ]);
        } else {
            // Return JSON-encoded data
            echo $json;
        }
        exit;
    }

    /**
     * Send a success response
     *
     * @param mixed $data The data to include in the response
     * @param int $statusCode HTTP status code
     */
    public static function success($data = null, $statusCode = 200) {
        $response = [
            'success' => true
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send an error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send a text response
     *
     * @param string $text Response text
     * @param int $statusCode HTTP status code
     * @param array $headers Additional HTTP headers
     */
    public static function text($text, $statusCode = 200, $headers = []) {
        // Set content type header
        header('Content-Type: text/plain');

        // Set status code
        http_response_code($statusCode);

        // Set additional headers
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }

        // Return text
        echo $text;
        exit;
    }

    /**
     * Send an HTML response
     *
     * @param string $html Response HTML
     * @param int $statusCode HTTP status code
     * @param array $headers Additional HTTP headers
     */
    public static function html($html, $statusCode = 200, $headers = []) {
        // Set content type header
        header('Content-Type: text/html');

        // Set status code
        http_response_code($statusCode);

        // Set additional headers
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }

        // Return HTML
        echo $html;
        exit;
    }

    /**
     * Redirect to another URL
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (301 or 302)
     */
    public static function redirect($url, $statusCode = 302) {
        // Set status code
        http_response_code($statusCode);

        // Set location header
        header("Location: $url");
        exit;
    }
}
