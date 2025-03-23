<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables if available
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

require_once BASE_PATH . '/app/utils/Database.php';

use App\Utils\Database;

// Initialize database
try {
    $db = Database::getGameInstance('elden_ring');
    echo "Connected to Elden Ring database.\n";
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
}

/**
 * Fetch data from the Elden Ring Wiki API
 * @param string $endpoint API endpoint to fetch
 * @return array JSON decoded response
 */
function fetchFromAPI($endpoint) {
    $baseUrl = "https://eldenring.fanapis.com/api/";
    $url = $baseUrl . $endpoint;
    
    echo "Fetching data from: $url\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => ["accept: application/json"],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        echo "cURL Error: " . $err . "\n";
        return [];
    }
    
    return json_decode($response, true) ?: [];
}

/**
 * Import locations into database
 */
function importLocations($db) {
    echo "Importing locations...\n";
    
    try {
        // Clear existing locations data
        $db->exec("DELETE FROM locations");
        $db->exec("DELETE FROM sqlite_sequence WHERE name='locations'");
        
        $response = fetchFromAPI("locations?limit=100");
        $locations = $response['data'] ?? [];
        
        $count = 0;
        foreach ($locations as $location) {
            // Create a unique ID for the location
            $locationId = 'loc_' . md5($location['name']);
            
            // Determine parent location if available
            $parentLocationId = null;
            $region = $location['region'] ?? null;
            
            // Check if location already exists
            $checkStmt = $db->prepare("SELECT location_id FROM locations WHERE location_id = ?");
            $result = $db->execPrepared($checkStmt, [$locationId]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Skip if it already exists
                echo "Skipping duplicate location: " . $location['name'] . "\n";
                continue;
            }
            
            // Ensure description is not null
            $description = $location['description'] ?? 'No description available';
            
            // Insert location
            $stmt = $db->prepare(
                "INSERT INTO locations (
                    location_id, name, description, region, 
                    parent_location_id, coordinates, points_of_interest
                ) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $db->execPrepared($stmt, [
                $locationId,
                $location['name'],
                $description,
                $region,
                $parentLocationId,
                null, // coordinates
                null  // points_of_interest
            ]);
            
            if ($result) {
                $count++;
                
                // Add to search index
                $keywords = $location['name'] . ' ' . ($region ?? '');
                $stmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($stmt, [
                    $locationId,
                    'location',
                    $location['name'],
                    $description,
                    $keywords
                ]);
            }
        }
        
        echo "Imported $count locations.\n";
    } catch (Exception $e) {
        echo "Error importing locations: " . $e->getMessage() . "\n";
    }
}

/**
 * Import items into database
 */
