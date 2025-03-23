<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    die("Error: vendor/autoload.php not found. Please run 'composer install' first.\n");
}

// Load environment variables if available
if (file_exists(BASE_PATH . '/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();
    } catch (Exception $e) {
        die("Error loading .env file: " . $e->getMessage() . "\n");
    }
} else {
    echo "Warning: .env file not found, using default settings.\n";
}

require_once BASE_PATH . '/app/utils/Database.php';

use App\Utils\Database;

// Initialize database
try {
    if (!file_exists(BASE_PATH . '/database/baldurs_gate3.db')) {
        echo "Database file doesn't exist, creating it now...\n";
        
        // Make sure the database directory exists
        if (!is_dir(BASE_PATH . '/database')) {
            mkdir(BASE_PATH . '/database', 0755, true);
        }
    }
    
    $db = Database::getGameInstance('baldurs_gate3');
    
    // Verify database connection by running a simple query
    $testQuery = $db->querySingle("PRAGMA table_info(npcs)");
    if ($testQuery === false) {
        throw new Exception("Database connection successful but couldn't query tables.");
    }
    
    echo "Connected to Baldur's Gate 3 database.\n";
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n" . 
        "Please ensure the database directory exists and is writable.\n");
}

/**
 * Import locations into database
 */
function importLocations($db) {
    echo "Importing locations...\n";
    
    // Clear existing locations data
    $db->exec("DELETE FROM locations");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='locations'");
    
    // Manual location data since there's no public API for BG3
    $locations = [
        [
            'id' => 'loc_wilderness_shore',
            'name' => 'Wilderness - Ravaged Beach',
            'description' => 'The location of the Nautiloid crash. The beginning of your adventure.',
            'region' => 'Act 1 - The Wilderness',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_wilderness_roadside',
            'name' => 'Wilderness - Roadside Cliffs',
            'description' => 'The elevated area above the beach, leading to the Druid Grove.',
            'region' => 'Act 1 - The Wilderness',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_wilderness_blighted_village',
            'name' => 'Blighted Village',
            'description' => 'A village overrun by goblins, home to an old apothecary and a cellar with dark secrets.',
            'region' => 'Act 1 - The Wilderness',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_druid_grove',
            'name' => 'Druid Grove',
            'description' => 'A sanctuary for Tiefling refugees, protected by druids of the Shadow Druids circle.',
            'region' => 'Act 1 - The Wilderness',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_goblin_camp',
            'name' => 'Goblin Camp',
            'description' => 'A former temple now occupied by the Absolute\'s goblin forces.',
            'region' => 'Act 1 - The Wilderness',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_underdark_entrance',
            'name' => 'Underdark Entrance',
            'description' => 'The entrance to the vast underground network known as the Underdark.',
            'region' => 'Act 1 - The Underdark',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_underdark_myconid_colony',
            'name' => 'Myconid Colony',
            'description' => 'A settlement of sentient mushroom creatures in the Underdark.',
            'region' => 'Act 1 - The Underdark',
            'parent_location_id' => 'loc_underdark_entrance'
        ],
        [
            'id' => 'loc_moonrise_towers',
            'name' => 'Moonrise Towers',
            'description' => 'A mysterious location controlled by the Absolute\'s followers.',
            'region' => 'Act 2 - Shadow-Cursed Lands',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_last_light_inn',
            'name' => 'Last Light Inn',
            'description' => 'A rare safe haven in the Shadow-Cursed Lands.',
            'region' => 'Act 2 - Shadow-Cursed Lands',
            'parent_location_id' => null
        ],
        [
            'id' => 'loc_city_baldurs_gate',
            'name' => 'Baldur\'s Gate City',
            'description' => 'The largest city on the Sword Coast, now troubled by various threats.',
            'region' => 'Act 3 - Baldur\'s Gate',
            'parent_location_id' => null
        ]
    ];
    
    $count = 0;
    foreach ($locations as $location) {
        // Insert location
        $stmt = $db->prepare(
            "INSERT INTO locations (
                location_id, name, description, region, 
                parent_location_id, coordinates, points_of_interest
            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $result = $db->execPrepared($stmt, [
            $location['id'],
            $location['name'],
            $location['description'],
            $location['region'],
            $location['parent_location_id'],
            null, // coordinates
            null  // points_of_interest
        ]);
        
        if ($result) {
            $count++;
            
            // Add to search index
            $keywords = $location['name'] . ' ' . $location['region'];
            $stmt = $db->prepare(
                "INSERT INTO search_index (
                    content_id, content_type, name, description, keywords
                ) VALUES (?, ?, ?, ?, ?)"
            );
            $db->execPrepared($stmt, [
                $location['id'],
                'location',
                $location['name'],
                $location['description'],
                $keywords
            ]);
        }
    }
    
    echo "Imported $count locations.\n";
}

/**
 * Import NPCs into database
 */
