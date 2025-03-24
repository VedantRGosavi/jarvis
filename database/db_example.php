<?php
/**
 * Database Interaction Example Script
 *
 * This script demonstrates how to interact with the SQLite databases
 * for the Gaming Companion Overlay Tool.
 *
 * It shows how to use both the system database and game-specific databases
 * according to our new multi-database architecture.
 */

// Base path to database files
define('BASE_PATH', dirname(__DIR__));

// Function to run examples
function runExamples() {
    echo "=== Gaming Companion Overlay Tool Database Examples ===\n\n";

    // System database examples
    echo "=== SYSTEM DATABASE EXAMPLES ===\n";

    // Create a new user
    echo "Creating a test user...\n";

    // Using the Database utility class for system database
    $systemDb = Database::getSystemInstance();

    try {
        $stmt = $systemDb->prepare("
            INSERT INTO users (name, email, password, created_at)
            VALUES (:name, :email, :password, :created_at)
        ");

        $name = 'Test User';
        $email = 'test@example.com';
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
        $stmt->bindValue(':created_at', $createdAt, SQLITE3_TEXT);

        $result = $stmt->execute();

        $userId = $systemDb->db->lastInsertRowID();
        echo "User created with ID: $userId\n";

        // Update user settings
        $settings = [
            'position' => 'top-right',
            'size' => 'medium',
            'opacity' => 0.75,
            'theme' => 'dark',
            'show_spoilers' => 0
        ];

        // Check if settings already exist
        $existingSettings = $systemDb->fetchOne("
            SELECT id FROM user_settings
            WHERE user_id = :user_id
        ", [':user_id' => $userId]);

        $updatedAt = date('Y-m-d H:i:s');

        if ($existingSettings) {
            // Update existing settings
            $systemDb->fetchAll("
                UPDATE user_settings
                SET overlay_position = :position,
                    overlay_size = :size,
                    overlay_opacity = :opacity,
                    theme = :theme,
                    show_spoilers = :spoilers,
                    updated_at = :updated_at
                WHERE user_id = :user_id
            ", [
                ':user_id' => $userId,
                ':position' => $settings['position'],
                ':size' => $settings['size'],
                ':opacity' => $settings['opacity'],
                ':theme' => $settings['theme'],
                ':spoilers' => $settings['show_spoilers'],
                ':updated_at' => $updatedAt
            ]);
        } else {
            // Insert new settings
            $systemDb->fetchAll("
                INSERT INTO user_settings (
                    user_id, overlay_position, overlay_size, overlay_opacity,
                    theme, show_spoilers, created_at, updated_at
                )
                VALUES (
                    :user_id, :position, :size, :opacity,
                    :theme, :spoilers, :created_at, :updated_at
                )
            ", [
                ':user_id' => $userId,
                ':position' => $settings['position'],
                ':size' => $settings['size'],
                ':opacity' => $settings['opacity'],
                ':theme' => $settings['theme'],
                ':spoilers' => $settings['show_spoilers'],
                ':created_at' => $updatedAt,
                ':updated_at' => $updatedAt
            ]);
        }

        echo "User settings updated successfully\n";

        // Get user by email
        $user = $systemDb->fetchOne("
            SELECT id, name, email, created_at, last_login, subscription_status
            FROM users
            WHERE email = :email
        ", [':email' => 'test@example.com']);

        if ($user) {
            echo "Retrieved user: " . $user['name'] . " (" . $user['email'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Error with system database: " . $e->getMessage() . "\n";
    }

    // Game database examples
    echo "\n=== ELDEN RING DATABASE EXAMPLES ===\n";

    // Create Elden Ring database instance
    $eldenRingDb = Database::getGameInstance('elden_ring');

    // Add a test location
    echo "Adding a test location...\n";
    $locationData = [
        'location_id' => 'loc_limgrave_chapel',
        'name' => 'Church of Elleh',
        'description' => 'A ruined church in Limgrave, close to where the Tarnished first enters the open world.',
        'region' => 'Limgrave',
        'coordinates' => '{"x": 234, "y": 121}',
        'points_of_interest' => 'Site of Grace, Merchant Kale',
        'difficulty_level' => 'beginner',
        'recommended_level' => '1-10'
    ];

    try {
        $stmt = $eldenRingDb->prepare("
            INSERT INTO locations (
                location_id, name, description, region, parent_location_id,
                coordinates, points_of_interest, connected_locations,
                difficulty_level, recommended_level, notable_items, notable_npcs
            )
            VALUES (
                :location_id, :name, :description, :region, :parent_location_id,
                :coordinates, :points_of_interest, :connected_locations,
                :difficulty_level, :recommended_level, :notable_items, :notable_npcs
            )
        ");

        $stmt->bindValue(':location_id', $locationData['location_id'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $locationData['name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $locationData['description'], SQLITE3_TEXT);
        $stmt->bindValue(':region', $locationData['region'], SQLITE3_TEXT);
        $stmt->bindValue(':parent_location_id', null, SQLITE3_TEXT);
        $stmt->bindValue(':coordinates', $locationData['coordinates'], SQLITE3_TEXT);
        $stmt->bindValue(':points_of_interest', $locationData['points_of_interest'], SQLITE3_TEXT);
        $stmt->bindValue(':connected_locations', null, SQLITE3_TEXT);
        $stmt->bindValue(':difficulty_level', $locationData['difficulty_level'], SQLITE3_TEXT);
        $stmt->bindValue(':recommended_level', $locationData['recommended_level'], SQLITE3_TEXT);
        $stmt->bindValue(':notable_items', null, SQLITE3_TEXT);
        $stmt->bindValue(':notable_npcs', null, SQLITE3_TEXT);

        $result = $stmt->execute();
        echo "Location added successfully\n";
    } catch (Exception $e) {
        echo "Error adding location: " . $e->getMessage() . "\n";
    }

    // Add a test quest
    echo "Adding a test quest...\n";
    $questData = [
        'quest_id' => 'q_white_mask_varre',
        'name' => 'White Mask Varré\'s Quest',
        'description' => 'The quest involving White Mask Varré, which leads to accessing Mohgwyn Palace early.',
        'type' => 'side',
        'starting_location_id' => 'loc_limgrave_chapel',
        'quest_giver_id' => 'npc_white_mask_varre',
        'difficulty' => 'moderate',
        'is_main_story' => 0,
        'rewards' => 'Access to Mohgwyn Palace, Bloody Finger',
        'spoiler_level' => 1
    ];

    try {
        $stmt = $eldenRingDb->prepare("
            INSERT INTO quests (
                quest_id, name, description, type, starting_location_id,
                quest_giver_id, difficulty, is_main_story, prerequisites,
                rewards, related_quests, spoiler_level, version_added, last_updated
            )
            VALUES (
                :quest_id, :name, :description, :type, :starting_location_id,
                :quest_giver_id, :difficulty, :is_main_story, :prerequisites,
                :rewards, :related_quests, :spoiler_level, :version_added, :last_updated
            )
        ");

        $stmt->bindValue(':quest_id', $questData['quest_id'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $questData['name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $questData['description'], SQLITE3_TEXT);
        $stmt->bindValue(':type', $questData['type'], SQLITE3_TEXT);
        $stmt->bindValue(':starting_location_id', $questData['starting_location_id'], SQLITE3_TEXT);
        $stmt->bindValue(':quest_giver_id', $questData['quest_giver_id'], SQLITE3_TEXT);
        $stmt->bindValue(':difficulty', $questData['difficulty'], SQLITE3_TEXT);
        $stmt->bindValue(':is_main_story', $questData['is_main_story'], SQLITE3_INTEGER);
        $stmt->bindValue(':prerequisites', null, SQLITE3_TEXT);
        $stmt->bindValue(':rewards', $questData['rewards'], SQLITE3_TEXT);
        $stmt->bindValue(':related_quests', null, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $questData['spoiler_level'], SQLITE3_INTEGER);
        $stmt->bindValue(':version_added', '1.0.0', SQLITE3_TEXT);
        $stmt->bindValue(':last_updated', date('Y-m-d'), SQLITE3_TEXT);

        $result = $stmt->execute();
        echo "Quest added successfully\n";

        // Add quest steps
        echo "Adding quest steps...\n";
        $stepData1 = [
            'step_id' => 'qs_varre_1',
            'quest_id' => 'q_white_mask_varre',
            'step_number' => 1,
            'title' => 'Meeting Varré',
            'description' => 'Meet White Mask Varré at the First Step in Limgrave.',
            'objective' => 'Talk to White Mask Varré',
            'hints' => 'He is standing near the First Step Site of Grace.',
            'location_id' => 'loc_limgrave_chapel',
            'spoiler_level' => 0
        ];

        $stepData2 = [
            'step_id' => 'qs_varre_2',
            'quest_id' => 'q_white_mask_varre',
            'step_number' => 2,
            'title' => 'Meeting the Two Fingers',
            'description' => 'Visit the Roundtable Hold and meet the Two Fingers as suggested by Varré.',
            'objective' => 'Speak with the Two Fingers at Roundtable Hold',
            'hints' => 'You need to rest at a Site of Grace outside of Limgrave or defeat Godrick to reach Roundtable Hold.',
            'location_id' => 'loc_roundtable_hold',
            'next_step_id' => 'qs_varre_3',
            'spoiler_level' => 1
        ];

        // Add step 1
        $stmt = $eldenRingDb->prepare("
            INSERT INTO quest_steps (
                step_id, quest_id, step_number, title, description,
                objective, hints, location_id, required_items,
                required_npcs, completion_flags, next_step_id,
                alternative_paths, spoiler_level
            )
            VALUES (
                :step_id, :quest_id, :step_number, :title, :description,
                :objective, :hints, :location_id, :required_items,
                :required_npcs, :completion_flags, :next_step_id,
                :alternative_paths, :spoiler_level
            )
        ");

        $stmt->bindValue(':step_id', $stepData1['step_id'], SQLITE3_TEXT);
        $stmt->bindValue(':quest_id', $stepData1['quest_id'], SQLITE3_TEXT);
        $stmt->bindValue(':step_number', $stepData1['step_number'], SQLITE3_INTEGER);
        $stmt->bindValue(':title', $stepData1['title'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $stepData1['description'], SQLITE3_TEXT);
        $stmt->bindValue(':objective', $stepData1['objective'], SQLITE3_TEXT);
        $stmt->bindValue(':hints', $stepData1['hints'], SQLITE3_TEXT);
        $stmt->bindValue(':location_id', $stepData1['location_id'], SQLITE3_TEXT);
        $stmt->bindValue(':required_items', null, SQLITE3_TEXT);
        $stmt->bindValue(':required_npcs', null, SQLITE3_TEXT);
        $stmt->bindValue(':completion_flags', null, SQLITE3_TEXT);
        $stmt->bindValue(':next_step_id', null, SQLITE3_TEXT);
        $stmt->bindValue(':alternative_paths', null, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $stepData1['spoiler_level'], SQLITE3_INTEGER);

        $result = $stmt->execute();

        // Add step 2
        $stmt = $eldenRingDb->prepare("
            INSERT INTO quest_steps (
                step_id, quest_id, step_number, title, description,
                objective, hints, location_id, required_items,
                required_npcs, completion_flags, next_step_id,
                alternative_paths, spoiler_level
            )
            VALUES (
                :step_id, :quest_id, :step_number, :title, :description,
                :objective, :hints, :location_id, :required_items,
                :required_npcs, :completion_flags, :next_step_id,
                :alternative_paths, :spoiler_level
            )
        ");

        $stmt->bindValue(':step_id', $stepData2['step_id'], SQLITE3_TEXT);
        $stmt->bindValue(':quest_id', $stepData2['quest_id'], SQLITE3_TEXT);
        $stmt->bindValue(':step_number', $stepData2['step_number'], SQLITE3_INTEGER);
        $stmt->bindValue(':title', $stepData2['title'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $stepData2['description'], SQLITE3_TEXT);
        $stmt->bindValue(':objective', $stepData2['objective'], SQLITE3_TEXT);
        $stmt->bindValue(':hints', $stepData2['hints'], SQLITE3_TEXT);
        $stmt->bindValue(':location_id', $stepData2['location_id'], SQLITE3_TEXT);
        $stmt->bindValue(':required_items', null, SQLITE3_TEXT);
        $stmt->bindValue(':required_npcs', null, SQLITE3_TEXT);
        $stmt->bindValue(':completion_flags', null, SQLITE3_TEXT);
        $stmt->bindValue(':next_step_id', $stepData2['next_step_id'], SQLITE3_TEXT);
        $stmt->bindValue(':alternative_paths', null, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $stepData2['spoiler_level'], SQLITE3_INTEGER);

        $result = $stmt->execute();
        echo "Quest steps added successfully\n";
    } catch (Exception $e) {
        echo "Error with quest data: " . $e->getMessage() . "\n";
    }

    // Search for quests
    echo "\nSearching for quests containing 'Varré'...\n";

    try {
        $searchResults = $eldenRingDb->fetchAll("
            SELECT * FROM search_index
            WHERE content_type = 'quest' AND (
                name LIKE :search OR
                description LIKE :search OR
                keywords LIKE :search
            )
            LIMIT 10
        ", [':search' => '%Varré%']);

        $quests = [];
        foreach ($searchResults as $row) {
            $quest = $eldenRingDb->fetchOne("
                SELECT * FROM quests
                WHERE quest_id = :quest_id
            ", [':quest_id' => $row['content_id']]);

            if ($quest) {
                $quests[] = $quest;
            }
        }

        if (count($quests) > 0) {
            foreach ($quests as $quest) {
                echo "Found quest: " . $quest['name'] . "\n";
                echo "Description: " . $quest['description'] . "\n";
            }
        } else {
            echo "No quests found\n";
        }
    } catch (Exception $e) {
        echo "Error searching quests: " . $e->getMessage() . "\n";
    }

    // Baldur's Gate 3 example
    echo "\n=== BALDUR'S GATE 3 DATABASE EXAMPLES ===\n";

    // Create Baldur's Gate 3 database instance
    $bg3Db = Database::getGameInstance('baldurs_gate3');

    // Add a test location
    echo "Adding a test location...\n";
    $locationData = [
        'location_id' => 'loc_bg3_grove',
        'name' => 'Emerald Grove',
        'description' => 'A druid sanctuary in the wilderness near the Sword Coast.',
        'region' => 'Wilderness',
        'points_of_interest' => 'Tiefling Refugees, Druid Circle, Hidden Entrance',
        'difficulty_level' => 'beginner',
        'recommended_level' => '1-2'
    ];

    try {
        $stmt = $bg3Db->prepare("
            INSERT INTO locations (
                location_id, name, description, region, parent_location_id,
                coordinates, points_of_interest, connected_locations,
                difficulty_level, recommended_level, notable_items, notable_npcs
            )
            VALUES (
                :location_id, :name, :description, :region, :parent_location_id,
                :coordinates, :points_of_interest, :connected_locations,
                :difficulty_level, :recommended_level, :notable_items, :notable_npcs
            )
        ");

        $stmt->bindValue(':location_id', $locationData['location_id'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $locationData['name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $locationData['description'], SQLITE3_TEXT);
        $stmt->bindValue(':region', $locationData['region'], SQLITE3_TEXT);
        $stmt->bindValue(':parent_location_id', null, SQLITE3_TEXT);
        $stmt->bindValue(':coordinates', null, SQLITE3_TEXT);
        $stmt->bindValue(':points_of_interest', $locationData['points_of_interest'], SQLITE3_TEXT);
        $stmt->bindValue(':connected_locations', null, SQLITE3_TEXT);
        $stmt->bindValue(':difficulty_level', $locationData['difficulty_level'], SQLITE3_TEXT);
        $stmt->bindValue(':recommended_level', $locationData['recommended_level'], SQLITE3_TEXT);
        $stmt->bindValue(':notable_items', null, SQLITE3_TEXT);
        $stmt->bindValue(':notable_npcs', null, SQLITE3_TEXT);

        $result = $stmt->execute();
        echo "Location added successfully\n";
    } catch (Exception $e) {
        echo "Error adding location: " . $e->getMessage() . "\n";
    }

    echo "\nExamples completed!\n";
}

/**
 * Database utility class
 */
class Database {
    private static $systemInstance = null;
    private static $gameInstances = [];
    public $db = null;

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

// Run the examples
// Comment out this line if you want to include this file without running examples
runExamples();
