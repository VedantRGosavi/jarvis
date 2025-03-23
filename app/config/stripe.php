<?php

return [
    'integration_version' => '1.0.0',
    
    'products' => [
        // Core Game Data Packs
        'elden_ring' => [
            'id' => 'prod_RzsUc7tUAsw7zs',
            'price_id' => 'price_1R5sjwK9ng7JDxksVP7LdRsF', // $5.99
            'metadata' => [
                'product_type' => 'core_game',
                'game_id' => 'elden_ring',
                'features' => [
                    'quest_info',
                    'item_locations',
                    'game_mechanics',
                    'basic_overlay'
                ]
            ]
        ],
        'baldurs_gate_3' => [
            'id' => 'prod_RzsSE2lDLtRFmz',
            'price_id' => 'price_1R5shwK9ng7JDxks1NWmypAg', // $5.99
            'metadata' => [
                'product_type' => 'core_game',
                'game_id' => 'baldurs_gate_3',
                'features' => [
                    'quest_info',
                    'item_locations',
                    'game_mechanics',
                    'basic_overlay'
                ]
            ]
        ],
        'kingdom_come' => [
            'id' => 'prod_RzsSmyvQ7nXFaR',
            'price_id' => 'price_1R5si5K9ng7JDxksdcvyWmfS', // $5.99
            'metadata' => [
                'product_type' => 'core_game',
                'game_id' => 'kingdom_come',
                'features' => [
                    'quest_info',
                    'item_locations',
                    'game_mechanics',
                    'basic_overlay'
                ]
            ]
        ],

        // Premium Add-ons
        'priority_support' => [
            'id' => 'prod_RzsTm2Qtr4jo5p',
            'price_id' => 'price_1R5siCK9ng7JDxksUyBLBTJw', // $3.99
            'metadata' => [
                'product_type' => 'addon',
                'features' => [
                    '24h_response',
                    'direct_email_support'
                ]
            ]
        ],
        'overlay_themes' => [
            'id' => 'prod_RzsTG7caSQdGO2',
            'price_id' => 'price_1R5siJK9ng7JDxksgB3aHwUx', // $2.49
            'metadata' => [
                'product_type' => 'addon',
                'features' => [
                    'custom_themes',
                    'personalized_overlay'
                ]
            ]
        ],
        'premium_features' => [
            'id' => 'prod_RzsTecvVm32yD8',
            'price_id' => 'price_1R5siQK9ng7JDxksic3SL1KV', // $3.99
            'metadata' => [
                'product_type' => 'addon',
                'features' => [
                    'detailed_maps',
                    'voice_narration',
                    'advanced_mechanics'
                ]
            ]
        ],

        // Trial
        'trial' => [
            'id' => 'prod_RzsTxQJdovC6MD',
            'price_id' => 'price_1R5siWK9ng7JDxksQvOw02Ym', // $0.99
            'metadata' => [
                'product_type' => 'trial',
                'features' => [
                    'all_core_features',
                    '7_day_access'
                ]
            ]
        ],

        // Template for future games
        'game_template' => [
            'id' => 'prod_RzrgDzKd2Ou4Tc',
            'price_id' => 'price_1R5rwoQsTgjjtXBeuUdBrffm', // $5.99
            'metadata' => [
                'product_type' => 'core_game',
                'features' => [
                    'quest_info',
                    'item_locations',
                    'game_mechanics',
                    'basic_overlay'
                ]
            ]
        ]
    ]
];
