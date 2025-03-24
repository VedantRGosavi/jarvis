/* 
 * IMPORTANT: If any changes are made to this database schema,
 * please ensure that corresponding updates are applied throughout
 * the entire codebase to maintain consistency and prevent integration issues.
 */

# Gaming Companion Overlay Tool - Comprehensive Database Schema

## Overview

This document outlines a comprehensive database schema for the Gaming Companion Overlay Tool, specifically designed to cover all aspects of complex RPG games like Elden Ring and Baldur's Gate 3. The schema is structured to provide clear, detailed information to players during gameplay without causing confusion or information overload.

The design follows a modular approach with separate SQLite databases for each game, ensuring ease of maintenance and the ability to update game data independently. This approach aligns with the project's goal of rapid development and deployment while maintaining a high level of detail and accuracy.

## Database Architecture

### 1. System Database

A central SQLite database (`system.sqlite`) handles user accounts, subscriptions, and usage analytics. This database is separate from the game-specific data to ensure clean separation of concerns.

### 2. Game-Specific Databases

Each supported game has its own dedicated SQLite database:
- `elden_ring.sqlite`
- `baldurs_gate3.sqlite`

This modular approach offers several advantages:
- Independent updates for each game
- Simplified data management
- Potential for community-contributed game data packs
- Faster query performance through smaller, focused databases

## System Database Schema

### 1. Database Creation

```bash
# Create system database
sqlite3 data/system.sqlite < database/schema/system_schema.sql
```

### 2. Schema Definition

```sql
-- users table: Stores user account information
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    created_at TEXT NOT NULL,
    last_login TEXT,
    subscription_status TEXT NOT NULL DEFAULT 'none',
    stripe_customer_id TEXT
);

-- subscriptions table: Tracks user subscription details
CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_subscription_id TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL,
    current_period_end TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- purchases table: Records one-time purchases
CREATE TABLE purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    payment_intent_id TEXT,
    status TEXT NOT NULL,
    amount INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    completed_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- user_settings table: Stores user preferences
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    overlay_position TEXT,
    overlay_size TEXT,
    overlay_opacity REAL DEFAULT 0.85,
    hotkey_combination TEXT DEFAULT 'ctrl+shift+j',
    theme TEXT DEFAULT 'dark',
    default_info_display_mode TEXT DEFAULT 'concise',
    show_spoilers BOOLEAN DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- user_game_progress table: Tracks user progress within games
CREATE TABLE user_game_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    quest_id TEXT NOT NULL,
    step_id TEXT,
    completed INTEGER DEFAULT 0,
    marked_status TEXT DEFAULT 'untracked',
    notes TEXT,
    last_accessed TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, quest_id)
);

-- user_bookmarks table: Stores user-saved content
CREATE TABLE user_bookmarks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    resource_type TEXT NOT NULL,
    resource_id TEXT NOT NULL,
    display_name TEXT NOT NULL,
    bookmark_group TEXT DEFAULT 'default',
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, resource_type, resource_id)
);

-- usage_logs table: Records user activity for analytics
CREATE TABLE usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    action_type TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    session_id TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_purchases_user_id ON purchases(user_id);
CREATE INDEX idx_purchases_game_id ON purchases(game_id);
CREATE INDEX idx_usage_logs_user_id ON usage_logs(user_id);
CREATE INDEX idx_usage_logs_game_id ON usage_logs(game_id);
CREATE INDEX idx_user_game_progress_user_game ON user_game_progress(user_id, game_id);
CREATE INDEX idx_user_bookmarks_user_game ON user_bookmarks(user_id, game_id);
```

## Game Database Schema

The following schema is applied to each game-specific database (shown here for `elden_ring.sqlite`). The schema for `baldurs_gate3.sqlite` follows the same structure with game-specific data.

### 1. Database Creation

```bash
# Create game-specific database
sqlite3 data/game_data/elden_ring.sqlite < database/schema/game_schema.sql
```

### 2. Schema Definition