function importNPCs($db) {
    echo "Importing NPCs...\n";
    
    // Clear existing NPCs data
    $db->exec("DELETE FROM npcs");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='npcs'");
    
    // Manual NPC data
    $npcs = [
        [
            'id' => 'npc_shadowheart',
            'name' => 'Shadowheart',
            'description' => 'A reserved cleric of Shar with a forgotten past and a mysterious artifact.',
            'role' => 'Companion',
            'faction' => 'Shar Worshippers',
            'default_location' => 'loc_wilderness_shore',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'I serve the goddess Shar... though I remember little else about my past.'
        ],
        [
            'id' => 'npc_astarion',
            'name' => 'Astarion',
            'description' => 'A high elf vampire spawn with a taste for the finer things in life.',
            'role' => 'Companion',
            'faction' => 'None',
            'default_location' => 'loc_wilderness_shore',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'I\'ve been a slave to a vampire for two hundred years. Finally, I\'m free.'
        ],
        [
            'id' => 'npc_gale',
            'name' => 'Gale',
            'description' => 'A human wizard with a mysterious connection to the goddess of magic, Mystra.',
            'role' => 'Companion',
            'faction' => 'Mystra Worshippers',
            'default_location' => 'loc_wilderness_roadside',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'If we don\'t stop this parasite, all of Faerûn is doomed.'
        ],
        [
            'id' => 'npc_laezel',
            'name' => 'Lae\'zel',
            'description' => 'A fierce Githyanki warrior determined to rid herself of the parasite.',
            'role' => 'Companion',
            'faction' => 'Githyanki',
            'default_location' => 'loc_wilderness_shore',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'I am Githyanki. We do not surrender. We do not bend.'
        ],
        [
            'id' => 'npc_wyll',
            'name' => 'Wyll',
            'description' => 'The \'Blade of Frontiers\', a human warlock with a vendetta against goblins.',
            'role' => 'Companion',
            'faction' => 'The Flaming Fist',
            'default_location' => 'loc_druid_grove',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'The Blade of Frontiers, at your service.'
        ],
        [
            'id' => 'npc_halsin',
            'name' => 'Halsin',
            'description' => 'The leader of the druids at the Emerald Grove, a wise and powerful shapeshifter.',
            'role' => 'Quest Giver',
            'faction' => 'Emerald Grove Druids',
            'default_location' => 'loc_druid_grove',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'I am the First Druid of the Emerald Grove, guardian of the sacred shrine.'
        ],
        [
            'id' => 'npc_minthara',
            'name' => 'Minthara',
            'description' => 'A ruthless drow paladin serving the Absolute, leading the goblin raid against the grove.',
            'role' => 'Antagonist',
            'faction' => 'The Absolute',
            'default_location' => 'loc_goblin_camp',
            'is_hostile' => 1,
            'is_merchant' => 0,
            'dialogue' => 'Pledge yourself to the Absolute, or die like the rest.'
        ],
        [
            'id' => 'npc_volo',
            'name' => 'Volo',
            'description' => 'The famous traveling storyteller and author, Volothamp "Volo" Geddarm.',
            'role' => 'Quest Giver',
            'faction' => 'None',
            'default_location' => 'loc_druid_grove',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'Volothamp Geddarm, chronicler, wizard, and celebrity, at your service!'
        ],
        [
            'id' => 'npc_zevlor',
            'name' => 'Zevlor',
            'description' => 'The leader of the Tiefling refugees seeking shelter at the Emerald Grove.',
            'role' => 'Quest Giver',
            'faction' => 'Tiefling Refugees',
            'default_location' => 'loc_druid_grove',
            'is_hostile' => 0,
            'is_merchant' => 0,
            'dialogue' => 'The druids have given us sanctuary, but for how long?'
        ],
        [
            'id' => 'npc_auntie_ethel',
            'name' => 'Auntie Ethel',
            'description' => 'A seemingly kind old woman with a cottage in the woods, hiding a dark secret.',
            'role' => 'Antagonist',
            'faction' => 'Hags',
            'default_location' => 'loc_wilderness_roadside',
            'is_hostile' => 1,
            'is_merchant' => 1,
            'dialogue' => 'Come in, dearie. Let Auntie Ethel fix you a cup of tea.'
        ]
    ];
    
    $count = 0;
    foreach ($npcs as $npc) {
        // Insert NPC
        $stmt = $db->prepare(
            "INSERT INTO npcs (
                npc_id, name, description, role, faction,
                default_location_id, is_hostile, is_merchant, dialogue_summary
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $result = $db->execPrepared($stmt, [
            $npc['id'],
            $npc['name'],
            $npc['description'],
            $npc['role'],
            $npc['faction'],
            $npc['default_location'],
            $npc['is_hostile'],
            $npc['is_merchant'],
            $npc['dialogue']
        ]);
        
        if ($result) {
            $count++;
            
            // Link NPC to default location if provided
            if (!empty($npc['default_location'])) {
                $locStmt = $db->prepare("INSERT INTO npc_locations (npc_id, location_id) VALUES (?, ?)");
                $db->execPrepared($locStmt, [$npc['id'], $npc['default_location']]);
            }
            
            // Add to search index
            $keywords = $npc['name'] . ' ' . $npc['role'] . ' ' . $npc['faction'];
            $stmt = $db->prepare(
                "INSERT INTO search_index (
                    content_id, content_type, name, description, keywords
                ) VALUES (?, ?, ?, ?, ?)"
            );
            $db->execPrepared($stmt, [
                $npc['id'],
                'npc',
                $npc['name'],
                $npc['description'],
                $keywords
            ]);
        }
    }
    
    echo "Imported $count NPCs.\n";
}

/**
 * Import Items into database
 */
function importItems($db) {
    echo "Importing items...\n";
    
    // Clear existing items data
    $db->exec("DELETE FROM items");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='items'");
    
    // Manual item data
    $items = [
        [
            'id' => 'item_shortsword',
            'name' => 'Shortsword',
            'description' => 'A short blade, ideal for quick strikes. Deals piercing damage.',
            'type' => 'weapon',
            'subtype' => 'simple melee',
            'stats' => '{"damage":"1d6","damage_type":"piercing","weight":2}',
            'requirements' => null,
            'rarity' => 'common'
        ],
        [
            'id' => 'item_longbow',
            'name' => 'Longbow',
            'description' => 'A tall bow that requires significant strength to use effectively.',
            'type' => 'weapon',
            'subtype' => 'martial ranged',
            'stats' => '{"damage":"1d8","damage_type":"piercing","range":"150/600","weight":2}',
            'requirements' => null,
            'rarity' => 'common'
        ],
        [
            'id' => 'item_leather_armor',
            'name' => 'Leather Armor',
            'description' => 'A set of armor made from tough but flexible leather.',
            'type' => 'armor',
            'subtype' => 'light armor',
            'stats' => '{"ac":11,"weight":10}',
            'requirements' => null,
            'rarity' => 'common'
        ],
        [
            'id' => 'item_shield_of_faith',
            'name' => 'Shield of Faith',
            'description' => 'A shimmering field of protection surrounds a creature of your choice.',
            'type' => 'spell',
            'subtype' => 'abjuration',
            'stats' => '{"level":1,"casting_time":"1 bonus action","duration":"10 minutes"}',
            'requirements' => '{"class":"Cleric, Paladin"}',
            'rarity' => 'common'
        ],
        [
            'id' => 'item_fireball',
            'name' => 'Fireball',
            'description' => 'A bright streak flashes from your pointing finger to a point you choose and then blossoms with a low roar into an explosion of flame.',
            'type' => 'spell',
            'subtype' => 'evocation',
            'stats' => '{"level":3,"casting_time":"1 action","duration":"Instantaneous","damage":"8d6"}',
            'requirements' => '{"class":"Sorcerer, Wizard, Warlock"}',
            'rarity' => 'common'
        ],
        [
            'id' => 'item_potion_healing',
            'name' => 'Potion of Healing',
            'description' => 'A potion that restores 2d4+2 hit points when drunk.',
            'type' => 'consumable',
            'subtype' => 'potion',
            'stats' => '{"healing":"2d4+2"}',
            'requirements' => null,
            'rarity' => 'common'
        ],
        [
            'id' => 'item_wand_of_magic_missiles',
            'name' => 'Wand of Magic Missiles',
            'description' => 'This wand has 7 charges. While holding it, you can use an action to expend 1 or more of its charges to cast the Magic Missile spell.',
            'type' => 'magic item',
            'subtype' => 'wand',
            'stats' => '{"charges":7,"spell":"Magic Missile"}',
            'requirements' => null,
            'rarity' => 'uncommon'
        ],
        [
            'id' => 'item_scroll_revivify',
            'name' => 'Scroll of Revivify',
            'description' => 'A spell scroll that allows you to cast Revivify once, even if you don\'t know the spell.',
            'type' => 'consumable',
            'subtype' => 'scroll',
            'stats' => '{"level":3,"casting_time":"1 action"}',
            'requirements' => '{"class":"Cleric, Paladin, Druid"}',
            'rarity' => 'rare'
        ],
        [
            'id' => 'item_sword_of_justice',
            'name' => 'Sword of Justice',
            'description' => 'A magnificent greatsword that shines with divine light.',
            'type' => 'weapon',
            'subtype' => 'martial melee',
            'stats' => '{"damage":"2d6+1","damage_type":"slashing","bonus_damage":"1d8 radiant against fiends and undead"}',
            'requirements' => null,
            'rarity' => 'rare'
        ],
        [
            'id' => 'item_ring_protection',
            'name' => 'Ring of Protection',
            'description' => 'You gain a +1 bonus to AC and saving throws while wearing this ring.',
            'type' => 'magic item',
            'subtype' => 'ring',
            'stats' => '{"ac_bonus":1,"save_bonus":1}',
            'requirements' => null,
            'rarity' => 'rare'
        ]
    ];
    
    $count = 0;
    foreach ($items as $item) {
        // Insert item
        $stmt = $db->prepare(
            "INSERT INTO items (
                item_id, name, description, type, subtype,
                stats, requirements, rarity
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $result = $db->execPrepared($stmt, [
            $item['id'],
            $item['name'],
            $item['description'],
            $item['type'],
            $item['subtype'],
            $item['stats'] ? json_encode($item['stats']) : null,
            $item['requirements'] ? json_encode($item['requirements']) : null,
            $item['rarity']
        ]);
        
        if ($result) {
            $count++;
            
            // Add to search index
            $keywords = $item['name'] . ' ' . $item['type'] . ' ' . $item['subtype'] . ' ' . $item['rarity'];
            $stmt = $db->prepare(
                "INSERT INTO search_index (
                    content_id, content_type, name, description, keywords
                ) VALUES (?, ?, ?, ?, ?)"
            );
            $db->execPrepared($stmt, [
                $item['id'],
                'item',
                $item['name'],
                $item['description'],
                $keywords
            ]);
        }
    }
    
    echo "Imported $count items.\n";
}

