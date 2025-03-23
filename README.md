# FridayAI: Gaming Companion Overlay Tool
![image](https://github.com/user-attachments/assets/8153e61a-b402-4e96-b85b-2c6ca37bd3d6)

FridayAI is a comprehensive gaming companion tool that provides in-game overlay information for RPG games like Elden Ring and Baldur's Gate 3.

## Database Setup

This project uses SQLite databases to store both system and game-specific data. The database structure follows the schema defined in `database-schema.md`.

### Databases Overview

Three separate SQLite databases are used:

1. **System Database** (`data/system.sqlite`): Manages user accounts, progress tracking, and personalization
2. **Elden Ring Database** (`data/game_data/elden_ring.sqlite`): Contains all game content for Elden Ring
3. **Baldur's Gate 3 Database** (`data/game_data/baldurs_gate3.sqlite`): Contains all game content for Baldur's Gate 3

### Setting Up Databases

The databases can be created using the included setup script:

```bash
./database/setup_databases.sh
```

To verify that the databases are working correctly:

```bash
./database/verify_databases.sh
```

For detailed information about working with the databases, see the `database/README.md` file.

### Importing Game Data

To populate the databases with actual game content for Elden Ring and Baldur's Gate 3, use the data import script:

```bash
./scripts/setup_game_data.sh
```

This script will:
1. Create the database files if they don't exist
2. Import locations, NPCs, items, bosses, creatures, starting classes, and quests for Elden Ring using public API data
3. Import manually curated data for Baldur's Gate 3
4. Create search indexes for fast in-game lookups
5. Verify the integrity of all databases

For Elden Ring, data is sourced from the Elden Ring Fan API and the Elden Ring API GitHub repository. For Baldur's Gate 3, data is manually curated from various wikis and game guides.

### Updating Game Data

If you already have the databases set up and want to update with the latest content, use the update script:

```bash
./scripts/update_game_data.sh
```

This script will:
1. Update the database schema if needed
2. Create backups of your existing databases
3. Import new data without affecting existing records
4. Verify database integrity

## Data Sources

The game data is sourced from the following locations:

### Elden Ring Data

- **Primary API**: [Elden Ring Fan API](https://eldenring.fanapis.com/)
- **Complete Game Data**: [Elden Ring API GitHub](https://github.com/deliton/eldenring-api)
  - Bosses, Creatures, NPCs
  - Weapons, Shields, Armors
  - Spells (Incantations, Sorceries)
  - Spirit Ashes
  - Character Classes
  - Locations and more
- **Additional Content**: Community wikis and official game guides

### Baldur's Gate 3 Data

- **Primary Content**: Manually curated from official wiki sources
- **Missable Items/Quests**: [BG3 Missables GitHub](https://github.com/plasticmacaroni/bg3-missables)
- **Additional Resources**: [BG3 Missables Website](https://plasticmacaroni.github.io/bg3-missables/)

## Database Structure

### System Database Tables

- **users**: User account information
- **subscriptions**: User subscription details
- **purchases**: One-time purchase records
- **user_settings**: User preferences
- **user_game_progress**: Tracks user progress in games
- **user_bookmarks**: Stores user-saved content
- **usage_logs**: Records user activity for analytics

### Game Database Tables

Both game databases share the same structure:

- **quests**: Main quest information with time_sensitive flag for missable quests
- **quest_steps**: Detailed quest progression steps
- **locations**: Game areas and points of interest
- **npcs**: Non-player characters, bosses, and creatures with health and drops information
- **items**: Game items including weapons, armor, spells, and more
- **npc_locations**: Tracks where NPCs can be found
- **quest_prerequisites**: Tracks requirements for quests
- **quest_consequences**: Tracks effects of quest decisions
- **search_index**: Optimized table for text search

Elden Ring database additional tables:
- **classes**: Character starting classes with stats and equipment

## API Endpoints

The backend provides several API endpoints for accessing game data:

- `/api/games/{game_id}` - Get general game information
- `/api/games/{game_id}/quests` - List all quests or get a specific quest
  - Filter: `?time_sensitive=1` - Show only missable quests
  - Filter: `?category=Missable` - Show quests in the Missable category
- `/api/games/{game_id}/items` - List all items or get a specific item
  - Filter: `?missable=1` - Show only missable items
  - Filter: `?rarity=legendary` - Filter by item rarity
  - Filter: `?type=Weapon` - Filter by item type (Weapon, Armor, Shield, Spell, etc.)
- `/api/games/{game_id}/locations` - List all locations or get a specific location
- `/api/games/{game_id}/npcs` - List all NPCs or get a specific NPC
  - Filter: `?category=boss` - Show only boss NPCs
  - Filter: `?category=creature` - Show only creatures
  - Filter: `?hostile=1` - Show only hostile NPCs
- `/api/games/{game_id}/search` - Search across all game content
- `/api/games/{game_id}/classes` - List all character classes (Elden Ring only)

## Development

For examples of how to interact with the databases, see `database/db_example.php`. This script demonstrates common operations like:

- Creating users
- Adding game content
- Searching for information
- Tracking user progress

## Prerequisites

- SQLite3 (command-line tool)
- PHP 8.1+ with SQLite extension
- cURL extension for PHP (for data import scripts)

## Working with the Databases

You can use the SQLite command-line tool to interact with the databases directly:

```bash
sqlite3 data/system.sqlite
```

Or use any SQLite GUI tool like DB Browser for SQLite, SQLiteStudio, or TablePlus.