```sql
-- quests table: Main quest information
CREATE TABLE quests (
    quest_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    type TEXT NOT NULL,
    starting_location_id TEXT,
    quest_giver_id TEXT,
    difficulty TEXT,
    is_main_story INTEGER DEFAULT 0,
    prerequisites TEXT,
    rewards TEXT,
    related_quests TEXT,
    spoiler_level INTEGER DEFAULT 0,
    version_added TEXT,
    last_updated TEXT
);

-- quest_steps table: Detailed quest progression steps
CREATE TABLE quest_steps (
    step_id TEXT PRIMARY KEY,
    quest_id TEXT NOT NULL,
    step_number INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    objective TEXT NOT NULL,
    hints TEXT,
    location_id TEXT,
    required_items TEXT,
    required_npcs TEXT,
    completion_flags TEXT,
    next_step_id TEXT,
    alternative_paths TEXT,
    spoiler_level INTEGER DEFAULT 0,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- locations table: Game areas and points of interest
CREATE TABLE locations (
    location_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    region TEXT NOT NULL,
    parent_location_id TEXT,
    coordinates TEXT,
    points_of_interest TEXT,
    connected_locations TEXT,
    difficulty_level TEXT,
    recommended_level TEXT,
    notable_items TEXT,
    notable_npcs TEXT,
    FOREIGN KEY (parent_location_id) REFERENCES locations(location_id)
);

-- npcs table: Non-player characters
CREATE TABLE npcs (
    npc_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    role TEXT,
    default_location_id TEXT,
    faction TEXT,
    is_hostile INTEGER DEFAULT 0,
    is_merchant INTEGER DEFAULT 0,
    gives_quests TEXT,
    services TEXT,
    dialogue_summary TEXT,
    relationship_to_other_npcs TEXT,
    schedule TEXT,
    drops_on_defeat TEXT,
    FOREIGN KEY (default_location_id) REFERENCES locations(location_id)
);

-- items table: Game items
CREATE TABLE items (
    item_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    type TEXT NOT NULL,
    subtype TEXT,
    stats TEXT,
    requirements TEXT,
    effects TEXT,
    locations_found TEXT,
    dropped_by TEXT,
    quest_related INTEGER DEFAULT 0,
    related_quests TEXT,
    rarity TEXT,
    image_path TEXT
);

-- npc_locations table: Tracks where NPCs can be found at different stages
CREATE TABLE npc_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    npc_id TEXT NOT NULL,
    location_id TEXT NOT NULL,
    condition_type TEXT,
    condition_value TEXT,
    notes TEXT,
    FOREIGN KEY (npc_id) REFERENCES npcs(npc_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- quest_prerequisites table: Tracks requirements for quests
CREATE TABLE quest_prerequisites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quest_id TEXT NOT NULL,
    prerequisite_type TEXT NOT NULL,
    prerequisite_id TEXT NOT NULL,
    notes TEXT,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id)
);

-- quest_consequences table: Tracks effects of quest decisions
CREATE TABLE quest_consequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quest_id TEXT NOT NULL,
    step_id TEXT,
    decision TEXT NOT NULL,
    affects_type TEXT NOT NULL,
    affects_id TEXT NOT NULL,
    effect TEXT NOT NULL,
    spoiler_level INTEGER DEFAULT 0,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
    FOREIGN KEY (step_id) REFERENCES quest_steps(step_id)
);

-- search_index table: Optimized table for text search
CREATE VIRTUAL TABLE search_index USING fts5(
    content_id,
    content_type,
    name,
    description,
    keywords,
    tokenize='porter'
);

-- Create indexes for performance
CREATE INDEX idx_quest_steps_quest_id ON quest_steps(quest_id);
CREATE INDEX idx_locations_region ON locations(region);
CREATE INDEX idx_items_type ON items(type);
CREATE INDEX idx_items_quest_related ON items(quest_related);
CREATE INDEX idx_npcs_default_location ON npcs(default_location_id);
CREATE INDEX idx_npc_locations_npc_id ON npc_locations(npc_id);
CREATE INDEX idx_npc_locations_location_id ON npc_locations(location_id);
CREATE INDEX idx_quest_prerequisites_quest_id ON quest_prerequisites(quest_id);
```

## Data Structure and Relationships

### 1. Core Game Entities

#### Quests
Central to both games, quests are structured as:
- Main information in `quests` table (name, description, type)
- Detailed progression in `quest_steps` table (step-by-step walkthrough)
- Dependencies in `quest_prerequisites` table (prevents player confusion about quest availability)
- Outcomes in `quest_consequences` table (helps players understand implications of choices)

#### Locations
Game areas are organized hierarchically:
- Main locations and sub-locations (using parent_location_id)
- Region grouping for easier navigation
- Connected locations for pathfinding assistance
- Points of interest to highlight key features

#### NPCs
Characters with detailed tracking:
- Base information (name, role, faction)
- Location tracking via the `npc_locations` table (handles character movement)
- Relationships to quests and other NPCs (prevents confusion about character relevance)
- Services offered (merchants, trainers, etc.)

#### Items
Game items with comprehensive details:
- Type categorization (weapon, armor, quest item, etc.)
- Acquisition information (locations, NPCs)
- Quest relevance flagging (helps players identify important items)
- Stats and requirements (for equipment)

### 2. Key Relationships

The schema establishes clear relationships between entities:

1. **Quests to Steps**: One-to-many relationship tracking quest progression
2. **Quests to NPCs**: Many-to-many relationship via quest_giver_id and gives_quests
3. **Quests to Locations**: Many-to-many relationship through starting_location_id and quest_steps
4. **NPCs to Locations**: Many-to-many relationship through npc_locations table
5. **Items to Locations**: Many-to-many relationship via locations_found
6. **Quests to Prerequisites**: One-to-many relationship in quest_prerequisites

## Sample Data Structure

### Elden Ring Example

#### Quest Record
```json
{
  "quest_id": "q_irina_letter",
  "name": "Irina's Letter",
  "description": "Deliver a letter from Irina to her father at Castle Morne.",
  "type": "side",
  "starting_location_id": "loc_weeping_peninsula_bridge",
  "quest_giver_id": "npc_irina",
  "difficulty": "beginner",
  "is_main_story": 0,
  "prerequisites": null,
  "rewards": "Sacrificial Twig",
  "related_quests": "q_castle_morne",
  "spoiler_level": 1,
  "version_added": "1.0.0",
  "last_updated": "2025-03-01"
}
```