/**
 * Import missable items from BG3 Missables repository
 */
function importMissableItems($db) {
    echo "Importing missable items...\n";
    
    try {
        // Fetch the checklist.md content from GitHub
        $missablesUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
        
        $response = fetchUrl($missablesUrl);
        
        if (!$response) {
            echo "Failed to fetch missable items data, skipping this step.\n";
            return;
        }
        
        // Parse the markdown to extract item data
        $lines = explode("\n", $response);
        $items = [];
        $currentAct = '';
        $currentLocation = '';
        
        // Define rarity mapping
        $rarityMap = [
            'item_common' => 'common',
            'item_uncommon' => 'uncommon',
            'item_rare' => 'rare',
            'item_veryrare' => 'very rare',
            'item_legendary' => 'legendary',
            'item_story' => 'story',
            'item_ordinary' => 'ordinary'
        ];
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Extract item information
            if (preg_match('/- ::(item_\w+):: \[(.*?)\]\((.*?)\)/', $line, $matches)) {
                $rarityCode = $matches[1];
                $itemName = $matches[2];
                $itemUrl = $matches[3];
                
                // Get description from the link if possible
                $description = "A missable item in Baldur's Gate 3.";
                
                // Create a unique ID for the item
                $itemId = 'item_' . md5($itemName);
                
                // Add to items array
                $items[] = [
                    'id' => $itemId,
                    'name' => $itemName,
                    'description' => $description,
                    'type' => 'Missable Item',
                    'subtype' => isset($rarityMap[$rarityCode]) ? ucfirst($rarityMap[$rarityCode]) : 'Unknown',
                    'rarity' => isset($rarityMap[$rarityCode]) ? $rarityMap[$rarityCode] : 'common',
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'url' => $itemUrl
                ];
            }
        }
        
        $count = 0;
        foreach ($items as $item) {
            // Check if item already exists
            $checkStmt = $db->prepare("SELECT item_id FROM items WHERE name = ?");
            $result = $db->execPrepared($checkStmt, [$item['name']]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Update the existing item with missable flag
                $updateStmt = $db->prepare("UPDATE items SET is_missable = 1, rarity = ? WHERE name = ?");
                $updateResult = $db->execPrepared($updateStmt, [$item['rarity'], $item['name']]);
                
                if ($updateResult) {
                    $count++;
                    echo "Updated existing item as missable: " . $item['name'] . "\n";
                }
            } else {
                // Insert new item
                $stmt = $db->prepare(
                    "INSERT INTO items (
                        item_id, name, description, type, subtype,
                        stats, requirements, effects, rarity, is_missable,
                        location_note
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $locationNote = "{$item['act']} - {$item['location']}";
                
                $result = $db->execPrepared($stmt, [
                    $item['id'],
                    $item['name'],
                    $item['description'],
                    $item['type'],
                    $item['subtype'],
                    null, // stats
                    null, // requirements
                    null, // effects
                    $item['rarity'],
                    1,    // is_missable
                    $locationNote
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $item['name'] . ' ' . $item['type'] . ' ' . $item['rarity'] . ' missable ' . $item['act'] . ' ' . $item['location'];
                    $stmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($stmt, [
                        $item['id'],
                        'item',
                        $item['name'],
                        $item['description'],
                        $keywords
                    ]);
                }
            }
        }
        
        echo "Imported/updated $count missable items.\n";
    } catch (Exception $e) {
        echo "Error importing missable items: " . $e->getMessage() . "\n";
    }
}

/**
 * Import missable quests from BG3 Missables repository
 */
function importMissableQuests($db) {
    echo "Importing missable quests...\n";
    
    try {
        // Fetch the checklist.md content from GitHub
        $missablesUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
        
        $response = fetchUrl($missablesUrl);
        
        if (!$response) {
            echo "Failed to fetch missable quests data, skipping this step.\n";
            return;
        }
        
        // Parse the markdown to extract quest data
        $lines = explode("\n", $response);
        $quests = [];
        $currentAct = '';
        $currentLocation = '';
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Extract missable quest information
            if (preg_match('/- ::missable:: (?:Act \d+ - )?(.+?)(?:\((.*?)\))?$/', $line, $matches) || 
                preg_match('/- ::missable:: (.+?)(?:\((.*?)\))?$/', $line, $matches)) {
                
                $questDescription = trim($matches[1]);
                
                // Check if there's a link in the description
                $questName = $questDescription;
                $questUrl = null;
                
                if (preg_match('/\[(.*?)\]\((.*?)\)/', $questDescription, $linkMatches)) {
                    $questName = $linkMatches[1];
                    $questUrl = $linkMatches[2];
                    $questDescription = str_replace($linkMatches[0], $questName, $questDescription);
                }
                
                // Create a unique ID for the quest
                $questId = 'quest_' . md5($questName);
                
                // Add to quests array
                $quests[] = [
                    'id' => $questId,
                    'name' => $questName,
                    'description' => $questDescription,
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'url' => $questUrl,
                    'time_sensitive' => true
                ];
            }
        }
        
        $count = 0;
        foreach ($quests as $quest) {
            // Check if quest already exists
            $checkStmt = $db->prepare("SELECT quest_id FROM quests WHERE name = ?");
            $result = $db->execPrepared($checkStmt, [$quest['name']]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($exists) {
                // Update the existing quest with time sensitive flag
                $updateStmt = $db->prepare("UPDATE quests SET time_sensitive = 1 WHERE name = ?");
                $updateResult = $db->execPrepared($updateStmt, [$quest['name']]);
                
                if ($updateResult) {
                    $count++;
                    echo "Updated existing quest as time-sensitive: " . $quest['name'] . "\n";
                }
            } else {
                // Insert new quest
                $stmt = $db->prepare(
                    "INSERT INTO quests (
                        quest_id, name, description, category, level,
                        prerequisites, time_sensitive, location_note, type
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $locationNote = "{$quest['act']} - {$quest['location']}";
                
                $result = $db->execPrepared($stmt, [
                    $quest['id'],
                    $quest['name'],
                    $quest['description'],
                    'Missable',
                    null, // level
                    null, // prerequisites
                    1,    // time_sensitive
                    $locationNote,
                    'Side Quest' // Default type for missable quests
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $quest['name'] . ' ' . 'missable quest time-sensitive ' . $quest['act'] . ' ' . $quest['location'];
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
                }
            }
        }
        
        echo "Imported/updated $count missable quests.\n";
    } catch (Exception $e) {
        echo "Error importing missable quests: " . $e->getMessage() . "\n";
    }
}

/**
 * Import general missable tasks from the bg3-missables website
 */
function importMissableTasks($db) {
    echo "Importing missable tasks...\n";
    
    try {
        // Fetch the checklist.md content from GitHub
        $missablesUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
        
        $response = fetchUrl($missablesUrl);
        
        if (!$response) {
            echo "Failed to fetch missable tasks data, skipping this step.\n";
            return;
        }
        
        // Parse the markdown to extract task data
        $lines = explode("\n", $response);
        $tasks = [];
        $currentAct = '';
        $currentLocation = '';
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Extract general task information (non-item, non-quest)
            if (preg_match('/- ::task:: (.*?)(?:\(|$)/', $line, $matches)) {
                $taskDescription = trim($matches[1]);
                
                // Skip general instructions
                if (strpos($line, '::task:: The following icon usage') !== false || 
                    strpos($line, '::task:: Tips for obtaining') !== false ||
                    strpos($line, '::task:: Various quests') !== false ||
                    strpos($line, '::task:: Some NPC') !== false ||
                    strpos($line, '::task:: It may be impossible') !== false) {
                    continue;
                }
                
                // Skip if this line is just a bullet point for another task
                if (strpos($line, '  - ::task::') === 0) {
                    continue;
                }
                
                // Check if there's a link in the description
                if (preg_match('/\[(.*?)\]\((.*?)\)/', $taskDescription, $linkMatches)) {
                    $linkedText = $linkMatches[1];
                    $url = $linkMatches[2];
                    
                    // Replace the markdown link with just the text
                    $taskDescription = str_replace($linkMatches[0], $linkedText, $taskDescription);
                }
                
                // Create a unique ID for the task
                $taskId = 'task_' . md5($taskDescription . $currentLocation . $currentAct);
                
                // Add to tasks array
                $tasks[] = [
                    'id' => $taskId,
                    'description' => $taskDescription,
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'is_missable' => 0  // Regular task
                ];
            }
            
            // Extract specifically missable tasks (time-sensitive)
            if (preg_match('/- ::missable:: (.*?)(?:\(|$)/', $line, $matches)) {
                $taskDescription = trim($matches[1]);
                
                // Skip if this is dealing with items or already captured quests
                if (strpos($line, '::item_') !== false) {
                    continue;
                }
                
                // Check if there's a link in the description
                if (preg_match('/\[(.*?)\]\((.*?)\)/', $taskDescription, $linkMatches)) {
                    $linkedText = $linkMatches[1];
                    $url = $linkMatches[2];
                    
                    // Replace the markdown link with just the text
                    $taskDescription = str_replace($linkMatches[0], $linkedText, $taskDescription);
                }
                
                // Create a unique ID for the task
                $taskId = 'task_' . md5($taskDescription . $currentLocation . $currentAct);
                
                // Add to tasks array with missable flag
                $tasks[] = [
                    'id' => $taskId,
                    'description' => $taskDescription,
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'is_missable' => 1  // Missable task
                ];
            }
        }
        
        // Create tasks table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS tasks (
                task_id TEXT PRIMARY KEY,
                description TEXT NOT NULL,
                location TEXT,
                act TEXT,
                is_missable INTEGER DEFAULT 0,
                completed INTEGER DEFAULT 0
            )
        ");
        
        // Insert tasks into database
        $count = 0;
        foreach ($tasks as $task) {
            // Check if task already exists
            $checkStmt = $db->prepare("SELECT task_id FROM tasks WHERE task_id = ?");
            $result = $db->execPrepared($checkStmt, [$task['id']]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$exists) {
                // Insert new task
                $stmt = $db->prepare(
                    "INSERT INTO tasks (
                        task_id, description, location, act, is_missable
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                
                $result = $db->execPrepared($stmt, [
                    $task['id'],
                    $task['description'],
                    $task['location'],
                    $task['act'],
                    $task['is_missable']
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $task['description'] . ' ' . 
                                ($task['is_missable'] ? 'missable time-sensitive' : 'task') . ' ' . 
                                $task['act'] . ' ' . $task['location'];
                    
                    $stmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($stmt, [
                        $task['id'],
                        'task',
                        $task['description'],
                        $task['description'],
                        $keywords
                    ]);
                }
            }
        }
        
        echo "Imported $count new missable tasks.\n";
    } catch (Exception $e) {
        echo "Error importing missable tasks: " . $e->getMessage() . "\n";
    }
}

/**
 * Import missable abilities from the bg3-missables website
 */
function importMissableAbilities($db) {
    echo "Importing missable abilities...\n";
    
    try {
        // Fetch the checklist.md content from GitHub
        $missablesUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
        
        $response = fetchUrl($missablesUrl);
        
        if (!$response) {
            echo "Failed to fetch missable abilities data, skipping this step.\n";
            return;
        }
        
        // Parse the markdown to extract ability data
        $lines = explode("\n", $response);
        $abilities = [];
        $currentAct = '';
        $currentLocation = '';
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Extract ability information
            if (preg_match('/- ::ability:: (.*?)(?:\(|$)/', $line, $matches)) {
                $abilityDescription = trim($matches[1]);
                
                // Check if there's a link in the description
                $abilityName = $abilityDescription;
                $abilityUrl = null;
                
                if (preg_match('/\[(.*?)\]\((.*?)\)/', $abilityDescription, $linkMatches)) {
                    $abilityName = $linkMatches[1];
                    $abilityUrl = $linkMatches[2];
                    $abilityDescription = str_replace($linkMatches[0], $abilityName, $abilityDescription);
                }
                
                // Create a unique ID for the ability
                $abilityId = 'ability_' . md5($abilityName);
                
                // Add to abilities array
                $abilities[] = [
                    'id' => $abilityId,
                    'name' => $abilityName,
                    'description' => $abilityDescription,
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'url' => $abilityUrl
                ];
            }
        }
        
        // Create abilities table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS abilities (
                ability_id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                location TEXT,
                act TEXT,
                url TEXT
            )
        ");
        
        // Insert abilities into database
        $count = 0;
        foreach ($abilities as $ability) {
            // Check if ability already exists
            $checkStmt = $db->prepare("SELECT ability_id FROM abilities WHERE name = ?");
            $result = $db->execPrepared($checkStmt, [$ability['name']]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$exists) {
                // Insert new ability
                $stmt = $db->prepare(
                    "INSERT INTO abilities (
                        ability_id, name, description, location, act, url
                    ) VALUES (?, ?, ?, ?, ?, ?)"
                );
                
                $result = $db->execPrepared($stmt, [
                    $ability['id'],
                    $ability['name'],
                    $ability['description'],
                    $ability['location'],
                    $ability['act'],
                    $ability['url']
                ]);
                
                if ($result) {
                    $count++;
                    
                    // Add to search index
                    $keywords = $ability['name'] . ' ' . $ability['description'] . ' ability missable ' . 
                                $ability['act'] . ' ' . $ability['location'];
                    
                    $stmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($stmt, [
                        $ability['id'],
                        'ability',
                        $ability['name'],
                        $ability['description'],
                        $keywords
                    ]);
                }
            }
        }
        
        echo "Imported $count new missable abilities.\n";
    } catch (Exception $e) {
        echo "Error importing missable abilities: " . $e->getMessage() . "\n";
    }
}