function importItems($db) {
    echo "Importing items...\n";
    
    // Clear existing items data
    $db->exec("DELETE FROM items");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='items'");
    
    $itemTypes = ['weapons', 'armors', 'spells', 'shields', 'ashes', 'items'];
    $count = 0;
    
    foreach ($itemTypes as $type) {
        echo "Fetching $type...\n";
        
        $response = fetchFromAPI("$type?limit=100");
        $items = $response['data'] ?? [];
        
        foreach ($items as $item) {
            // Create a unique ID for the item
            $itemId = 'item_' . md5($item['name'] . '_' . $type);
            
            // Determine item rarity
            $rarity = 'common';
            if (strpos(strtolower($item['description'] ?? ''), 'rare') !== false) {
                $rarity = 'rare';
            } elseif (strpos(strtolower($item['description'] ?? ''), 'unique') !== false) {
                $rarity = 'unique';
            } elseif (strpos(strtolower($item['description'] ?? ''), 'legendary') !== false) {
                $rarity = 'legendary';
            }
            
            // Insert item
            $stmt = $db->prepare(
                "INSERT INTO items (
                    item_id, name, description, type, subtype,
                    stats, requirements, effects, rarity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stats = json_encode($item['attack'] ?? $item['defence'] ?? $item['effects'] ?? null);
            $requirements = json_encode($item['requiredAttributes'] ?? null);
            $effects = $item['skill'] ?? $item['effect'] ?? null;
            
            $result = $db->execPrepared($stmt, [
                $itemId,
                $item['name'],
                $item['description'] ?? '',
                $type,
                $item['category'] ?? null,
                $stats,
                $requirements,
                $effects,
                $rarity
            ]);
            
            if ($result) {
                $count++;
                
                // Add to search index
                $keywords = $item['name'] . ' ' . ($item['category'] ?? '') . ' ' . $type;
                $stmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($stmt, [
                    $itemId,
                    'item',
                    $item['name'],
                    $item['description'] ?? '',
                    $keywords
                ]);
            }
        }
    }
    
    echo "Imported $count items.\n";
}

/**
 * Import NPCs into database
 */
function importNPCs($db) {
    echo "Importing NPCs...\n";
    
    try {
        // Clear existing NPCs data
        $db->exec("DELETE FROM npcs");
        $db->exec("DELETE FROM sqlite_sequence WHERE name='npcs'");
        
        $response = fetchFromAPI("npcs?limit=100");
        $npcs = $response['data'] ?? [];
        
        $count = 0;
        foreach ($npcs as $npc) {
            // Create a unique ID for the NPC
            $npcId = 'npc_' . md5($npc['name']);
            
            // Check if NPC already exists
            $checkStmt = $db->prepare("SELECT npc_id FROM npcs WHERE npc_id = ?");
            $result = $db->execPrepared($checkStmt, [$npcId]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Skip if it already exists
                echo "Skipping duplicate NPC: " . $npc['name'] . "\n";
                continue;
            }
            
            // Determine hostility
            $isHostile = 0;
            if (strpos(strtolower($npc['description'] ?? ''), 'enemy') !== false || 
                strpos(strtolower($npc['description'] ?? ''), 'hostile') !== false ||
                strpos(strtolower($npc['description'] ?? ''), 'boss') !== false) {
                $isHostile = 1;
            }
            
            // Determine if merchant
            $isMerchant = 0;
            if (strpos(strtolower($npc['description'] ?? ''), 'merchant') !== false || 
                strpos(strtolower($npc['description'] ?? ''), 'sells') !== false ||
                strpos(strtolower($npc['description'] ?? ''), 'vendor') !== false) {
                $isMerchant = 1;
            }
            
            // Ensure description is not null
            $description = $npc['description'] ?? 'No description available';
            
            // Insert NPC
            $stmt = $db->prepare(
                "INSERT INTO npcs (
                    npc_id, name, description, role, faction,
                    is_hostile, is_merchant, dialogue_summary
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $db->execPrepared($stmt, [
                $npcId,
                $npc['name'],
                $description,
                null, // role
                null, // faction
                $isHostile,
                $isMerchant,
                null  // dialogue_summary
            ]);
            
            if ($result) {
                $count++;
                
                // Add default location if found in description
                $locationKeywords = ['found at', 'located in', 'resides in', 'lives in', 'stays at'];
                $locationId = null;
                
                foreach ($locationKeywords as $keyword) {
                    if (strpos(strtolower($description), $keyword) !== false) {
                        // Extract location from description (simplified)
                        preg_match('/' . $keyword . ' ([^\.]+)/i', $description, $matches);
                        if (!empty($matches[1])) {
                            // Find location by name
                            $locationStmt = $db->prepare("SELECT location_id FROM locations WHERE name LIKE ? LIMIT 1");
                            $locationResult = $db->execPrepared($locationStmt, ['%' . trim($matches[1]) . '%']);
                            $locationRow = $locationResult->fetchArray(SQLITE3_ASSOC);
                            if ($locationRow) {
                                $locationId = $locationRow['location_id'];
                                
                                // Link NPC to location
                                $npcLocationStmt = $db->prepare("INSERT INTO npc_locations (npc_id, location_id) VALUES (?, ?)");
                                $db->execPrepared($npcLocationStmt, [$npcId, $locationId]);
                                
                                // Update location's notable NPCs
                                $updateLocationStmt = $db->prepare("UPDATE locations SET notable_npcs = COALESCE(notable_npcs, '') || ',' || ? WHERE location_id = ?");
                                $db->execPrepared($updateLocationStmt, [$npcId, $locationId]);
                            }
                        }
                    }
                }
                
                // Add to search index
                $keywords = $npc['name'] . ' ' . ($isHostile ? 'enemy hostile' : 'friendly') . ' ' . ($isMerchant ? 'merchant vendor' : '');
                $stmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($stmt, [
                    $npcId,
                    'npc',
                    $npc['name'],
                    $description,
                    $keywords
                ]);
            }
        }
        
        echo "Imported $count NPCs.\n";
    } catch (Exception $e) {
        echo "Error importing NPCs: " . $e->getMessage() . "\n";
    }
}

/**
 * Import bosses into database
 */
function importBosses($db) {
    echo "Importing bosses...\n";
    
    try {
        // Clear existing bosses data
        $db->exec("DELETE FROM npcs WHERE category = 'boss'");
        
        // Load boss data from GitHub API
        $bossesJsonUrl = "https://raw.githubusercontent.com/deliton/eldenring-api/main/api/public/data/bosses.json";
        
        echo "Fetching boss data from: $bossesJsonUrl\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $bossesJsonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error: " . $err . "\n";
            return;
        }
        
        $bosses = json_decode($response, true);
        
        if (!$bosses || !is_array($bosses)) {
            echo "Error: Failed to parse bosses data\n";
            return;
        }
        
        $count = 0;
        foreach ($bosses as $boss) {
            // Create a unique ID for the boss
            $npcId = 'boss_' . md5($boss['name']);
            
            // Check if NPC already exists
            $checkStmt = $db->prepare("SELECT npc_id FROM npcs WHERE npc_id = ?");
            $result = $db->execPrepared($checkStmt, [$npcId]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Skip if it already exists
                echo "Skipping duplicate boss: " . $boss['name'] . "\n";
                continue;
            }
            
            // Prepare location data
            $defaultLocation = null;
            if (!empty($boss['location'])) {
                // Check if the location exists in the database
                $locStmt = $db->prepare("SELECT location_id FROM locations WHERE name LIKE ?");
                $locResult = $db->execPrepared($locStmt, ['%' . $boss['location'] . '%']);
                $locRow = $locResult->fetchArray(SQLITE3_ASSOC);
                
                if ($locRow) {
                    $defaultLocation = $locRow['location_id'];
                }
            }
            
            // Process drops into JSON
            $dropsJson = null;
            if (!empty($boss['drops']) && is_array($boss['drops'])) {
                $dropsJson = json_encode($boss['drops']);
            } elseif (!empty($boss['drops']) && is_string($boss['drops'])) {
                $drops = explode(',', str_replace('"', '', $boss['drops']));
                $dropsJson = json_encode($drops);
            }
            
            // Insert boss as an NPC
            $stmt = $db->prepare(
                "INSERT INTO npcs (
                    npc_id, name, description, role, faction, 
                    default_location_id, is_hostile, is_merchant, 
                    dialogue_summary, category, image_url, health, drops
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $db->execPrepared($stmt, [
                $npcId,
                $boss['name'],
                $boss['description'] ?? 'A formidable boss in the Lands Between.',
                'Boss',
                $boss['region'] ?? null,
                $defaultLocation,
                1, // Is hostile
                0, // Is not a merchant
                null, // No dialogue
                'boss', // Category
                $boss['image'] ?? null,
                $boss['healthPoints'] ?? null,
                $dropsJson
            ]);
            
            if ($result) {
                $count++;
                
                // Add to search index
                $keywords = $boss['name'] . ' boss ' . ($boss['region'] ?? '') . ' ' . ($boss['location'] ?? '');
                $stmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($stmt, [
                    $npcId,
                    'npc',
                    $boss['name'],
                    $boss['description'] ?? 'A formidable boss in the Lands Between.',
                    $keywords
                ]);
            }
        }
        
        echo "Imported $count bosses.\n";
    } catch (Exception $e) {
        echo "Error importing bosses: " . $e->getMessage() . "\n";
    }
}

/**
 * Import Quests into database (manually created based on wiki data)
 */
function importQuests($db) {
    echo "Importing quests...\n";
    
    // Clear existing quests data
    $db->exec("DELETE FROM quests");
    $db->exec("DELETE FROM quest_steps");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='quests'");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='quest_steps'");
    
    // Sample quest data (would be expanded with more complete data)
    $quests = [
        [
            'id' => 'quest_melina',
            'name' => 'Melina\'s Guidance',
            'description' => 'Follow Melina\'s guidance to reach the Erdtree and become Elden Lord.',
            'type' => 'main',
            'is_main_story' => 1,
            'difficulty' => 'medium',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'Meet Melina',
                    'description' => 'Rest at the Site of Grace at Gatefront to meet Melina.',
                    'objective' => 'Rest at the Site of Grace at Gatefront',
                    'hints' => 'Look for the glowing Site of Grace near the ruins with soldiers.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Reach Stormveil Castle',
                    'description' => 'Travel north from Limgrave to reach Stormveil Castle.',
                    'objective' => 'Find the entrance to Stormveil Castle',
                    'hints' => 'Follow the main path north from the Gatefront Ruins.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'Defeat Godrick the Grafted',
                    'description' => 'Confront and defeat Godrick the Grafted, the demigod ruler of Stormveil Castle.',
                    'objective' => 'Defeat Godrick the Grafted',
                    'hints' => 'Prepare for a difficult boss fight. Use Spirit Ashes to summon help.'
                ]
            ]
        ],
        [
            'id' => 'quest_ranni',
            'name' => 'Ranni\'s Questline',
            'description' => 'Assist the witch Ranni in her mysterious quest against the Two Fingers.',
            'type' => 'side',
            'is_main_story' => 0,
            'difficulty' => 'hard',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'Meet Ranni',
                    'description' => 'Visit Ranni\'s Rise in Liurnia and speak to the witch Ranni.',
                    'objective' => 'Speak to Ranni at Ranni\'s Rise',
                    'hints' => 'Ranni\'s Rise is located in the northwest part of Liurnia, behind Caria Manor.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Find Blaidd',
                    'description' => 'Locate Blaidd in Siofra River and learn about Nokron, Eternal City.',
                    'objective' => 'Speak to Blaidd in Siofra River',
                    'hints' => 'Reach Siofra River by taking the well elevator in Mistwood.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'Defeat Starscourge Radahn',
                    'description' => 'Travel to Caelid and defeat General Radahn in the Radahn Festival.',
                    'objective' => 'Defeat Starscourge Radahn',
                    'hints' => 'Speak to Jerren at Redmane Castle to participate in the festival.'
                ]
            ]
        ],
        [
            'id' => 'quest_alexander',
            'name' => 'Iron Fist Alexander',
            'description' => 'Help the warrior jar Alexander fulfill his purpose and become stronger.',
            'type' => 'side',
            'is_main_story' => 0,
            'difficulty' => 'medium',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'Stuck in a Hole',
                    'description' => 'Find Alexander stuck in the ground in Limgrave and help him get out.',
                    'objective' => 'Free Alexander from the ground',
                    'hints' => 'Alexander can be found north of the Agheel Lake South Site of Grace. Use a heavy attack to free him.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Radahn Festival',
                    'description' => 'Meet Alexander at the Radahn Festival in Redmane Castle, Caelid.',
                    'objective' => 'Find Alexander at Redmane Castle',
                    'hints' => 'Progress through the castle to reach the festival grounds.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'Mt. Gelmir Bath',
                    'description' => 'Find Alexander bathing in lava at Mt. Gelmir and help him get unstuck again.',
                    'objective' => 'Help Alexander at Mt. Gelmir',
                    'hints' => 'Look for him near the lava pools in Mt. Gelmir.'
                ]
            ]
        ]
    ];
    
    $count = 0;
    foreach ($quests as $quest) {
        // Insert quest
        $stmt = $db->prepare(
            "INSERT INTO quests (
                quest_id, name, description, type,
                is_main_story, difficulty
            ) VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $result = $db->execPrepared($stmt, [
            $quest['id'],
            $quest['name'],
            $quest['description'],
            $quest['type'],
            $quest['is_main_story'],
            $quest['difficulty']
        ]);
        
        if ($result) {
            $count++;
            
            // Add to search index
            $keywords = $quest['name'] . ' ' . $quest['type'] . ' quest';
            $stmt = $db->prepare(
                "INSERT INTO search_index (
                    content_id, content_type, name, description, keywords
                ) VALUES (?, ?, ?, ?, ?)"
            );
            $db->execPrepared($stmt, [
                $quest['id'],
                'quest',
                $quest['name'],
                $quest['description'],
                $keywords
            ]);
            
            // Extract quest steps if available
            if (!empty($quest['steps'])) {
                $stepCount = 0;
                foreach ($quest['steps'] as $index => $step) {
                    $stepId = 'step_' . md5($quest['id'] . '_' . $index);
                    
                    $stepStmt = $db->prepare(
                        "INSERT INTO quest_steps (
                            step_id, quest_id, step_number, title, description,
                            objective, location_id, spoiler_level
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    
                    $db->execPrepared($stepStmt, [
                        $stepId,
                        $quest['id'],
                        $index + 1,
                        $step['title'] ?? "Step " . ($index + 1),
                        $step['description'] ?? '',
                        $step['objective'] ?? '',
                        null, // location_id
                        null  // spoiler_level
                    ]);
                    
                    $stepCount++;
                }
                
                echo "Added $stepCount steps for quest " . $quest['id'] . "\n";
            }
        }
    }
    
    echo "Imported $count quests with their steps.\n";
}