#### Quest Step Records
```json
[
  {
    "step_id": "qs_irina_letter_1",
    "quest_id": "q_irina_letter",
    "step_number": 1,
    "title": "Meeting Irina",
    "description": "Find Irina at the bridge leading to the Weeping Peninsula.",
    "objective": "Talk to Irina",
    "hints": "She is located at the Weeping Peninsula bridge checkpoint.",
    "location_id": "loc_weeping_peninsula_bridge",
    "required_items": null,
    "required_npcs": "npc_irina",
    "completion_flags": "dialogue_irina_first_meeting",
    "next_step_id": "qs_irina_letter_2",
    "alternative_paths": null,
    "spoiler_level": 0
  },
  {
    "step_id": "qs_irina_letter_2",
    "quest_id": "q_irina_letter",
    "step_number": 2,
    "title": "Delivering the Letter",
    "description": "Take Irina's letter to her father at Castle Morne.",
    "objective": "Find Edgar at Castle Morne and deliver Irina's letter",
    "hints": "Edgar can be found on the ramparts of Castle Morne.",
    "location_id": "loc_castle_morne_rampart",
    "required_items": "item_irina_letter",
    "required_npcs": "npc_edgar",
    "completion_flags": "dialogue_edgar_receives_letter",
    "next_step_id": "qs_irina_letter_3",
    "alternative_paths": null,
    "spoiler_level": 1
  }
]
```

#### NPC Record
```json
{
  "npc_id": "npc_irina",
  "name": "Irina of Morne",
  "description": "A blind young woman who asks for help delivering a letter to her father.",
  "role": "quest_giver",
  "default_location_id": "loc_weeping_peninsula_bridge",
  "faction": "Castle Morne",
  "is_hostile": 0,
  "is_merchant": 0,
  "gives_quests": "q_irina_letter",
  "services": null,
  "dialogue_summary": "Asks player to deliver a letter to her father Edgar at Castle Morne.",
  "relationship_to_other_npcs": "Daughter of Edgar, the castellan of Castle Morne.",
  "schedule": null,
  "drops_on_defeat": null
}
```

### Baldur's Gate 3 Example

#### Quest Record
```json
{
  "quest_id": "q_bg3_rescue_druid_halsin",
  "name": "Rescue the Druid Halsin",
  "description": "Find and rescue the Arch Druid Halsin from the Goblin Camp.",
  "type": "side",
  "starting_location_id": "loc_bg3_emerald_grove",
  "quest_giver_id": "npc_bg3_rath",
  "difficulty": "moderate",
  "is_main_story": 0,
  "prerequisites": "q_bg3_enter_emerald_grove",
  "rewards": "Halsin joins the camp, Druid faction approval",
  "related_quests": "q_bg3_dealing_with_goblins",
  "spoiler_level": 2,
  "version_added": "1.0.0",
  "last_updated": "2025-03-15"
}
```

#### Location Record
```json
{
  "location_id": "loc_bg3_goblin_camp",
  "name": "Goblin Camp",
  "description": "A fortified camp where goblins and their Drow leaders have established a base.",
  "region": "Wilderness",
  "parent_location_id": null,
  "coordinates": "{'x': 235, 'y': 412}",
  "points_of_interest": "Priestess Gut's quarters, Minthara's chambers, Prisoner cells, Main gate",
  "connected_locations": "loc_bg3_blighted_village, loc_bg3_shattered_sanctum",
  "difficulty_level": "moderate",
  "recommended_level": "3-4",
  "notable_items": "item_bg3_wyvern_poison, item_bg3_shattered_sanctum_key",
  "notable_npcs": "npc_bg3_minthara, npc_bg3_priestess_gut, npc_bg3_halsin_bear"
}
```

## User Progress and Personalization

The system database includes tables that track user interaction with game content:

### 1. User Game Progress

```sql
CREATE TABLE user_game_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    quest_id TEXT NOT NULL,
    step_id TEXT,
    completed INTEGER DEFAULT 0,
    marked_status TEXT DEFAULT 'untracked',
    notes TEXT,
    last_accessed TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, quest_id)
);
```

This table allows for:
- Tracking quest completion status
- Custom user status labels ('in progress', 'skipped', etc.)
- Personal notes on quests
- Chronological tracking of quest engagement

### 2. User Bookmarks

```sql
CREATE TABLE user_bookmarks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    resource_type TEXT NOT NULL,
    resource_id TEXT NOT NULL,
    display_name TEXT NOT NULL,
    bookmark_group TEXT DEFAULT 'default',
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, resource_type, resource_id)
);
```

This table enables:
- Saving important game content for quick access
- Custom naming of bookmarks
- Grouping bookmarks by category
- Bookmarking any entity type (quests, items, NPCs, locations)

### 3. User Settings

```sql
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    overlay_position TEXT,
    overlay_size TEXT,
    overlay_opacity REAL DEFAULT 0.85,
    hotkey_combination TEXT DEFAULT 'ctrl+shift+j',
    theme TEXT DEFAULT 'dark',
    default_info_display_mode TEXT DEFAULT 'concise',
    show_spoilers BOOLEAN DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

This table stores:
- Interface customization preferences
- Content display preferences (concise vs. detailed)
- Spoiler visibility settings to prevent unwanted information

## Preventing User Confusion

The schema incorporates several features specifically designed to prevent user confusion:

### 1. Spoiler Management

- `spoiler_level` fields in quest and step records
- User preference for spoiler visibility
- Progressive revelation of information based on quest progress

### 2. Contextual Information Display

- Clear quest prerequisites and dependencies
- Location hierarchies and connections
- NPC relationships and schedules
- Quest-item relationships

### 3. Information Clarity

- Separate fields for descriptions (flavor text) and objectives (actionable information)
- Hints field for non-spoiler guidance
- Alternative paths for quests with multiple solutions
- Consequence tracking for player decisions

### 4. User Customization

- Ability to save personal notes on quests
- Bookmark system for frequently needed information
- Custom display preferences (concise vs. detailed views)

## Implementation Details

### 1. Data Access Layer

```php
<?php
// models/GameData.php
class GameData {
    private $db;
    private $game;
    
