/*
 * IMPORTANT: If any changes are made to this backend architecture,
 * please ensure that corresponding updates are applied throughout
 * the entire codebase to maintain consistency and prevent integration issues.
 */

# Gaming Companion Overlay Tool - Backend Architecture

## Overview

The backend follows a minimalist PHP architecture inspired by Pieter Levels' approach, focusing on simplicity, speed, and maintainability. It handles user authentication, game data management, and payment processing with minimal dependencies.

## Technology Stack

- **PHP 8.1+**: Core server-side language
- **SQLite**: Lightweight file-based database
- **NGINX**: Web server for PHP processing and static file delivery
- **Stripe PHP SDK**: Payment processing integration

## File Structure

```
app/
├── public/                     # Web root directory
│   ├── index.php              # Entry point for web requests
│   ├── .htaccess              # URL rewriting rules
│   └── assets/                # Public assets (already in frontend doc)
├── api/                       # API endpoints
│   ├── index.php              # API router
│   ├── auth.php               # Authentication endpoints
│   ├── games.php              # Game data endpoints
│   ├── users.php              # User management
│   └── payments.php           # Payment processing
├── config/                    # Configuration files
│   ├── app.php                # Application settings
│   ├── database.php           # Database configuration
│   └── stripe.php             # Stripe API credentials
├── models/                    # Data models
│   ├── User.php               # User account model
│   ├── Game.php               # Game data model
│   ├── Subscription.php       # Subscription model
│   └── Purchase.php           # One-time purchase model
├── utils/                     # Utility functions
│   ├── Database.php           # Database connection handler
│   ├── Auth.php               # Authentication utilities
│   ├── Response.php           # API response formatter
│   └── Logger.php             # Logging functionality
├── data/                      # Data storage
│   ├── system.sqlite        # Main SQLite database for user accounts and settings
│   ├── logs/                # Application logs
│   └── game_data/           # Game-specific databases
│       ├── elden_ring.sqlite   # Elden Ring database
│       └── baldurs_gate3.sqlite # Baldur's Gate 3 database
└── vendor/                    # Composer dependencies (minimal)
    └── stripe/                # Stripe PHP SDK
```

## Component Details

### 1. Request Handling (`public/index.php`)

This file serves as the entry point for all web requests, implementing a simple router:

```php
<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/config/app.php';

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// API route handling
if (strpos($request_uri, '/api/') === 0) {
    require BASE_PATH . '/api/index.php';
    exit;
}

// Static file handling
$file_path = BASE_PATH . '/public' . $request_uri;
if (file_exists($file_path) && is_file($file_path)) {
    // Determine mime type based on extension
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $mime_types = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico' => 'image/x-icon',
    ];

    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    }

    readfile($file_path);
    exit;
}

// Default to serving the SPA entry point
require_once BASE_PATH . '/public/app.html';
```

### 2. API Router (`api/index.php`)

This file routes API requests to the appropriate endpoint handlers:

```php
<?php
// API request handling
$api_path = str_replace('/api/', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$api_segments = explode('/', $api_path);

// Determine API endpoint
$endpoint = $api_segments[0] ?? '';

// Route to appropriate handler
switch ($endpoint) {
    case 'auth':
        require BASE_PATH . '/api/auth.php';
        break;
    case 'games':
        require BASE_PATH . '/api/games.php';
        break;
    case 'users':
        require BASE_PATH . '/api/users.php';
        break;
    case 'payments':
        require BASE_PATH . '/api/payments.php';
        break;
    default:
        // Handle 404 for API
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API endpoint not found']);
}
```

### 3. Database Utilities (`utils/Database.php`)

This file provides a simple database connection interface for accessing both the system database and game-specific databases:

```php
<?php
class Database {
    private static $systemInstance = null;
    private static $gameInstances = [];
    private $db = null;

    // Private constructor for singleton pattern
    private function __construct($databasePath) {
        $this->db = new SQLite3($databasePath);
        $this->db->enableExceptions(true);
    }

    // Get system database instance
    public static function getSystemInstance() {
        if (self::$systemInstance === null) {
            self::$systemInstance = new self(BASE_PATH . '/data/system.sqlite');
        }
        return self::$systemInstance;
    }

    // Get game database instance
    public static function getGameInstance($game) {
        // Validate game ID to prevent directory traversal
        if (!in_array($game, ['elden_ring', 'baldurs_gate3'])) {
            throw new Exception("Invalid game identifier");
        }

        if (!isset(self::$gameInstances[$game])) {
            self::$gameInstances[$game] = new self(BASE_PATH . "/data/game_data/{$game}.sqlite");
        }
        return self::$gameInstances[$game];
    }

    // Query execution methods
    public function query($sql) {
        return $this->db->query($sql);
    }

    public function prepare($sql) {
        return $this->db->prepare($sql);
    }

    public function exec($sql) {
        return $this->db->exec($sql);
    }

    // Fetch methods
    public function fetchAll($sql, $params = []) {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($param, $value, $type);
        }

        $result = $stmt->execute();

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($param, $value, $type);
        }

        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC);
    }
}
```

### 4. Authentication Handler (`api/auth.php`)

This file handles user authentication endpoints:

```php
<?php
require_once BASE_PATH . '/utils/Response.php';
require_once BASE_PATH . '/models/User.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate auth handler
$action = $api_segments[1] ?? '';

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password required', 400);
            break;
        }

        $user = new User();
        $result = $user->authenticate($data['email'], $data['password']);

        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } else {
            Response::error('Invalid credentials', 401);
        }
        break;

    case 'register':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            Response::error('Name, email and password required', 400);
            break;
        }

        $user = new User();
        $result = $user->register($data['name'], $data['email'], $data['password']);

        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } else {
            Response::error($result['message'], 400);
        }
        break;

    default:
        Response::error('Auth endpoint not found', 404);
}
```

### 5. User Model (`models/User.php`)

This file implements the User model:

```php
<?php
require_once BASE_PATH . '/utils/Database.php';
require_once BASE_PATH . '/utils/Auth.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function authenticate($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );

        if (!$user) {
            return ['success' => false];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false];
        }

        // Generate JWT token
        $token = Auth::generateToken($user['id']);

        // Remove password from user data
        unset($user['password']);

        return [
            'success' => true,
            'token' => $token,
            'user' => $user
        ];
    }

    public function register($name, $email, $password) {
        // Check if user already exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );

        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'User with this email already exists'
            ];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s'),
            'subscription_status' => 'none'
        ]);

        // Generate JWT token
        $token = Auth::generateToken($userId);

        // Get user data
        $user = $this->db->fetchOne(
            "SELECT id, name, email, created_at, subscription_status FROM users WHERE id = :id",
            ['id' => $userId]
        );

        return [
            'success' => true,
            'token' => $token,
            'user' => $user
        ];
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT id, name, email, created_at, subscription_status FROM users WHERE id = :id",
            ['id' => $id]
        );
    }

    public function updateSubscription($userId, $status) {
        return $this->db->update(
            'users',
            ['subscription_status' => $status],
            'id = :id',
            ['id' => $userId]
        );
    }
}
```

### 6. Game Data Handler (`api/games.php`)

This file manages game data access:

```php
<?php
require_once BASE_PATH . '/utils/Response.php';
require_once BASE_PATH . '/utils/Auth.php';
require_once BASE_PATH . '/models/Game.php';

// Validate authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token || !Auth::validateToken($token)) {
    Response::error('Unauthorized', 401);
    exit;
}

// Get authenticated user
$userId = Auth::getUserIdFromToken($token);

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$game = $api_segments[1] ?? '';
$resource = $api_segments[2] ?? '';
$resourceId = $api_segments[3] ?? '';

// Initialize game model
$gameModel = new Game();

// Check user access to requested game
if (!$gameModel->checkUserAccess($userId, $game)) {
    Response::error('Access denied to this game data', 403);
    exit;
}

// Route to appropriate handler based on resource type
switch ($resource) {
    case 'quests':
        if ($resourceId) {
            // Get specific quest
            $questData = $gameModel->getQuestData($game, $resourceId);
            if ($questData) {
                Response::success($questData);
            } else {
                Response::error('Quest not found', 404);
            }
        } else {
            // List all quests
            $quests = $gameModel->listQuests($game);
            Response::success($quests);
        }
        break;

    case 'regions':
    case 'areas':
        // List all regions/areas
        $areas = $gameModel->listRegions($game);
        Response::success($areas);
        break;

    case 'items':
        if ($resourceId) {
            // Get specific item
            $itemData = $gameModel->getItemData($game, $resourceId);
            if ($itemData) {
                Response::success($itemData);
            } else {
                Response::error('Item not found', 404);
            }
        } else {
            // List all items
            $items = $gameModel->listItems($game);
            Response::success($items);
        }
        break;

    case 'search':
        // Search across game data
        $query = $_GET['q'] ?? '';
        if (!$query) {
            Response::error('Search query required', 400);
            break;
        }

        $results = $gameModel->search($game, $query);
        Response::success($results);
        break;

    default:
        // Get game overview data
        $gameData = $gameModel->getGameOverview($game);
        if ($gameData) {
            Response::success($gameData);
        } else {
            Response::error('Game data not found', 404);
        }
}
```

### 8. Response Utility (`utils/Response.php`)

This file provides standardized API responses:

```php
<?php
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
```

### 9. Authentication Utility (`utils/Auth.php`)

This file handles JWT token generation and validation:

```php
<?php
class Auth {
    private static $secretKey = 'your-secret-key'; // Move to config in production
    private static $tokenExpiration = 86400; // 24 hours

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
```

### 10. Payment Handler (`api/payments.php`)

This file manages payment processing with Stripe:

```php
<?php
require_once BASE_PATH . '/utils/Response.php';
require_once BASE_PATH . '/utils/Auth.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/models/Subscription.php';
require_once BASE_PATH . '/models/Purchase.php';
require_once BASE_PATH . '/vendor/stripe/init.php';

// Load Stripe configuration
$stripeConfig = require BASE_PATH . '/config/stripe.php';
\Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

// Validate authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token || !Auth::validateToken($token)) {
    Response::error('Unauthorized', 401);
    exit;
}

// Get authenticated user
$userId = Auth::getUserIdFromToken($token);
$userModel = new User();
$user = $userModel->getById($userId);

if (!$user) {
    Response::error('User not found', 404);
    exit;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $api_segments[1] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// Route to appropriate payment handler
switch ($action) {
    case 'create-subscription':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        try {
            // Create Stripe customer if not exists
            $subscriptionModel = new Subscription();
            $stripeCustomerId = $subscriptionModel->getStripeCustomerId($userId);

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $subscriptionModel->saveStripeCustomerId($userId, $stripeCustomerId);
            }

            // Create subscription
            $subscription = \Stripe\Subscription::create([
                'customer' => $stripeCustomerId,
                'items' => [
                    ['price' => $stripeConfig['subscription_price_id']]
                ],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
                'trial_period_days' => 7
            ]);

            $subscriptionModel->createSubscription(
                $userId,
                $subscription->id,
                'trialing',
                date('Y-m-d H:i:s', $subscription->current_period_end)
            );

            // Update user status
            $userModel->updateSubscription($userId, 'trialing');

            Response::success([
                'subscription_id' => $subscription->id,
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
                'status' => $subscription->status
            ]);

        } catch (\Exception $e) {
            Response::error('Subscription creation failed: ' . $e->getMessage(), 400);
        }
        break;

    case 'one-time-purchase':
        if ($method !== 'POST') {
            Response::error('Method not allowed', 405);
            break;
        }

        if (!isset($data['game_id'])) {
            Response::error('Game ID required', 400);
            break;
        }

        try {
            $gameId = $data['game_id'];

            // Create Stripe customer if not exists
            $subscriptionModel = new Subscription();
            $stripeCustomerId = $subscriptionModel->getStripeCustomerId($userId);

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $subscriptionModel->saveStripeCustomerId($userId, $stripeCustomerId);
            }

            // Create payment intent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => 1999, // $19.99
                'currency' => 'usd',
                'customer' => $stripeCustomerId,
                'metadata' => [
                    'user_id' => $userId,
                    'game_id' => $gameId
                ]
            ]);

            // Create purchase record
            $purchaseModel = new Purchase();
            $purchaseModel->createPurchase(
                $userId,
                $gameId,
                $paymentIntent->id,
                'pending',
                1999
            );

            Response::success([
                'client_secret' => $paymentIntent->client_secret
            ]);

        } catch (\Exception $e) {
            Response::error('Payment creation failed: ' . $e->getMessage(), 400);
        }
        break;

    case 'webhook':
        // Handle Stripe webhooks
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $stripeConfig['webhook_secret']
            );

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $purchaseModel = new Purchase();
                    $purchaseModel->updatePurchaseStatus($paymentIntent->id, 'completed');
                    break;

                case 'invoice.paid':
                    $invoice = $event->data->object;
                    $subscriptionModel = new Subscription();
                    $subscriptionModel->updateSubscriptionStatus($invoice->subscription, 'active');

                    // Update user status from Stripe customer ID
                    $userId = $subscriptionModel->getUserIdFromSubscription($invoice->subscription);
                    if ($userId) {
                        $userModel->updateSubscription($userId, 'subscribed');
                    }
                    break;

                case 'customer.subscription.deleted':
                    $subscription = $event->data->object;
                    $subscriptionModel = new Subscription();
                    $subscriptionModel->updateSubscriptionStatus($subscription->id, 'cancelled');

                    // Update user status
                    $userId = $subscriptionModel->getUserIdFromSubscription($subscription->id);
                    if ($userId) {
                        $userModel->updateSubscription($userId, 'none');
                    }
                    break;
            }

            Response::success(['status' => 'success']);

        } catch (\UnexpectedValueException $e) {
            Response::error('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Response::error('Invalid signature', 400);
        } catch (\Exception $e) {
            Response::error('Webhook error: ' . $e->getMessage(), 400);
        }
        break;

    default:
        Response::error('Payment endpoint not found', 404);
}

### 8. Game Model (`models/Game.php`)

This file implements the Game data model:

```php
<?php
require_once BASE_PATH . '/utils/Database.php';

class Game {
    private $db;
    private $game;

    public function __construct($game) {
        $this->game = $game;
        $this->db = Database::getGameInstance($game);
    }

    // Get quest information with steps
    public function getQuest($questId, $spoilerLevel = 0) {
        $quest = $this->db->fetchOne("
            SELECT * FROM quests
            WHERE quest_id = :id AND spoiler_level <= :spoiler_level
        ", [
            ':id' => $questId,
            ':spoiler_level' => $spoilerLevel
        ]);

        if (!$quest) {
            return null;
        }

        // Fetch quest steps
        $steps = $this->db->fetchAll("
            SELECT * FROM quest_steps
            WHERE quest_id = :quest_id AND spoiler_level <= :spoiler_level
            ORDER BY step_number
        ", [
            ':quest_id' => $questId,
            ':spoiler_level' => $spoilerLevel
        ]);

        $quest['steps'] = $steps;

        return $quest;
    }

    // Search game content
    public function search($term, $contentType = null, $limit = 10) {
        $sql = "
            SELECT * FROM search_index
            WHERE (name LIKE :term OR description LIKE :term OR keywords LIKE :term)
        ";

        if ($contentType) {
            $sql .= " AND content_type = :content_type";
        }

        $sql .= " LIMIT :limit";

        $params = [
            ':term' => '%' . $term . '%',
            ':limit' => $limit
        ];

        if ($contentType) {
            $params[':content_type'] = $contentType;
        }

        return $this->db->fetchAll($sql, $params);
    }
}