/**
 * Import missable merchant items from the bg3-missables website
 */
function importMerchantItems($db) {
    echo "Importing merchant items...\n";
    
    try {
        // Fetch the checklist.md content from GitHub
        $missablesUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
        
        $response = fetchUrl($missablesUrl);
        
        if (!$response) {
            echo "Failed to fetch merchant items data, skipping this step.\n";
            return;
        }
        
        // Parse the markdown to extract merchant items data
        $lines = explode("\n", $response);
        $merchants = [];
        $currentMerchant = null;
        $currentAct = '';
        $currentLocation = '';
        $inMerchantBlock = false;
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Detect merchant entries
            if (preg_match('/- ::task:: \[(.*?)\]\((.*?)\) offers merchant services/', $line, $matches)) {
                $inMerchantBlock = true;
                $merchantName = $matches[1];
                $merchantUrl = $matches[2];
                
                $currentMerchant = [
                    'name' => $merchantName,
                    'url' => $merchantUrl,
                    'location' => $currentLocation,
                    'act' => $currentAct,
                    'items' => []
                ];
                
                // Create a merchant ID
                $merchantId = 'npc_' . md5($merchantName);
                $currentMerchant['id'] = $merchantId;
                
                continue;
            }
            
            // End of merchant block
            if ($inMerchantBlock && (trim($line) === '' || strpos($line, '- ::task::') === 0) && strpos($line, 'merchant services') === false) {
                if ($currentMerchant && !empty($currentMerchant['items'])) {
                    $merchants[] = $currentMerchant;
                }
                $inMerchantBlock = false;
                $currentMerchant = null;
                continue;
            }
            
            // Extract merchant items
            if ($inMerchantBlock && preg_match('/- ::(item_\w+):: \[(.*?)\]\((.*?)\)/', $line, $matches)) {
                $rarityCode = $matches[1];
                $itemName = $matches[2];
                $itemUrl = $matches[3];
                
                // Define rarity mapping
                $rarityMap = [
                    'item_common' => 'common',
                    'item_uncommon' => 'uncommon',
                    'item_rare' => 'rare',
                    'item_veryrare' => 'very rare',
                    'item_legendary' => 'legendary',
                    'item_story' => 'story',
                    'item_ordinary' => 'ordinary'
                ];
                
                // Add to current merchant's items
                $currentMerchant['items'][] = [
                    'name' => $itemName,
                    'url' => $itemUrl,
                    'rarity' => isset($rarityMap[$rarityCode]) ? $rarityMap[$rarityCode] : 'unknown',
                    'item_id' => 'item_' . md5($itemName)
                ];
            }
            
            // Extract missable information for merchants
            if ($inMerchantBlock && preg_match('/- ::missable:: (.*?)(?:\(|$)/', $line, $matches)) {
                if ($currentMerchant) {
                    $currentMerchant['is_missable'] = true;
                    $currentMerchant['missable_note'] = trim($matches[1]);
                }
            }
        }
        
        // Create merchant_items table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS merchant_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                merchant_id TEXT NOT NULL,
                item_id TEXT NOT NULL,
                is_missable INTEGER DEFAULT 0,
                act TEXT,
                location TEXT,
                FOREIGN KEY (merchant_id) REFERENCES npcs(npc_id),
                FOREIGN KEY (item_id) REFERENCES items(item_id)
            )
        ");
        
        // Insert merchants and their items
        $merchantCount = 0;
        $itemCount = 0;
        
        foreach ($merchants as $merchant) {
            // Check if merchant exists in NPCs table
            $checkStmt = $db->prepare("SELECT npc_id FROM npcs WHERE name = ?");
            $result = $db->execPrepared($checkStmt, [$merchant['name']]);
            $exists = $result->fetchArray(SQLITE3_ASSOC);
            
            $merchantId = $exists ? $exists['npc_id'] : $merchant['id'];
            
            // If merchant doesn't exist, create a new NPC entry
            if (!$exists) {
                $merchantStmt = $db->prepare(
                    "INSERT INTO npcs (
                        npc_id, name, description, role, 
                        default_location_id, is_merchant
                    ) VALUES (?, ?, ?, ?, ?, ?)"
                );
                
                // Find location ID from location name
                $locStmt = $db->prepare("SELECT location_id FROM locations WHERE name LIKE ? LIMIT 1");
                $locResult = $db->execPrepared($locStmt, ['%' . $merchant['location'] . '%']);
                $locData = $locResult->fetchArray(SQLITE3_ASSOC);
                $locationId = $locData ? $locData['location_id'] : null;
                
                $description = "A merchant in Baldur's Gate 3 who can be found in {$merchant['location']}.";
                if (isset($merchant['is_missable']) && $merchant['is_missable']) {
                    $description .= " This merchant is missable: {$merchant['missable_note']}";
                }
                
                $db->execPrepared($merchantStmt, [
                    $merchantId,
                    $merchant['name'],
                    $description,
                    'Merchant',
                    $locationId,
                    1 // is_merchant = true
                ]);
                
                $merchantCount++;
                
                // Add to search index
                $keywords = $merchant['name'] . ' merchant ' . $merchant['location'] . ' ' . $merchant['act'];
                if (isset($merchant['is_missable']) && $merchant['is_missable']) {
                    $keywords .= ' missable';
                }
                
                $searchStmt = $db->prepare(
                    "INSERT INTO search_index (
                        content_id, content_type, name, description, keywords
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                $db->execPrepared($searchStmt, [
                    $merchantId,
                    'npc',
                    $merchant['name'],
                    $description,
                    $keywords
                ]);
            }
            
            // Process merchant items
            foreach ($merchant['items'] as $item) {
                // Check if item exists
                $checkItemStmt = $db->prepare("SELECT item_id FROM items WHERE name = ?");
                $itemResult = $db->execPrepared($checkItemStmt, [$item['name']]);
                $itemExists = $itemResult->fetchArray(SQLITE3_ASSOC);
                
                $itemId = $itemExists ? $itemExists['item_id'] : $item['item_id'];
                
                // If item doesn't exist, create it
                if (!$itemExists) {
                    $itemStmt = $db->prepare(
                        "INSERT INTO items (
                            item_id, name, description, type,
                            rarity, is_missable, location_note
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    
                    $itemDescription = "An item sold by {$merchant['name']} in {$merchant['location']}.";
                    if (isset($merchant['is_missable']) && $merchant['is_missable']) {
                        $itemDescription .= " This item is missable because the merchant may become unavailable.";
                    }
                    
                    $db->execPrepared($itemStmt, [
                        $itemId,
                        $item['name'],
                        $itemDescription,
                        'Merchant Item',
                        $item['rarity'],
                        isset($merchant['is_missable']) ? 1 : 0,
                        $merchant['act'] . ' - ' . $merchant['location']
                    ]);
                    
                    // Add to search index
                    $itemKeywords = $item['name'] . ' ' . $item['rarity'] . ' merchant item ' . 
                                    $merchant['name'] . ' ' . $merchant['location'];
                    
                    $searchStmt = $db->prepare(
                        "INSERT INTO search_index (
                            content_id, content_type, name, description, keywords
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    $db->execPrepared($searchStmt, [
                        $itemId,
                        'item',
                        $item['name'],
                        $itemDescription,
                        $itemKeywords
                    ]);
                }
                
                // Link item to merchant
                $checkMerchantItemStmt = $db->prepare(
                    "SELECT id FROM merchant_items WHERE merchant_id = ? AND item_id = ?"
                );
                $merchantItemResult = $db->execPrepared(
                    $checkMerchantItemStmt, 
                    [$merchantId, $itemId]
                );
                $merchantItemExists = $merchantItemResult->fetchArray(SQLITE3_ASSOC);
                
                if (!$merchantItemExists) {
                    $merchantItemStmt = $db->prepare(
                        "INSERT INTO merchant_items (
                            merchant_id, item_id, is_missable, act, location
                        ) VALUES (?, ?, ?, ?, ?)"
                    );
                    
                    $db->execPrepared($merchantItemStmt, [
                        $merchantId,
                        $itemId,
                        isset($merchant['is_missable']) ? 1 : 0,
                        $merchant['act'],
                        $merchant['location']
                    ]);
                    
                    $itemCount++;
                }
            }
        }
        
        echo "Imported $merchantCount new merchants with $itemCount items.\n";
    } catch (Exception $e) {
        echo "Error importing merchant items: " . $e->getMessage() . "\n";
    }
}

