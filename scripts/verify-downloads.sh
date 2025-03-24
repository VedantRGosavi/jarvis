#!/bin/bash

# Set colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
DOWNLOADS_DIR="$ROOT_DIR/downloads"

echo -e "${YELLOW}Verifying FridayAI Download Files${NC}"

# Check if all required files exist
REQUIRED_FILES=(
    "FridayAI-Win-latest.zip"
    "FridayAI-Win-beta.zip"
    "FridayAI-Mac-latest.dmg"
    "FridayAI-Mac-beta.dmg"
    "FridayAI-Linux-latest.tar.gz"
    "FridayAI-Linux-beta.tar.gz"
    "checksums.txt"
)

echo -e "\n${YELLOW}1. Checking for required files:${NC}"
all_files_exist=true

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$DOWNLOADS_DIR/$file" ]; then
        size=$(du -h "$DOWNLOADS_DIR/$file" | cut -f1)
        echo -e "  ${GREEN}✓${NC} $file exists ($size)"
    else
        echo -e "  ${RED}✗${NC} $file is missing"
        all_files_exist=false
    fi
done

if [ "$all_files_exist" = false ]; then
    echo -e "\n${RED}Error: Some required files are missing. Run the build-installers.sh script to generate them.${NC}"
    exit 1
fi

# Verify file sizes are reasonable (not placeholder/empty files)
echo -e "\n${YELLOW}2. Verifying file sizes:${NC}"
all_sizes_ok=true

for file in "${REQUIRED_FILES[@]}"; do
    if [ "$file" = "checksums.txt" ]; then
        continue  # Skip checksums file
    fi

    size_bytes=$(stat -f%z "$DOWNLOADS_DIR/$file")

    if [ "$size_bytes" -lt 1000 ]; then
        echo -e "  ${RED}✗${NC} $file is too small ($size_bytes bytes) - likely a placeholder"
        all_sizes_ok=false
    else
        echo -e "  ${GREEN}✓${NC} $file size is reasonable ($size_bytes bytes)"
    fi
done

if [ "$all_sizes_ok" = false ]; then
    echo -e "\n${RED}Error: Some files appear to be placeholders. Run build-installers.sh to generate proper files.${NC}"
    exit 1
fi

# Verify checksums match
echo -e "\n${YELLOW}3. Verifying file checksums:${NC}"
all_checksums_ok=true

while IFS= read -r line; do
    # Skip comment lines and empty lines
    if [[ "$line" =~ ^#.*$ || -z "$line" ]]; then
        continue
    fi

    # Extract checksum and filename
    checksum=$(echo "$line" | awk '{print $1}')
    filename=$(echo "$line" | awk '{print $2}')

    if [ -f "$DOWNLOADS_DIR/$filename" ]; then
        calculated_checksum=$(shasum -a 256 "$DOWNLOADS_DIR/$filename" | awk '{print $1}')

        if [ "$calculated_checksum" = "$checksum" ]; then
            echo -e "  ${GREEN}✓${NC} $filename checksum verified"
        else
            echo -e "  ${RED}✗${NC} $filename checksum mismatch"
            echo -e "     Expected: $checksum"
            echo -e "     Actual:   $calculated_checksum"
            all_checksums_ok=false
        fi
    fi
done < "$DOWNLOADS_DIR/checksums.txt"

if [ "$all_checksums_ok" = false ]; then
    echo -e "\n${RED}Error: Some file checksums do not match. Run build-installers.sh to regenerate files and checksums.${NC}"
    exit 1
fi

# All checks passed
echo -e "\n${GREEN}✓ All download files verified successfully!${NC}"
echo -e "${GREEN}✓ Files are ready for production use.${NC}"

exit 0
