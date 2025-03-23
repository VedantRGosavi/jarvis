#!/bin/bash

# Exit on error
set -e

echo "Game Companion Overlay - Data Setup"
echo "===================================="
echo

# Check if the necessary databases exist
if [ ! -f "data/game_data/elden_ring.sqlite" ] || [ ! -f "data/game_data/baldurs_gate3.sqlite" ]; then
    echo "Database files not found. Setting up databases first..."
    bash database/setup_databases.sh
else
    echo "Database files found. Proceeding with data import."
fi

# Import Elden Ring data
echo
echo "Importing Elden Ring data..."
php scripts/import_elden_ring_data.php

# Import Baldur's Gate 3 data
echo
echo "Importing Baldur's Gate 3 data..."
php scripts/import_baldurs_gate3_data.php

# Verify the database structure and data
echo
echo "Verifying database integrity..."
bash database/verify_databases.sh

echo
echo "Data setup completed successfully!"
echo
echo "You now have fully populated databases for:"
echo "- Elden Ring (data/game_data/elden_ring.sqlite)"
echo "- Baldur's Gate 3 (data/game_data/baldurs_gate3.sqlite)"
echo
echo "You can now start the application and use all features." 