    public function __construct($game) {
        $this->game = $game;
        // Use the Database utility class to get a game database instance
        $this->db = Database::getGameInstance($game);
    }
    
    public function getQuest($questId, $spoilerLevel = 0) {
        $quest = $this->db->fetchOne("
            SELECT * FROM quests 
            WHERE quest_id = :quest_id AND spoiler_level <= :spoiler_level
        ", [
            ':quest_id' => $questId,
            ':spoiler_level' => $spoilerLevel
        ]);
        
        if (!$quest) {
            return null;
        }
        
        // Get quest steps
        $steps = $this->db->fetchAll("
            SELECT * FROM quest_steps 
            WHERE quest_id = :quest_id AND spoiler_level <= :spoiler_level
            ORDER BY step_number
        ", [
            ':quest_id' => $questId,
            ':spoiler_level' => $spoilerLevel
        ]);
        
        $quest['steps'] = $steps;
        
        // Get prerequisites
        $prerequisites = $this->db->fetchAll("
            SELECT * FROM quest_prerequisites
            WHERE quest_id = :quest_id
        ", [
            ':quest_id' => $questId
        ]);
        
        $quest['prerequisites'] = $prerequisites;
        
        return $quest;
    }
    
    public function getQuestsByLocation($locationId, $spoilerLevel = 0) {
        // Get quests that start at this location
        $quests = $this->db->fetchAll("
            SELECT * FROM quests 
            WHERE starting_location_id = :location_id AND spoiler_level <= :spoiler_level
        ", [
            ':location_id' => $locationId,
            ':spoiler_level' => $spoilerLevel
        ]);
        
        // Get quests with steps at this location
        $stepsAtLocation = $this->db->fetchAll("
            SELECT DISTINCT q.* FROM quests q
            JOIN quest_steps qs ON q.quest_id = qs.quest_id
            WHERE qs.location_id = :location_id 
            AND q.spoiler_level <= :spoiler_level
            AND qs.spoiler_level <= :spoiler_level
        ", [
            ':location_id' => $locationId,
            ':spoiler_level' => $spoilerLevel
        ]);
        
        // Merge the results, avoiding duplicates
        $questIds = array_column($quests, 'quest_id');
        foreach ($stepsAtLocation as $quest) {
            if (!in_array($quest['quest_id'], $questIds)) {
                $quests[] = $quest;
                $questIds[] = $quest['quest_id'];
            }
        }
        
        return $quests;
    }
    
    // Additional methods for other entity types...
}
```

### 2. User Progress Tracking

```php
<?php
// models/UserProgress.php
class UserProgress {
    private $db;
    
    public function __construct() {
        // Use the Database utility class to get the system database instance
        $this->db = Database::getSystemInstance();
    }
    
    public function trackQuestProgress($userId, $gameId, $questId, $stepId = null, $completed = 0, $status = 'in_progress') {
        // Check if record exists
        $existing = $this->db->fetchOne("
            SELECT id FROM user_game_progress
            WHERE user_id = :user_id AND game_id = :game_id AND quest_id = :quest_id
        ", [
            ':user_id' => $userId,
            ':game_id' => $gameId,
            ':quest_id' => $questId
        ]);
        
        $timestamp = date('Y-m-d H:i:s');
        
        if ($existing) {
            // Update existing record
            $this->db->fetchAll("
                UPDATE user_game_progress
                SET step_id = :step_id,
                    completed = :completed,
                    marked_status = :status,
                    last_accessed = :last_accessed,
                    updated_at = :updated_at
                WHERE id = :id
            ", [
                ':step_id' => $stepId,
                ':completed' => $completed,
                ':status' => $status,
                ':last_accessed' => $timestamp,
                ':updated_at' => $timestamp,
                ':id' => $existing['id']
            ]);
            
            return $existing['id'];
        } else {
            // Create new record
            $stmt = $this->db->prepare("
                INSERT INTO user_game_progress (
                    user_id, game_id, quest_id, step_id, completed,
                    marked_status, last_accessed, created_at, updated_at
                )
                VALUES (
                    :user_id, :game_id, :quest_id, :step_id, :completed,
                    :status, :last_accessed, :created_at, :updated_at
                )
            ");
            
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':game_id', $gameId, SQLITE3_TEXT);
            $stmt->bindValue(':quest_id', $questId, SQLITE3_TEXT);
            $stmt->bindValue(':step_id', $stepId, SQLITE3_TEXT);
            $stmt->bindValue(':completed', $completed, SQLITE3_INTEGER);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':last_accessed', $timestamp, SQLITE3_TEXT);
            $stmt->bindValue(':created_at', $timestamp, SQLITE3_TEXT);
            $stmt->bindValue(':updated_at', $timestamp, SQLITE3_TEXT);
            
            $stmt->execute();
            return $this->db->db->lastInsertRowID();
        }
    }
    
    public function getUserQuestProgress($userId, $gameId, $questId) {
        return $this->db->fetchOne("
            SELECT * FROM user_game_progress
            WHERE user_id = :user_id AND game_id = :game_id AND quest_id = :quest_id
        ", [
            ':user_id' => $userId,
            ':game_id' => $gameId,
            ':quest_id' => $questId
        ]);
    }
    
    public function addQuestNote($userId, $gameId, $questId, $notes) {
        $progress = $this->getUserQuestProgress($userId, $gameId, $questId);
        if ($progress) {
            $this->db->fetchAll("
                UPDATE user_game_progress
                SET notes = :notes,
                    updated_at = :updated_at
                WHERE id = :id
            ", [
                ':notes' => $notes,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':id' => $progress['id']
            ]);
            
            return true;
        }
        return false;
    }
    
    // Additional methods for other tracking functionality...
}
```

### 3. API Endpoint for Quest Information

```php
<?php
// api/quests.php
require_once BASE_PATH . '/utils/Response.php';
require_once BASE_PATH . '/utils/Auth.php';
require_once BASE_PATH . '/utils/Database.php';
require_once BASE_PATH . '/models/GameData.php';
require_once BASE_PATH . '/models/UserProgress.php';
require_once BASE_PATH . '/models/UserSettings.php';

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
$gameId = $api_segments[1] ?? '';
$questId = $api_segments[2] ?? '';