/**
 * Import companion approval information from the bg3-missables repository
 */
function importCompanionApproval($db) {
    echo "Importing companion approval data...\n";
    
    try {
        // Try to fetch companion-specific data from GitHub repo
        $approvalUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/companion_approval.md";
        
        $response = fetchUrl($approvalUrl);
        
        // If companion_approval.md doesn't exist, fallback to main checklist
        if (!$response || strpos($response, "404: Not Found") !== false) {
            echo "Companion approval file not found, extracting from main checklist instead.\n";
            $approvalUrl = "https://raw.githubusercontent.com/plasticmacaroni/bg3-missables/main/checklist.md";
            
            $response = fetchUrl($approvalUrl);
            
            if (!$response) {
                echo "Failed to fetch companion approval data, skipping this step.\n";
                return;
            }
        }
        
        // Create companion_events table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS companion_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id TEXT NOT NULL,
                companion_id TEXT NOT NULL,
                description TEXT NOT NULL,
                location TEXT,
                act TEXT,
                effect TEXT,
                approval_change TEXT,
                is_missable INTEGER DEFAULT 0,
                FOREIGN KEY (companion_id) REFERENCES npcs(npc_id)
            )
        ");
        
        // Parse the markdown to extract companion approval data
        $lines = explode("\n", $response);
        $events = [];
        $currentAct = '';
        $currentLocation = '';
        $companionMap = [
            'Astarion' => 'npc_astarion',
            'Gale' => 'npc_gale',
            'Karlach' => 'npc_karlach',
            'Lae\'zel' => 'npc_laezel',
            'Shadowheart' => 'npc_shadowheart',
            'Wyll' => 'npc_wyll',
            'Minthara' => 'npc_minthara',
            'Halsin' => 'npc_halsin'
        ];
        
        // Add Karlach and Minthara to NPCs if they don't exist
        $karlachCheck = $db->querySingle("SELECT npc_id FROM npcs WHERE npc_id = 'npc_karlach'");
        if (!$karlachCheck) {
            $db->exec("
                INSERT INTO npcs (
                    npc_id, name, description, role, faction, default_location_id, is_hostile, is_merchant, dialogue_summary
                ) VALUES (
                    'npc_karlach', 
                    'Karlach', 
                    'A tiefling fighter with an infernal engine for a heart, running from hellish hunters.', 
                    'Companion', 
                    'None',
                    'loc_wilderness_roadside',
                    0, 
                    0,
                    'My heart is a cursed infernal engine, but I don\'t let that stop me from living life to the fullest.'
                )
            ");
            
            // Add to search index
            $stmt = $db->prepare(
                "INSERT INTO search_index (
                    content_id, content_type, name, description, keywords
                ) VALUES (?, ?, ?, ?, ?)"
            );
            $db->execPrepared($stmt, [
                'npc_karlach',
                'npc',
                'Karlach',
                'A tiefling fighter with an infernal engine for a heart, running from hellish hunters.',
                'Karlach tiefling companion fighter Act 1'
            ]);
            
            echo "Added missing companion Karlach to database.\n";
        }
        
        // Add Minthara if she doesn't exist (she's already in the original import but as a check)
        $mintharaCheck = $db->querySingle("SELECT npc_id FROM npcs WHERE npc_id = 'npc_minthara'");
        if (!$mintharaCheck) {
            // Minthara is already imported in importNPCs function, this is just a check
            echo "Minthara is already imported, skipping.\n";
        }
        
        foreach ($lines as $line) {
            // Extract act information
            if (preg_match('/^# (Act \d+.*)/', $line, $matches)) {
                $currentAct = $matches[1];
                continue;
            }
            
            // Extract location information
            if (preg_match('/^# (.*)$/', $line, $matches) && $matches[1] != 'Getting Started (Feel Free to Check These Off)') {
                $currentLocation = $matches[1];
                continue;
            }
            
            // Look for companion approval patterns - this requires some adaptation based on the actual format
            if (preg_match('/- ::approval:: (.+?)(?:\s*\((.*?)\))?$/', $line, $matches) || 
                preg_match('/- ::(?:task|missable):: (.+?)(?:approves|disapproves|likes|dislikes|preferred|preference)/', $line, $matches)) {
                
                $description = trim($matches[1]);
                $approvalInfo = isset($matches[2]) ? $matches[2] : '';
                
                // Try to identify which companion(s) this affects
                $affectedCompanions = [];
                
                foreach ($companionMap as $name => $id) {
                    if (stripos($line, $name) !== false) {
                        $affectedCompanions[$id] = [
                            'name' => $name,
                            'effect' => strpos($line, 'disapprove') !== false || strpos($line, 'dislike') !== false 
                                      ? 'negative' 
                                      : (strpos($line, 'approve') !== false || strpos($line, 'like') !== false 
                                         ? 'positive' 
                                         : 'unknown')
                        ];
                    }
                }
                
                // If no specific companions were mentioned but approval is mentioned
                if (empty($affectedCompanions) && 
                    (strpos($line, 'approve') !== false || strpos($line, 'like') !== false || 
                     strpos($line, 'companion') !== false)) {
                    
                    // Generic approval event affecting multiple companions
                    $affectedCompanions['generic'] = [
                        'name' => 'Multiple companions',
                        'effect' => 'varies'
                    ];
                }
                
                if (!empty($affectedCompanions)) {
                    // Create a unique event ID
                    $eventId = 'event_' . md5($description . $currentLocation . $currentAct);
                    
                    // Is this event missable?
                    $isMissable = strpos($line, '::missable::') !== false ? 1 : 0;
                    
                    // Add to events array
                    $events[] = [
                        'id' => $eventId,
                        'description' => $description,
                        'location' => $currentLocation,
                        'act' => $currentAct,
                        'companions' => $affectedCompanions,
                        'approval_info' => $approvalInfo,
                        'is_missable' => $isMissable
                    ];
                }
            }
        }
        
        // Insert events into database
        $count = 0;
        foreach ($events as $event) {
            foreach ($event['companions'] as $companionId => $companionData) {
                // Skip generic events for now as they need special handling
                if ($companionId === 'generic') {
                    continue;
                }
                
                // Check if companion exists in the database
                if ($companionId !== 'multiple') {
                    $companionExists = $db->querySingle("SELECT npc_id FROM npcs WHERE npc_id = '$companionId'");
                    if (!$companionExists) {
                        echo "Warning: Companion $companionId not found in database, skipping event.\n";
                        continue;
                    }
                }
                
                // Check if the event for this companion already exists
                $checkStmt = $db->prepare(
                    "SELECT id FROM companion_events WHERE event_id = ? AND companion_id = ?"
                );
                $result = $db->execPrepared($checkStmt, [$event['id'], $companionId]);
                $exists = $result->fetchArray(SQLITE3_ASSOC);
                
                if (!$exists) {
                    // Insert new event
                    $stmt = $db->prepare(
                        "INSERT INTO companion_events (
                            event_id, companion_id, description, location, act,
                            effect, approval_change, is_missable
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    
                    $result = $db->execPrepared($stmt, [
                        $event['id'],
                        $companionId,
                        $event['description'],
                        $event['location'],
                        $event['act'],
                        $companionData['effect'],
                        $event['approval_info'],
                        $event['is_missable']
                    ]);
                    
                    if ($result) {
                        $count++;
                        
                        // Add to search index
                        $keywords = $event['description'] . ' ' . $companionData['name'] . ' companion approval ' . 
                                    $companionData['effect'] . ' ' . $event['act'] . ' ' . $event['location'];
                        
                        if ($event['is_missable']) {
                            $keywords .= ' missable';
                        }
                        
                        $searchStmt = $db->prepare(
                            "INSERT INTO search_index (
                                content_id, content_type, name, description, keywords
                            ) VALUES (?, ?, ?, ?, ?)"
                        );
                        $db->execPrepared($searchStmt, [
                            $event['id'] . '_' . $companionId,
                            'companion_event',
                            $companionData['name'] . ' - ' . $event['description'],
                            $event['description'],
                            $keywords
                        ]);
                    }
                }
            }
            
            // Handle generic events affecting multiple companions
            if (isset($event['companions']['generic'])) {
                // Create a unified entry for the generic event
                $checkStmt = $db->prepare(
                    "SELECT id FROM companion_events WHERE event_id = ? AND companion_id = ?"
                );
                $result = $db->execPrepared($checkStmt, [$event['id'], 'multiple']);
                $exists = $result->fetchArray(SQLITE3_ASSOC);
                
                if (!$exists) {
                    $stmt = $db->prepare(
                        "INSERT INTO companion_events (
                            event_id, companion_id, description, location, act,
                            effect, approval_change, is_missable
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    
                    $result = $db->execPrepared($stmt, [
                        $event['id'],
                        'multiple',
                        $event['description'],
                        $event['location'],
                        $event['act'],
                        'varies',
                        $event['approval_info'],
                        $event['is_missable']
                    ]);
                    
                    if ($result) {
                        $count++;
                        
                        // Add to search index
                        $keywords = $event['description'] . ' companion approval multiple companions ' . 
                                    $event['act'] . ' ' . $event['location'];
                        
                        if ($event['is_missable']) {
                            $keywords .= ' missable';
                        }
                        
                        $searchStmt = $db->prepare(
                            "INSERT INTO search_index (
                                content_id, content_type, name, description, keywords
                            ) VALUES (?, ?, ?, ?, ?)"
                        );
                        $db->execPrepared($searchStmt, [
                            $event['id'] . '_multiple',
                            'companion_event',
                            'Multiple Companions - ' . $event['description'],
                            $event['description'],
                            $keywords
                        ]);
                    }
                }
            }
        }
        
        echo "Imported $count companion approval events.\n";
    } catch (Exception $e) {
        echo "Error importing companion approval data: " . $e->getMessage() . "\n";
    }
}

