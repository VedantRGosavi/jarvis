<?php
// Application settings
return [
    'name' => 'Gaming Companion Overlay',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => $_ENV['APP_DEBUG'] ?? true,

    // Database settings
    'database' => [
        'system' => BASE_PATH . '/data/system.sqlite',
        'game_data' => BASE_PATH . '/data/game_data',
    ],

    // Supported games
    'supported_games' => [
        'elden_ring' => [
            'name' => 'Elden Ring',
            'database' => 'elden_ring.sqlite',
            'price' => 1999, // $19.99
        ],
        'baldurs_gate3' => [
            'name' => "Baldur's Gate 3",
            'database' => 'baldurs_gate3.sqlite',
            'price' => 1999, // $19.99
        ],
    ],

    // Subscription settings
    'subscription' => [
        'price_monthly' => 999, // $9.99
        'trial_days' => 7,
    ],
];