// Validate game
if (!in_array($gameId, ['elden_ring', 'baldurs_gate3'])) {
    Response::error('Invalid game', 400);
    exit;
}

// Initialize models
$gameData = new GameData($gameId);
$userProgress = new UserProgress();

// Get user settings for spoiler level
$userSettingsDb = Database::getSystemInstance();
$userSettings = $userSettingsDb->fetchOne("
    SELECT * FROM user_settings WHERE user_id = :user_id
", [':user_id' => $userId]);

$showSpoilers = $userSettings['show_spoilers'] ?? false;
$spoilerLevel = $showSpoilers ? 999 : 1; // 999 means show all spoilers

// Handle request
if ($method === 'GET' && $questId) {
    // Get specific quest
    $quest = $gameData->getQuest($questId, $spoilerLevel);
    
    if (!$quest) {
        Response::error('Quest not found', 404);
        exit;
    }
    
    // Get user progress for this quest
    $progress = $userProgress->getUserQuestProgress($userId, $gameId, $questId);
    
    // Log this access
    $usageLogs = Database::getSystemInstance();
    $usageLogs->prepare("
        INSERT INTO usage_logs (user_id, game_id, action_type, resource_type, resource_id, created_at)
        VALUES (:user_id, :game_id, :action_type, :resource_type, :resource_id, :created_at)
    ")->execute([
        ':user_id' => $userId,
        ':game_id' => $gameId,
        ':action_type' => 'view',
        ':resource_type' => 'quest',
        ':resource_id' => $questId,
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Return combined data
    Response::success([
        'quest' => $quest,
        'user_progress' => $progress
    ]);
} elseif ($method === 'GET') {
    // List all quests
    $gameDb = Database::getGameInstance($gameId);
    $quests = $gameDb->fetchAll("
        SELECT * FROM quests WHERE spoiler_level <= :spoiler_level
    ", [':spoiler_level' => $spoilerLevel]);
    
    // Get user progress for these quests
    $progress = $userProgress->fetchAll("
        SELECT * FROM user_game_progress
        WHERE user_id = :user_id AND game_id = :game_id
    ", [
        ':user_id' => $userId,
        ':game_id' => $gameId
    ]);
    
    // Convert to dictionary keyed by quest_id
    $progressDict = [];
    foreach ($progress as $item) {
        $progressDict[$item['quest_id']] = $item;
    }
    
    // Combine quest data with progress
    foreach ($quests as &$quest) {
        $quest['user_progress'] = $progressDict[$quest['quest_id']] ?? null;
    }
    
    Response::success($quests);
} elseif ($method === 'POST' && $questId) {
    // Update user's progress on this quest
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['status'])) {
        Response::error('Status required', 400);
        exit;
    }
    
    $stepId = $data['step_id'] ?? null;
    $completed = $data['completed'] ?? 0;
    $status = $data['status'];
    $notes = $data['notes'] ?? null;
    
    $userProgress->trackQuestProgress(
        $userId, 
        $gameId, 
        $questId, 
        $stepId, 
        $completed, 
        $status
    );
    
    // Update notes if provided
    if ($notes !== null) {
        $userProgress->addQuestNote($userId, $gameId, $questId, $notes);
    }
    
    Response::success(['status' => 'updated']);
} else {
    Response::error('Method not allowed', 405);
}
```

## Game Data Storage and Management

The game databases store structured information about every aspect of the supported games. This section outlines the approach to data management, including initial population, updates, and community contributions.

### 1. Data Population Strategy

For each game, initial data is sourced through a combination of methods:

1. **Manual Curation**: Core quest data, major NPCs, and locations are manually curated for accuracy
2. **Wiki Extraction**: Supporting data extracted from community wikis with proper attribution
3. **Community Submissions**: Framework for accepting community contributions (long-term)

### 2. Data Format for Import/Export

Game data can be exported and imported using a structured JSON format:

```json
{
  "game_id": "elden_ring",
  "version": "1.0.0",
  "last_updated": "2025-03-23",
  "entities": {
    "quests": [
      {
        "quest_id": "q_irina_letter",
        "name": "Irina's Letter",
        "description": "Deliver a letter from Irina to her father at Castle Morne.",
        "type": "side",
        "starting_location_id": "loc_weeping_peninsula_bridge",
        "quest_giver_id": "npc_irina",
        "difficulty": "beginner",
        "is_main_story": 0,
        "prerequisites": null,
        "rewards": "Sacrificial Twig",
        "related_quests": "q_castle_morne",
        "spoiler_level": 1,
        "version_added": "1.0.0",
        "last_updated": "2025-03-01"
      }
    ],
    "quest_steps": [
      {
        "step_id": "qs_irina_letter_1",
        "quest_id": "q_irina_letter",
        "step_number": 1,
        "title": "Meeting Irina",
        "description": "Find Irina at the bridge leading to the Weeping Peninsula.",
        "objective": "Talk to Irina",
        "hints": "She is located at the Weeping Peninsula bridge checkpoint.",
        "location_id": "loc_weeping_peninsula_bridge",
        "required_items": null,
        "required_npcs": "npc_irina",
        "completion_flags": "dialogue_irina_first_meeting",
        "next_step_id": "qs_irina_letter_2",
        "alternative_paths": null,
        "spoiler_level": 0
      }
    ],
    "locations": [
      {
        "location_id": "loc_weeping_peninsula_bridge",
        "name": "Bridge of Sacrifice",
        "description": "A bridge connecting Limgrave to the Weeping Peninsula.",
        "region": "Weeping Peninsula",
        "parent_location_id": null,
        "coordinates": "{'x': 143.5, 'y': 176.2}",
        "points_of_interest": "Site of Grace, Irina's position",
        "connected_locations": "loc_limgrave_south, loc_weeping_peninsula_north",
        "difficulty_level": "easy",
        "recommended_level": "1-10",
        "notable_items": null,
        "notable_npcs": "npc_irina"
      }
    ],
    "npcs": [
      {
        "npc_id": "npc_irina",
        "name": "Irina of Morne",
        "description": "A blind young woman who asks for help delivering a letter to her father.",
        "role": "quest_giver",
        "default_location_id": "loc_weeping_peninsula_bridge",
        "faction": "Castle Morne",
        "is_hostile": 0,
        "is_merchant": 0,
        "gives_quests": "q_irina_letter",
        "services": null,
        "dialogue_summary": "Asks player to deliver a letter to her father Edgar at Castle Morne.",
        "relationship_to_other_npcs": "Daughter of Edgar, the castellan of Castle Morne.",
        "schedule": null,
        "drops_on_defeat": null
      }
    ],
    "items": [
      {
        "item_id": "item_irina_letter",
        "name": "Irina's Letter",
        "description": "A letter from Irina to her father Edgar.",
        "type": "key_item",
        "subtype": "quest",
        "stats": null,
        "requirements": null,
        "effects": null,
        "locations_found": "npc_irina",
        "dropped_by": null,
        "quest_related": 1,
        "related_quests": "q_irina_letter",
        "rarity": "unique",
        "image_path": "items/key/irina_letter.png"
      }
    ]
  }
}
```

### 3. Data Import Script

```php
<?php
// utils/GameDataImporter.php
class GameDataImporter {
    private $db;
    private $game;
    
