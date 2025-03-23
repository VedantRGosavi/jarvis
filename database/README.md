# Gaming Companion Overlay Tool - Database Setup

This directory contains all database-related files for the Gaming Companion Overlay Tool.

## Database Structure

The application uses three separate SQLite databases:

1. **System Database** (`data/system.sqlite`): Stores user accounts, settings, and usage data
2. **Elden Ring Database** (`data/game_data/elden_ring.sqlite`): Game data for Elden Ring
3. **Baldur's Gate 3 Database** (`data/game_data/baldurs_gate3.sqlite`): Game data for Baldur's Gate 3

## Database Utility Class

The Database utility class (`utils/Database.php`) provides a simple interface for accessing both the system database and game-specific databases:

```php
// Get system database instance
$systemDb = Database::getSystemInstance();

// Get Elden Ring database instance
$eldenRingDb = Database::getGameInstance('elden_ring');

// Get Baldur's Gate 3 database instance
$bg3Db = Database::getGameInstance('baldurs_gate3');
```

This class implements a singleton pattern to ensure only one connection is established per database.

## Schema Files

- `schema/system_schema.sql`: SQL schema for the system database
- `schema/game_schema.sql`: SQL schema for game-specific databases

## Setup Instructions

1. Create the database files:

```bash
bash setup_databases.sh
```

2. Verify the databases are working correctly:

```bash
bash verify_databases.sh
```

## Example Code

Check the `db_example.php` file for working examples of database operations.

This file demonstrates:
- Creating user accounts in the system database
- Updating user settings
- Adding game data (quests, locations, etc.)
- Searching and retrieving game data

## Database Schema Overview

### System Database Tables

- `users`: User accounts
- `subscriptions`: Subscription information
- `purchases`: One-time purchases
- `user_settings`: User preferences
- `user_game_progress`: Tracks user progress in games
- `user_bookmarks`: User-saved content
- `usage_logs`: User activity for analytics

### Game Database Tables

- `quests`: Main quest information
- `quest_steps`: Detailed quest progression steps
- `locations`: Game areas and points of interest
- `npcs`: Non-player characters
- `items`: Game items
- `npc_locations`: Tracks NPC locations during different quest stages
- `quest_prerequisites`: Tracks requirements for quests
- `quest_consequences`: Tracks effects of quest decisions
- `search_index`: Optimized table for text search

## Database File Locations

- System database: `data/system.sqlite`
- Elden Ring database: `data/game_data/elden_ring.sqlite`
- Baldur's Gate 3 database: `data/game_data/baldurs_gate3.sqlite`

## Working with the Databases Directly

You can use the SQLite3 command-line tool to interact with the databases directly:

```bash
# Open system database
sqlite3 data/system.sqlite

# Open Elden Ring database
sqlite3 data/game_data/elden_ring.sqlite

# Open Baldur's Gate 3 database
sqlite3 data/game_data/baldurs_gate3.sqlite
```

Once in the SQLite prompt, you can run commands like:

```sql
-- List all tables
.tables

-- Show table structure
.schema quests

-- Query data
SELECT * FROM quests LIMIT 5;

-- Exit SQLite
.exit
```

## Using SQLite GUI Tools

There are several GUI tools available for working with SQLite databases:

- [DB Browser for SQLite](https://sqlitebrowser.org/) (cross-platform)
- [SQLiteStudio](https://sqlitestudio.pl/) (cross-platform)
- [TablePlus](https://tableplus.com/) (Mac, Windows, Linux)

These tools make it easier to browse, edit, and query the databases visually. 