# FridayAI: Gaming Companion Overlay Tool

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

- **quests**: Main quest information
- **quest_steps**: Detailed quest progression steps
- **locations**: Game areas and points of interest
- **npcs**: Non-player characters
- **items**: Game items
- **npc_locations**: Tracks where NPCs can be found
- **quest_prerequisites**: Tracks requirements for quests
- **quest_consequences**: Tracks effects of quest decisions
- **search_index**: Optimized table for text search

## Development

For examples of how to interact with the databases, see `database/db_example.php`. This script demonstrates common operations like:

- Creating users
- Adding game content
- Searching for information
- Tracking user progress

## Prerequisites

- SQLite3 (command-line tool)
- PHP with SQLite extension (for running example code)

## Working with the Databases

You can use the SQLite command-line tool to interact with the databases directly:

```bash
sqlite3 data/system.sqlite
```

Or use any SQLite GUI tool like DB Browser for SQLite, SQLiteStudio, or TablePlus.