    public function __construct($game) {
        $this->game = $game;
        $this->db = new SQLite3(BASE_PATH . "/data/game_data/{$game}.sqlite");
        $this->db->enableExceptions(true);
    }
    
    public function importFromJson($jsonFile) {
        // Read JSON file
        $jsonData = file_get_contents($jsonFile);
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception("Invalid JSON data");
        }
        
        // Begin transaction
        $this->db->exec('BEGIN TRANSACTION');
        
        try {
            // Import each entity type
            foreach ($data['entities'] as $entityType => $entities) {
                $this->importEntities($entityType, $entities);
            }
            
            // Rebuild search index
            $this->rebuildSearchIndex();
            
            // Commit transaction
            $this->db->exec('COMMIT');
            
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }
    
    private function importEntities($entityType, $entities) {
        foreach ($entities as $entity) {
            $this->insertOrUpdateEntity($entityType, $entity);
        }
    }
    
    private function insertOrUpdateEntity($entityType, $entity) {
        // Determine primary key field
        $primaryKey = $this->getPrimaryKeyField($entityType);
        $primaryKeyValue = $entity[$primaryKey];
        
        // Check if entity exists
        $stmt = $this->db->prepare("SELECT 1 FROM {$entityType} WHERE {$primaryKey} = :id");
        $stmt->bindValue(':id', $primaryKeyValue, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            // Update existing entity
            $this->updateEntity($entityType, $primaryKey, $primaryKeyValue, $entity);
        } else {
            // Insert new entity
            $this->insertEntity($entityType, $entity);
        }
    }
    
    private function insertEntity($entityType, $entity) {
        // Build column list and values
        $columns = array_keys($entity);
        $placeholders = array_map(function($col) {
            return ":{$col}";
        }, $columns);
        
        $columnList = implode(', ', $columns);
        $placeholderList = implode(', ', $placeholders);
        
        // Prepare and execute statement
        $stmt = $this->db->prepare("INSERT INTO {$entityType} ({$columnList}) VALUES ({$placeholderList})");
        
        foreach ($entity as $column => $value) {
            $stmt->bindValue(":{$column}", $value, $this->getValueType($value));
        }
        
        $stmt->execute();
    }
    
    private function updateEntity($entityType, $primaryKey, $primaryKeyValue, $entity) {
        // Build SET clause
        $setParts = [];
        foreach ($entity as $column => $value) {
            if ($column !== $primaryKey) {
                $setParts[] = "{$column} = :{$column}";
            }
        }
        
        $setClause = implode(', ', $setParts);
        
        // Prepare and execute statement
        $stmt = $this->db->prepare("UPDATE {$entityType} SET {$setClause} WHERE {$primaryKey} = :id");
        $stmt->bindValue(':id', $primaryKeyValue, SQLITE3_TEXT);
        
        foreach ($entity as $column => $value) {
            if ($column !== $primaryKey) {
                $stmt->bindValue(":{$column}", $value, $this->getValueType($value));
            }
        }
        
        $stmt->execute();
    }
    
