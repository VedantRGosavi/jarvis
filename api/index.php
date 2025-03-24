<?php
// Basic test file to check PHP runtime on Vercel

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'message' => 'PHP is working!',
    'php_version' => PHP_VERSION,
    'extensions' => get_loaded_extensions(),
    'env' => $_ENV
]);
