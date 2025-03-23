#!/bin/bash

# Exit on error
set -e

echo "Game Companion Overlay - Database Schema Update"
echo "==============================================="
echo

ELDEN_RING_DB="data/game_data/elden_ring.sqlite"
BG3_DB="data/game_data/baldurs_gate3.sqlite"

# Check if the databases exist
if [ ! -f "$ELDEN_RING_DB" ] || [ ! -f "$BG3_DB" ]; then
    echo "Error: Database files not found. Please run setup_databases.sh first."
    exit 1
fi

echo "Updating Elden Ring database schema..."
# Run each ALTER statement separately and ignore errors
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE npcs ADD COLUMN category TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE npcs ADD COLUMN image_url TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE npcs ADD COLUMN health TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE npcs ADD COLUMN drops TEXT;" 2>/dev/null || true

sqlite3 "$ELDEN_RING_DB" "ALTER TABLE items ADD COLUMN is_missable INTEGER DEFAULT 0;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE items ADD COLUMN location_note TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE items ADD COLUMN image_path TEXT;" 2>/dev/null || true

sqlite3 "$ELDEN_RING_DB" "ALTER TABLE quests ADD COLUMN time_sensitive INTEGER DEFAULT 0;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE quests ADD COLUMN category TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE quests ADD COLUMN level TEXT;" 2>/dev/null || true
sqlite3 "$ELDEN_RING_DB" "ALTER TABLE quests ADD COLUMN location_note TEXT;" 2>/dev/null || true

# Create classes table if it doesn't exist
sqlite3 "$ELDEN_RING_DB" "CREATE TABLE IF NOT EXISTS classes (
    class_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    stats TEXT,
    equipment TEXT,
    image_url TEXT
);" 2>/dev/null || true

echo "Updating Baldur's Gate 3 database schema..."
# Run each ALTER statement separately and ignore errors
sqlite3 "$BG3_DB" "ALTER TABLE npcs ADD COLUMN category TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE npcs ADD COLUMN image_url TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE npcs ADD COLUMN health TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE npcs ADD COLUMN drops TEXT;" 2>/dev/null || true

sqlite3 "$BG3_DB" "ALTER TABLE items ADD COLUMN is_missable INTEGER DEFAULT 0;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE items ADD COLUMN location_note TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE items ADD COLUMN image_path TEXT;" 2>/dev/null || true

sqlite3 "$BG3_DB" "ALTER TABLE quests ADD COLUMN time_sensitive INTEGER DEFAULT 0;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE quests ADD COLUMN category TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE quests ADD COLUMN level TEXT;" 2>/dev/null || true
sqlite3 "$BG3_DB" "ALTER TABLE quests ADD COLUMN location_note TEXT;" 2>/dev/null || true

echo "Database schema update completed successfully!"
echo
echo "You can now run the data import scripts to populate the new fields." 