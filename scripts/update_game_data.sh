#!/bin/bash

# Exit on error
set -e

echo "Game Companion Overlay - Data Update"
echo "===================================="
echo

# Check if the necessary databases exist
if [ ! -f "data/game_data/elden_ring.sqlite" ] || [ ! -f "data/game_data/baldurs_gate3.sqlite" ]; then
    echo "Database files not found. Setting up databases first..."
    bash database/setup_databases.sh
else
    echo "Database files found. Proceeding with schema update."
fi

# Update database schema
echo
echo "Updating database schema..."
bash database/update_schema.sh

# Backup existing databases before import
echo
echo "Creating database backups..."
cp data/game_data/elden_ring.sqlite data/game_data/elden_ring.sqlite.bak
cp data/game_data/baldurs_gate3.sqlite data/game_data/baldurs_gate3.sqlite.bak
echo "Backups created as:"
echo "- data/game_data/elden_ring.sqlite.bak"
echo "- data/game_data/baldurs_gate3.sqlite.bak"

# Import Elden Ring data
echo
echo "Importing Elden Ring data..."
php scripts/import_elden_ring_data.php

# Import Baldur's Gate 3 data
echo
echo "Importing Baldur's Gate 3 data..."
php scripts/import_baldurs_gate3_data.php

# Verify the database integrity
echo
echo "Verifying database integrity..."
bash database/verify_databases.sh

echo
echo "Data update completed successfully!"
echo
echo "The databases have been updated with new content:"
echo "- Elden Ring: Added boss data from eldenring-api"
echo "- Baldur's Gate 3: Added missable quests and items from bg3-missables"
echo
echo "You can now restart the application to see the new content." 