/**
 * Import special item types directly from GitHub repository 
 */
function importSpecialItems($db) {
    echo "Importing special items from the GitHub repository...\n";
    
    $itemTypes = [
        'ammos' => 'Ammunition',
        'armors' => 'Armor',
        'ashes' => 'Ash of War',
        'talismans' => 'Talisman',
        'shields' => 'Shield',
        'weapons' => 'Weapon',
        'sorceries' => 'Sorcery',
        'incantations' => 'Incantation',
        'spirits' => 'Spirit'
    ];
    
    $count = 0;
    foreach ($itemTypes as $endpoint => $itemType) {
        // Load data from GitHub API
        $jsonUrl = "https://raw.githubusercontent.com/deliton/eldenring-api/main/api/public/data/{$endpoint}.json";
        
        echo "Fetching {$itemType} data from: {$jsonUrl}\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $jsonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error: " . $err . "\n";
            continue;
        }
        
        $items = json_decode($response, true);
        
        if (!$items || !is_array($items)) {
            echo "Error: Failed to parse {$itemType} data\n";
            continue;
        }
        
        echo "Found " . count($items) . " {$itemType} items to import\n";
        
        foreach ($items as $item) {
            // Create a unique ID for the item
            $itemId = 'item_' . strtolower(str_replace(' ', '_', $endpoint)) . '_' . md5($item['name']);
            
            // Check if item already exists
            $checkStmt = $db->prepare("SELECT item_id FROM items WHERE item_id = ?");
            $result = $db->execPrepared($checkStmt, [$itemId]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Update existing item
                $updateStmt = $db->prepare(
                    "UPDATE items SET 
                    description = ?, 
                    subtype = ?, 
                    stats = ?, 
                    requirements = ?, 
                    effects = ?, 
                    image_path = ? 
                    WHERE item_id = ?"
                );
                
                // Prepare the data
                $stats = null;
                $requirements = null;
                $effects = null;
                
                // Process specific fields based on item type
                switch ($endpoint) {
                    case 'ammos':
                        $stats = json_encode([
                            'type' => $item['type'] ?? null,
                            'passive' => $item['passive'] ?? null,
                            'attackPower' => $item['attackPower'] ?? null
                        ]);
                        break;
                    case 'armors':
                        $stats = json_encode([
                            'weight' => $item['weight'] ?? null,
                            'dmgNegation' => $item['dmgNegation'] ?? null,
                            'resistance' => $item['resistance'] ?? null
                        ]);
                        break;
                    case 'ashes':
                        $effects = $item['skill'] ?? null;
                        $stats = json_encode([
                            'affinity' => $item['affinity'] ?? null,
                            'skill' => $item['skill'] ?? null,
                        ]);
                        break;
                    case 'talismans':
                        $effects = $item['effect'] ?? null;
                        break;
                    case 'shields':
                        $stats = json_encode([
                            'weight' => $item['weight'] ?? null,
                            'attack' => $item['attack'] ?? null,
                            'defence' => $item['defence'] ?? null,
                            'requiredAttributes' => $item['requiredAttributes'] ?? null,
                            'scalesWith' => $item['scalesWith'] ?? null
                        ]);
                        $effects = $item['skill'] ?? null;
                        break;
                    case 'weapons':
                        $stats = json_encode([
                            'attack' => $item['attack'] ?? null,
                            'defence' => $item['defence'] ?? null,
                            'requiredAttributes' => $item['requiredAttributes'] ?? null,
                            'scalesWith' => $item['scalesWith'] ?? null,
                            'weight' => $item['weight'] ?? null
                        ]);
                        $effects = $item['skill'] ?? null;
                        break;
                    case 'sorceries':
                    case 'incantations':
                        $stats = json_encode([
                            'cost' => $item['cost'] ?? null,
                            'slots' => $item['slots'] ?? null,
                        ]);
                        $requirements = json_encode($item['requires'] ?? null);
                        $effects = $item['effects'] ?? null;
                        break;
                    case 'spirits':
                        $stats = json_encode([
                            'fpCost' => $item['fpCost'] ?? null,
                            'hpCost' => $item['hpCost'] ?? null,
                        ]);
                        $effects = $item['effect'] ?? null;
                        break;
                }
                
                $result = $db->execPrepared($updateStmt, [
                    $item['description'] ?? '',
                    $item['category'] ?? null,
                    $stats,
                    $requirements,
                    $effects,
                    $item['image'] ?? null,
                    $itemId
                ]);
                
                if ($result) {
                    echo "Updated existing item: " . $item['name'] . "\n";
                    $count++;
                }
            } else {
                // Insert new item
                $stmt = $db->prepare(
                    "INSERT INTO items (
                        item_id, name, description, type, subtype,
                        stats, requirements, effects, rarity, image_path
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                // Determine item rarity if available
                $rarity = 'common';
                if (isset($item['rarity'])) {
                    $rarity = strtolower($item['rarity']);
                } else if (isset($item['type']) && stripos($item['type'], 'legend') !== false) {
                    $rarity = 'legendary';
                }
                
                // Prepare the data
                $stats = null;
                $requirements = null;
                $effects = null;
                
                // Process specific fields based on item type
                switch ($endpoint) {
                    case 'ammos':
                        $stats = json_encode([
                            'type' => $item['type'] ?? null,
                            'passive' => $item['passive'] ?? null,
                            'attackPower' => $item['attackPower'] ?? null
                        ]);
                        break;
                    case 'armors':
                        $stats = json_encode([
                            'weight' => $item['weight'] ?? null,
                            'dmgNegation' => $item['dmgNegation'] ?? null,
                            'resistance' => $item['resistance'] ?? null
                        ]);
                        break;
                    case 'ashes':
                        $effects = $item['skill'] ?? null;
                        $stats = json_encode([
                            'affinity' => $item['affinity'] ?? null,
                            'skill' => $item['skill'] ?? null,
                        ]);
                        break;
                    case 'talismans':
                        $effects = $item['effect'] ?? null;
                        break;
                    case 'shields':
                        $stats = json_encode([
                            'weight' => $item['weight'] ?? null,
                            'attack' => $item['attack'] ?? null,
                            'defence' => $item['defence'] ?? null,
                            'requiredAttributes' => $item['requiredAttributes'] ?? null,
                            'scalesWith' => $item['scalesWith'] ?? null
                        ]);
                        $effects = $item['skill'] ?? null;
                        break;
                    case 'weapons':
                        $stats = json_encode([
                            'attack' => $item['attack'] ?? null,
                            'defence' => $item['defence'] ?? null,
                            'requiredAttributes' => $item['requiredAttributes'] ?? null,
                            'scalesWith' => $item['scalesWith'] ?? null,
                            'weight' => $item['weight'] ?? null
                        ]);
                        $effects = $item['skill'] ?? null;
                        break;
                    case 'sorceries':
                    case 'incantations':
                        $stats = json_encode([
                            'cost' => $item['cost'] ?? null,
                            'slots' => $item['slots'] ?? null,
                        ]);
                        $requirements = json_encode($item['requires'] ?? null);
                        $effects = $item['effects'] ?? null;
                        break;
                    case 'spirits':
                        $stats = json_encode([
                            'fpCost' => $item['fpCost'] ?? null,
                            'hpCost' => $item['hpCost'] ?? null,
                        ]);
                        $effects = $item['effect'] ?? null;
                        break;
                }
                
                $result = $db->execPrepared($stmt, [
                    $itemId,
                    $item['name'],
                    $item['description'] ?? '',
                    $itemType,
                    $item['category'] ?? null,
                    $stats,
                    $requirements,
                    $effects,
                    $rarity,
                    $item['image'] ?? null
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $item['name'] . ' ' . $itemType . ' ' . ($item['category'] ?? '') . ' ' . $rarity;
                    $stmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($stmt, [
                        $itemId,
                        'item',
                        $item['name'],
                        $item['description'] ?? '',
                        $keywords
                    ]);
                }
            }
        }
    }
    
    echo "Imported/updated $count special items.\n";
}

/**
 * Import creatures from GitHub repository
 */
function importCreatures($db) {
    echo "Importing creatures...\n";
    
    try {
        // Load creature data from GitHub API
        $creaturesJsonUrl = "https://raw.githubusercontent.com/deliton/eldenring-api/main/api/public/data/creatures.json";
        
        echo "Fetching creature data from: $creaturesJsonUrl\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $creaturesJsonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error: " . $err . "\n";
            return;
        }
        
        $creatures = json_decode($response, true);
        
        if (!$creatures || !is_array($creatures)) {
            echo "Error: Failed to parse creatures data\n";
            return;
        }
        
        $count = 0;
        foreach ($creatures as $creature) {
            // Create a unique ID for the creature
            $npcId = 'creature_' . md5($creature['name']);
            
            // Check if NPC already exists
            $checkStmt = $db->prepare("SELECT npc_id FROM npcs WHERE npc_id = ?");
            $result = $db->execPrepared($checkStmt, [$npcId]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            // Determine if the creature is hostile (most creatures are)
            $isHostile = 1;
            
            // Determine location if available
            $locationId = null;
            if (!empty($creature['location'])) {
                $locStmt = $db->prepare("SELECT location_id FROM locations WHERE name LIKE ? LIMIT 1");
                $locResult = $db->execPrepared($locStmt, ['%' . $creature['location'] . '%']);
                $locRow = $locResult->fetchArray(SQLITE3_ASSOC);
                
                if ($locRow) {
                    $locationId = $locRow['location_id'];
                }
            }
            
            // Process drops into JSON
            $dropsJson = null;
            if (!empty($creature['drops']) && is_array($creature['drops'])) {
                $dropsJson = json_encode($creature['drops']);
            } elseif (!empty($creature['drops']) && is_string($creature['drops'])) {
                $drops = explode(',', str_replace('"', '', $creature['drops']));
                $dropsJson = json_encode($drops);
            }
            
            if ($exists) {
                // Update the existing creature
                $updateStmt = $db->prepare(
                    "UPDATE npcs SET 
                    description = ?, 
                    default_location_id = ?, 
                    category = ?, 
                    image_url = ?, 
                    drops = ? 
                    WHERE npc_id = ?"
                );
                
                $result = $db->execPrepared($updateStmt, [
                    $creature['description'] ?? 'A creature found in the Lands Between.',
                    $locationId,
                    'creature',
                    $creature['image'] ?? null,
                    $dropsJson,
                    $npcId
                ]);
                
                if ($result) {
                    echo "Updated existing creature: " . $creature['name'] . "\n";
                    $count++;
                }
            } else {
                // Insert creature as an NPC
                $stmt = $db->prepare(
                    "INSERT INTO npcs (
                        npc_id, name, description, role, faction, 
                        default_location_id, is_hostile, is_merchant, 
                        dialogue_summary, category, image_url, drops
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $result = $db->execPrepared($stmt, [
                    $npcId,
                    $creature['name'],
                    $creature['description'] ?? 'A creature found in the Lands Between.',
                    'Creature',
                    null, // faction
                    $locationId,
                    $isHostile,
                    0, // Is not a merchant
                    null, // No dialogue
                    'creature',
                    $creature['image'] ?? null,
                    $dropsJson
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $creature['name'] . ' creature ' . ($creature['location'] ?? '');
                    $stmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($stmt, [
                        $npcId,
                        'npc',
                        $creature['name'],
                        $creature['description'] ?? 'A creature found in the Lands Between.',
                        $keywords
                    ]);
                }
            }
        }
        
        echo "Imported/updated $count creatures.\n";
    } catch (Exception $e) {
        echo "Error importing creatures: " . $e->getMessage() . "\n";
    }
}

/**
 * Import classes from GitHub repository
 */
function importClasses($db) {
    echo "Importing character classes...\n";
    
    try {
        // Load class data from GitHub API
        $classesJsonUrl = "https://raw.githubusercontent.com/deliton/eldenring-api/main/api/public/data/classes.json";
        
        echo "Fetching class data from: $classesJsonUrl\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $classesJsonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error: " . $err . "\n";
            return;
        }
        
        $classes = json_decode($response, true);
        
        if (!$classes || !is_array($classes)) {
            echo "Error: Failed to parse classes data\n";
            return;
        }
        
        // Create a new table for classes if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS classes (
            class_id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            stats TEXT,
            equipment TEXT,
            image_url TEXT
        )");
        
        // Clear existing classes data
        $db->exec("DELETE FROM classes");
        
        $count = 0;
        foreach ($classes as $class) {
            // Create a unique ID for the class
            $classId = 'class_' . md5($class['name']);
            
            // Insert class
            $stmt = $db->prepare(
                "INSERT INTO classes (
                    class_id, name, description, stats, equipment, image_url
                ) VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            // Process stats and equipment into JSON
            $stats = json_encode([
                'level' => $class['level'] ?? null,
                'vigor' => $class['vigor'] ?? null,
                'mind' => $class['mind'] ?? null,
                'endurance' => $class['endurance'] ?? null,
                'strength' => $class['strength'] ?? null,
                'dexterity' => $class['dexterity'] ?? null,
                'intelligence' => $class['intelligence'] ?? null,
                'faith' => $class['faith'] ?? null,
                'arcane' => $class['arcane'] ?? null
            ]);
            
            $equipment = json_encode($class['equipment'] ?? null);
            
            $result = $db->execPrepared($stmt, [
                $classId,
                $class['name'],
                $class['description'] ?? 'A starting class in Elden Ring.',
                $stats,
                $equipment,
                $class['image'] ?? null
            ]);
            
            if ($result) {
                $count++;
                
                // Add to search index
                $keywords = $class['name'] . ' class starting character build';
                $stmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($stmt, [
                    $classId,
                    'class',
                    $class['name'],
                    $class['description'] ?? 'A starting class in Elden Ring.',
                    $keywords
                ]);
            }
        }
        
        echo "Imported $count character classes.\n";
    } catch (Exception $e) {
        echo "Error importing classes: " . $e->getMessage() . "\n";
    }
}

// Run the import functions
try {
    echo "Starting Elden Ring data import...\n";
    
    // Start transaction
    $db->exec("BEGIN TRANSACTION");
    
    // Import data
    importLocations($db);
    importItems($db);
    importNPCs($db);
    importBosses($db);
    importQuests($db);
    importCreatures($db);
    importSpecialItems($db);
    importClasses($db);
    
    // Commit transaction
    $db->exec("COMMIT");
    
    echo "Elden Ring data import completed successfully!\n";
} catch (Exception $e) {
    // Rollback transaction in case of error
    $db->exec("ROLLBACK");
    echo "Error during import: " . $e->getMessage() . "\n";
} 