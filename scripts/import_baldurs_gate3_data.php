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
    $db = Database::getGameInstance('baldurs_gate3');
    echo "Connected to Baldur's Gate 3 database.\n";
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
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

// Run the import functions
try {
    echo "Starting Baldur's Gate 3 data import...\n";
    
    // Start transaction
    $db->exec("BEGIN TRANSACTION");
    
    // Clear search index
    $db->exec("DELETE FROM search_index");
    
    // Import data
    importLocations($db);
    importNPCs($db);
    importItems($db);
    importQuests($db);
    
    // Commit transaction
    $db->exec("COMMIT");
    
    echo "Baldur's Gate 3 data import completed successfully!\n";
} catch (Exception $e) {
    // Rollback transaction in case of error
    $db->exec("ROLLBACK");
    echo "Error during import: " . $e->getMessage() . "\n";
} 