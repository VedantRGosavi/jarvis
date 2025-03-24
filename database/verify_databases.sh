#!/bin/bash

# Check if SQLite is installed
if ! command -v sqlite3 &> /dev/null; then
    echo "SQLite3 is not installed. Please install it and try again."
    exit 1
fi

# Define color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to verify database access
verify_database() {
    local db_path=$1
    local db_name=$2

    echo "Testing $db_name database ($db_path)..."

    # Test if the database exists
    if [ ! -f "$db_path" ]; then
        echo -e "${RED}Error: $db_path does not exist.${NC}"
        return 1
    fi

    # Test reading from the database by listing tables
    echo "- Tables in database:"
    sqlite3 "$db_path" ".tables"

    # Test writing to the database by creating and dropping a test table
    echo "- Testing write access..."
    sqlite3 "$db_path" "CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT);"
    sqlite3 "$db_path" "INSERT INTO test_table (name) VALUES ('test_value');"
    local count=$(sqlite3 "$db_path" "SELECT COUNT(*) FROM test_table;")
    sqlite3 "$db_path" "DROP TABLE test_table;"

    if [ "$count" -eq "1" ]; then
        echo -e "${GREEN}- Write test successful!${NC}"
        return 0
    else
        echo -e "${RED}- Write test failed!${NC}"
        return 1
    fi
}

# Verify system database
echo "=== Verifying System Database ==="
verify_database "data/system.sqlite" "System"
system_status=$?

# Verify Elden Ring database
echo -e "\n=== Verifying Elden Ring Database ==="
verify_database "data/game_data/elden_ring.sqlite" "Elden Ring"
elden_ring_status=$?

# Verify Baldur's Gate 3 database
echo -e "\n=== Verifying Baldur's Gate 3 Database ==="
verify_database "data/game_data/baldurs_gate3.sqlite" "Baldur's Gate 3"
baldurs_gate_status=$?

# Summary
echo -e "\n=== Summary ==="
if [ $system_status -eq 0 ]; then
    echo -e "${GREEN}System Database: OK${NC}"
else
    echo -e "${RED}System Database: FAIL${NC}"
fi

if [ $elden_ring_status -eq 0 ]; then
    echo -e "${GREEN}Elden Ring Database: OK${NC}"
else
    echo -e "${RED}Elden Ring Database: FAIL${NC}"
fi

if [ $baldurs_gate_status -eq 0 ]; then
    echo -e "${GREEN}Baldur's Gate 3 Database: OK${NC}"
else
    echo -e "${RED}Baldur's Gate 3 Database: FAIL${NC}"
fi

# Final verdict
if [ $system_status -eq 0 ] && [ $elden_ring_status -eq 0 ] && [ $baldurs_gate_status -eq 0 ]; then
    echo -e "\n${GREEN}All databases are correctly set up with read/write access!${NC}"
    exit 0
else
    echo -e "\n${RED}Some databases have issues. Please check the output above.${NC}"
    exit 1
fi