    private function getPrimaryKeyField($entityType) {
        switch ($entityType) {
            case 'quests':
                return 'quest_id';
            case 'quest_steps':
                return 'step_id';
            case 'locations':
                return 'location_id';
            case 'npcs':
                return 'npc_id';
            case 'items':
                return 'item_id';
            default:
                return 'id';
        }
    }
    
    private function getValueType($value) {
        if (is_null($value)) {
            return SQLITE3_NULL;
        } elseif (is_int($value)) {
            return SQLITE3_INTEGER;
        } elseif (is_float($value)) {
            return SQLITE3_FLOAT;
        } else {
            return SQLITE3_TEXT;
        }
    }
    
    private function rebuildSearchIndex() {
        // Clear existing index
        $this->db->exec('DELETE FROM search_index');
        
        // Index quests
        $result = $this->db->query('SELECT quest_id, name, description, type FROM quests');
        while ($quest = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->db->exec(sprintf(
                "INSERT INTO search_index (content_id, content_type, name, description, keywords) VALUES ('%s', 'quest', '%s', '%s', '%s')",
                $this->escapeString($quest['quest_id']),
                $this->escapeString($quest['name']),
                $this->escapeString($quest['description']),
                $this->escapeString($quest['type'])
            ));
        }
        
        // Index NPCs
        $result = $this->db->query('SELECT npc_id, name, description, role FROM npcs');
        while ($npc = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->db->exec(sprintf(
                "INSERT INTO search_index (content_id, content_type, name, description, keywords) VALUES ('%s', 'npc', '%s', '%s', '%s')",
                $this->escapeString($npc['npc_id']),
                $this->escapeString($npc['name']),
                $this->escapeString($npc['description']),
                $this->escapeString($npc['role'])
            ));
        }
        
        // Index locations
        $result = $this->db->query('SELECT location_id, name, description, region FROM locations');
        while ($location = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->db->exec(sprintf(
                "INSERT INTO search_index (content_id, content_type, name, description, keywords) VALUES ('%s', 'location', '%s', '%s', '%s')",
                $this->escapeString($location['location_id']),
                $this->escapeString($location['name']),
                $this->escapeString($location['description']),
                $this->escapeString($location['region'])
            ));
        }
        
        // Index items
        $result = $this->db->query('SELECT item_id, name, description, type FROM items');
        while ($item = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->db->exec(sprintf(
                "INSERT INTO search_index (content_id, content_type, name, description, keywords) VALUES ('%s', 'item', '%s', '%s', '%s')",
                $this->escapeString($item['item_id']),
                $this->escapeString($item['name']),
                $this->escapeString($item['description']),
                $this->escapeString($item['type'])
            ));
        }
    }
    
    private function escapeString($str) {
        if (is_null($str)) return '';
        return SQLite3::escapeString($str);
    }
}
```

## Database Search and Retrieval

The schema includes a dedicated search index using SQLite's FTS5 extension for efficient text search across all game entities.

### 1. Search Implementation

```php
<?php
// models/GameSearch.php
class GameSearch {
    private $db;
    private $game;
    
    public function __construct($game) {
        $this->game = $game;
        $this->db = new SQLite3(BASE_PATH . "/data/game_data/{$game}.sqlite");
        $this->db->enableExceptions(true);
    }
    
    public function search($query, $filters = [], $spoilerLevel = 0) {
        // Sanitize query
        $query = SQLite3::escapeString($query);
        
        // Build WHERE clause for filters
        $whereClause = '';
        $params = [];
        
        if (!empty($filters)) {
            $filterClauses = [];
            foreach ($filters as $key => $value) {
                $filterClauses[] = "content_type = :{$key}";
                $params[":{$key}"] = $value;
            }
            $whereClause = 'AND (' . implode(' OR ', $filterClauses) . ')';
        }
        
        // Execute search query
        $sql = "
            SELECT 
                content_id, 
                content_type, 
                name, 
                snippet(search_index, 2, '<b>', '</b>', '...', 10) AS snippet,
                rank
            FROM search_index
            WHERE search_index MATCH :query {$whereClause}
            ORDER BY rank
            LIMIT 20
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', $query, SQLITE3_TEXT);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        
        // Collect results
        $searchResults = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Fetch the full entity based on content_type and content_id
            $entity = $this->fetchEntity($row['content_type'], $row['content_id'], $spoilerLevel);
            
            if ($entity) {
                $searchResults[] = [
                    'entity' => $entity,
                    'type' => $row['content_type'],
                    'id' => $row['content_id'],
                    'name' => $row['name'],
                    'snippet' => $row['snippet']
                ];
            }
        }
        