/**
 * Import Quests into database
 */
function importQuests($db) {
    echo "Importing quests...\n";
    
    // Clear existing quests data
    $db->exec("DELETE FROM quests");
    $db->exec("DELETE FROM quest_steps");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='quests'");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='quest_steps'");
    
    // Manual quest data
    $quests = [
        [
            'id' => 'quest_main_absolute',
            'name' => 'The Illithid Tadpole',
            'description' => 'Find a way to remove the Mind Flayer parasite from your brain before it transforms you into a Mind Flayer.',
            'type' => 'main',
            'is_main_story' => 1,
            'difficulty' => 'medium',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'Escape the Nautiloid',
                    'description' => 'Escape from the Nautiloid ship as it crashes to the ground.',
                    'objective' => 'Find a way off the ship before it crashes',
                    'hints' => 'Navigate through the ship, defeat the imps and make your way to the helm.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Survival on the Beach',
                    'description' => 'Survive the crash and gather your bearings.',
                    'objective' => 'Explore the crash site and find other survivors',
                    'hints' => 'Look for survivors among the wreckage. Lae\'zel may be nearby.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'Seek a Healer',
                    'description' => 'Find someone who might be able to help with the parasite.',
                    'objective' => 'Look for a healer who might understand Mind Flayer parasites',
                    'hints' => 'The nearby Druid Grove may have healers, or there might be Githyanki who know about mind flayers.'
                ]
            ]
        ],
        [
            'id' => 'quest_druid_grove',
            'name' => 'The Druid Grove',
            'description' => 'Help resolve the conflict between the refugees and druids at the Emerald Grove.',
            'type' => 'side',
            'is_main_story' => 0,
            'difficulty' => 'medium',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'Enter the Grove',
                    'description' => 'Find and enter the secret Druid Grove.',
                    'objective' => 'Locate and gain entry to the Druid Grove',
                    'hints' => 'Follow the path north from the crash site. The entrance is guarded by druids.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Tiefling Troubles',
                    'description' => 'Learn about the tension between the Tiefling refugees and the druids.',
                    'objective' => 'Speak with Zevlor and Halsin about the situation',
                    'hints' => 'Zevlor leads the Tieflings. Halsin is the leader of the druids, but he\'s missing.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'Goblin Threat',
                    'description' => 'Deal with the goblin camp threatening the grove.',
                    'objective' => 'Find and deal with the goblin camp',
                    'hints' => 'The goblin camp is located to the east. You can either ally with them or fight them.'
                ],
                [
                    'step_number' => 4,
                    'title' => 'Save or Betray',
                    'description' => 'Decide the fate of the Druid Grove.',
                    'objective' => 'Choose to defend the grove or help the goblins raid it',
                    'hints' => 'Your decision will have major consequences for both the druids and the refugees.'
                ]
            ]
        ],
        [
            'id' => 'quest_find_halsin',
            'name' => 'Find the Missing Archdruid',
            'description' => 'Locate Halsin, the missing leader of the Emerald Grove druids.',
            'type' => 'side',
            'is_main_story' => 0,
            'difficulty' => 'hard',
            'steps' => [
                [
                    'step_number' => 1,
                    'title' => 'The Missing Leader',
                    'description' => 'Learn about Halsin\'s disappearance from Rath at the Druid Grove.',
                    'objective' => 'Speak with Rath about Halsin',
                    'hints' => 'Rath can be found near the central chamber of the Druid Grove.'
                ],
                [
                    'step_number' => 2,
                    'title' => 'Investigating the Goblin Camp',
                    'description' => 'Discover that Halsin may have been captured by goblins.',
                    'objective' => 'Find information about Halsin at the goblin camp',
                    'hints' => 'Infiltrate the goblin camp east of the grove. You might find clues by talking to prisoners or overhearing conversations.'
                ],
                [
                    'step_number' => 3,
                    'title' => 'The Bear in the Cage',
                    'description' => 'Find Halsin imprisoned in the goblin camp in bear form.',
                    'objective' => 'Locate and free Halsin',
                    'hints' => 'Check the prison area in the goblin camp. Look for a caged bear.'
                ],
                [
                    'step_number' => 4,
                    'title' => 'Return to the Grove',
                    'description' => 'Return with Halsin to the Druid Grove.',
                    'objective' => 'Escort Halsin back to the Druid Grove',
                    'hints' => 'Once freed, Halsin will either follow you back or make his own way to the grove.'
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
            $keywords = $quest['name'] . ' ' . $quest['type'] . ' ' . ($quest['is_main_story'] ? 'main story' : 'side quest');
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
            
            // Add quest steps
            $stepCount = 0;
            foreach ($quest['steps'] as $step) {
                // Generate a unique step ID if not provided
                $stepId = $step['id'] ?? $quest['id'] . '_step' . $step['step_number'];
                
                $stepStmt = $db->prepare(
                    "INSERT INTO quest_steps (
                        step_id, quest_id, step_number, title,
                        description, objective, hints, location_id,
                        spoiler_level
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $db->execPrepared($stepStmt, [
                    $stepId,
                    $quest['id'],
                    $step['step_number'],
                    $step['title'],
                    $step['description'],
                    $step['objective'],
                    $step['hints'],
                    $step['location_id'] ?? null,
                    $quest['spoiler_level'] ?? 0
                ]);
                
                $stepCount++;
            }
        }
    }
    
    echo "Imported $count quests with their steps.\n";
}

// After the database connection setup, add this helper function:

/**
 * Helper function to fetch URL content with error handling and retry logic
 * 
 * @param string $url The URL to fetch
 * @param int $maxRetries Maximum number of retries on failure
 * @param int $retryDelay Seconds to wait between retries
 * @return string|false Content of the URL or false on failure
 */
function fetchUrl($url, $maxRetries = 3, $retryDelay = 2) {
    echo "Fetching data from: $url\n";
    
    $retries = 0;
    while ($retries <= $maxRetries) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => 'Baldurs-Gate-3-Importer/1.0',
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        // If successful, return the response
        if (!$err && $httpCode == 200) {
            return $response;
        }
        
        // If rate limited, wait and retry
        if ($httpCode == 429) {
            $waitTime = $retryDelay * pow(2, $retries);
            echo "Rate limited by GitHub API. Waiting $waitTime seconds before retrying...\n";
            sleep($waitTime);
            $retries++;
            continue;
        }
        
        // If not found, return false immediately
        if ($httpCode == 404) {
            echo "Resource not found (404): $url\n";
            return false;
        }
        
        // For other errors, retry with backoff
        if ($retries < $maxRetries) {
            $waitTime = $retryDelay * pow(2, $retries);
            echo "Error fetching URL (HTTP $httpCode). Retrying in $waitTime seconds...\n";
            if ($err) {
                echo "cURL Error: $err\n";
            }
            sleep($waitTime);
            $retries++;
        } else {
            echo "Failed to fetch URL after $maxRetries retries. Last error: ";
            echo $err ? "cURL Error: $err\n" : "HTTP Code: $httpCode\n";
            return false;
        }
    }
    
    return false;
}

