#!/bin/bash

# Create directory structure if it doesn't exist
mkdir -p data
mkdir -p data/game_data
mkdir -p database/schema

# Check if SQLite is installed
if ! command -v sqlite3 &> /dev/null; then
    echo "SQLite3 is not installed. Please install it and try again."
    exit 1
fi

# Create system database
echo "Creating system database..."
sqlite3 data/system.sqlite < database/schema/system_schema.sql

# Create Elden Ring database
echo "Creating Elden Ring database..."
sqlite3 data/game_data/elden_ring.sqlite < database/schema/game_schema.sql

# Create Baldur's Gate 3 database
echo "Creating Baldur's Gate 3 database..."
sqlite3 data/game_data/baldurs_gate3.sqlite < database/schema/game_schema.sql

echo "All databases created successfully!"
echo "You now have:"
echo "- System database: data/system.sqlite"
echo "- Elden Ring database: data/game_data/elden_ring.sqlite"
echo "- Baldur's Gate 3 database: data/game_data/baldurs_gate3.sqlite"