        return $searchResults;
    }
    
    private function fetchEntity($type, $id, $spoilerLevel) {
        switch ($type) {
            case 'quest':
                return $this->fetchQuest($id, $spoilerLevel);
            case 'npc':
                return $this->fetchNPC($id);
            case 'location':
                return $this->fetchLocation($id);
            case 'item':
                return $this->fetchItem($id);
            default:
                return null;
        }
    }
    
    private function fetchQuest($questId, $spoilerLevel) {
        $stmt = $this->db->prepare("
            SELECT * FROM quests 
            WHERE quest_id = :id AND spoiler_level <= :spoiler_level
        ");
        $stmt->bindValue(':id', $questId, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $spoilerLevel, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    private function fetchNPC($npcId) {
        $stmt = $this->db->prepare("SELECT * FROM npcs WHERE npc_id = :id");
        $stmt->bindValue(':id', $npcId, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    private function fetchLocation($locationId) {
        $stmt = $this->db->prepare("SELECT * FROM locations WHERE location_id = :id");
        $stmt->bindValue(':id', $locationId, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    private function fetchItem($itemId) {
        $stmt = $this->db->prepare("SELECT * FROM items WHERE item_id = :id");
        $stmt->bindValue(':id', $itemId, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
}
```

### 2. Context-Aware Game Information

To prevent user confusion, the system implements context-aware information retrieval:

```php
<?php
// models/ContextualGameData.php
class ContextualGameData {
    private $db;
    private $game;
    private $userId;
    private $userProgress;
    
    public function __construct($game, $userId) {
        $this->game = $game;
        $this->userId = $userId;
        $this->db = new SQLite3(BASE_PATH . "/data/game_data/{$game}.sqlite");
        $this->db->enableExceptions(true);
        $this->userProgress = new UserProgress();
    }
    
    public function getContextualQuestInfo($questId) {
        // Get user's progress on this quest
        $progress = $this->userProgress->getUserQuestProgress($this->userId, $this->game, $questId);
        
        // Determine appropriate spoiler level based on progress
        $spoilerLevel = 0;
        
        if ($progress) {
            if ($progress['completed']) {
                // User completed the quest, show everything
                $spoilerLevel = 999;
            } else if ($progress['step_id']) {
                // User is on a specific step, show up to that step plus hints for next
                $currentStep = $this->getQuestStep($questId, $progress['step_id']);
                $spoilerLevel = min(2, $currentStep['step_number'] + 1);
            } else {
                // User knows about quest but hasn't started, show basic info
                $spoilerLevel = 1;
            }
        }
        
        // Retrieve quest with appropriate spoiler level
        $quest = $this->getQuest($questId, $spoilerLevel);
        
        // Remove future steps beyond current progress + 1
        if ($progress && $progress['step_id'] && !$progress['completed']) {
            $currentStepNumber = 0;
            
            foreach ($quest['steps'] as $step) {
                if ($step['step_id'] === $progress['step_id']) {
                    $currentStepNumber = $step['step_number'];
                    break;
                }
            }
            
            // Filter steps
            $quest['steps'] = array_filter($quest['steps'], function($step) use ($currentStepNumber) {
                return $step['step_number'] <= $currentStepNumber + 1;
            });
            
            // If there's a next step, limit its information
            foreach ($quest['steps'] as &$step) {
                if ($step['step_number'] > $currentStepNumber) {
                    // Provide hints but not full details
                    $step['description'] = $step['hints'] ?? "Continue the quest...";
                    unset($step['objective']);
                    unset($step['completion_flags']);
                }
            }
        }
        
        return $quest;
    }
    
    public function getQuest($questId, $spoilerLevel = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM quests 
            WHERE quest_id = :quest_id AND spoiler_level <= :spoiler_level
        ");
        $stmt->bindValue(':quest_id', $questId, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $spoilerLevel, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $quest = $result->fetchArray(SQLITE3_ASSOC);
        if (!$quest) {
            return null;
        }
        
        // Get quest steps
        $stmt = $this->db->prepare("
            SELECT * FROM quest_steps 
            WHERE quest_id = :quest_id AND spoiler_level <= :spoiler_level
            ORDER BY step_number
        ");
        $stmt->bindValue(':quest_id', $questId, SQLITE3_TEXT);
        $stmt->bindValue(':spoiler_level', $spoilerLevel, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $steps = [];
        while ($step = $result->fetchArray(SQLITE3_ASSOC)) {
            $steps[] = $step;
        }
        
        $quest['steps'] = $steps;
        
        return $quest;
    }
    
    private function getQuestStep($questId, $stepId) {
        $stmt = $this->db->prepare("
            SELECT * FROM quest_steps 
            WHERE quest_id = :quest_id AND step_id = :step_id
        ");
        $stmt->bindValue(':quest_id', $questId, SQLITE3_TEXT);
        $stmt->bindValue(':step_id', $stepId, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    // Additional contextual retrieval methods...
}
```

## Performance Optimization and Scaling

### 1. SQLite Optimization

SQLite performance is optimized through:

1. **Proper indexing**: Indexes on commonly queried fields
2. **FTS5 for text search**: Efficient full-text search capabilities
3. **Transaction usage**: Batch operations in transactions
4. **Query optimization**: Careful JOIN usage and limiting result sets

### 2. Scaling Path

As the application grows, the following scaling path is recommended:

1. **Split game databases**: Separate database per game (implemented from start)
2. **Read replicas**: For high-traffic games, implement read replicas
3. **Migration to MySQL**: When concurrent connections exceed SQLite capabilities
4. **Caching layer**: Redis for frequently accessed data
5. **CDN for static game data**: CloudFlare for image assets and static JSON

## Conclusion

This comprehensive database schema provides a solid foundation for the Gaming Companion Overlay Tool, with careful consideration to prevent user confusion and provide contextual, relevant information during gameplay.

The design features:

1. **Modular architecture** with separate databases for system and game data
2. **Rich relationship modeling** between quests, NPCs, locations, and items
3. **Progressive information revelation** to prevent spoilers while guiding players
4. **User personalization** through progress tracking and bookmarks
5. **Efficient search** via FTS5 full-text search index
6. **Clear upgrade path** for future scaling

This approach ensures players receive information that is:
- **Relevant**: Contextual to their current progress
- **Clear**: Well-structured with separate fields for descriptions and objectives
- **Navigable**: With proper relationships between game entities
- **Personalized**: Adapted to user preferences and play style

By following this schema, the application will provide a seamless, helpful companion experience for players of both Elden Ring and Baldur's Gate 3.