// Run the import functions
try {
    echo "Starting Baldur's Gate 3 data import...\n";
    
    // Ensure database schema exists
    ensureDatabaseSchema($db);
    
    // Start transaction
    $db->exec("BEGIN TRANSACTION");
    
    // Clear search index
    $db->exec("DELETE FROM search_index");
    
    // Import data
    importLocations($db);
    importItems($db);
    importMissableItems($db);
    importNPCs($db);
    importQuests($db);
    importMissableQuests($db);
    importMissableTasks($db);
    importMissableAbilities($db);
    importMerchantItems($db);
    importCompanionApproval($db);
    
    // Commit transaction
    $db->exec("COMMIT");
    
    echo "Baldur's Gate 3 data import completed successfully!\n";
} catch (Exception $e) {
    // Rollback transaction in case of error
    $db->exec("ROLLBACK");
    echo "Error during import: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Ensures all required database tables exist
 * 
 * @param SQLite3 $db Database connection
 */
function ensureDatabaseSchema($db) {
    echo "Ensuring database schema exists...\n";
    
    // Create search_index table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS search_index (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_id TEXT NOT NULL,
            content_type TEXT NOT NULL,
            name TEXT,
            description TEXT,
            keywords TEXT,
            UNIQUE(content_id, content_type)
        )
    ");
    
    // Create locations table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            region TEXT,
            parent_location_id TEXT,
            coordinates TEXT,
            points_of_interest TEXT
        )
    ");
    
    // Create NPCs table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS npcs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            npc_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            role TEXT,
            faction TEXT,
            default_location_id TEXT,
            is_hostile INTEGER DEFAULT 0,
            is_merchant INTEGER DEFAULT 0,
            dialogue_summary TEXT,
            FOREIGN KEY (default_location_id) REFERENCES locations(location_id)
        )
    ");
    
    // Create npc_locations table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS npc_locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            npc_id TEXT NOT NULL,
            location_id TEXT NOT NULL,
            FOREIGN KEY (npc_id) REFERENCES npcs(npc_id),
            FOREIGN KEY (location_id) REFERENCES locations(location_id)
        )
    ");
    
    // Create items table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            type TEXT,
            subtype TEXT,
            stats TEXT,
            requirements TEXT,
            effects TEXT,
            rarity TEXT,
            is_missable INTEGER DEFAULT 0,
            location_note TEXT
        )
    ");
    
    // Create quests table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS quests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quest_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            category TEXT,
            type TEXT,
            is_main_story INTEGER DEFAULT 0,
            time_sensitive INTEGER DEFAULT 0,
            difficulty TEXT,
            level TEXT,
            prerequisites TEXT,
            location_note TEXT
        )
    ");
    
    // Create quest_steps table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS quest_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            step_id TEXT UNIQUE NOT NULL,
            quest_id TEXT NOT NULL,
            step_number INTEGER,
            title TEXT,
            description TEXT,
            objective TEXT,
            hints TEXT,
            location_id TEXT,
            spoiler_level INTEGER DEFAULT 0,
            FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
            FOREIGN KEY (location_id) REFERENCES locations(location_id)
        )
    ");
    
    // Create other tables as needed
    $db->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            task_id TEXT PRIMARY KEY,
            description TEXT NOT NULL,
            location TEXT,
            act TEXT,
            is_missable INTEGER DEFAULT 0,
            completed INTEGER DEFAULT 0
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS abilities (
            ability_id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            location TEXT,
            act TEXT,
            url TEXT
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS merchant_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            merchant_id TEXT NOT NULL,
            item_id TEXT NOT NULL,
            is_missable INTEGER DEFAULT 0,
            act TEXT,
            location TEXT,
            FOREIGN KEY (merchant_id) REFERENCES npcs(npc_id),
            FOREIGN KEY (item_id) REFERENCES items(item_id)
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS companion_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL,
            companion_id TEXT NOT NULL,
            description TEXT NOT NULL,
            location TEXT,
            act TEXT,
            effect TEXT,
            approval_change TEXT,
            is_missable INTEGER DEFAULT 0,
            FOREIGN KEY (companion_id) REFERENCES npcs(npc_id)
        )
    ");
    
    echo "Database schema verified.\n";
} 