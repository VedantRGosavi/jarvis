#!/bin/bash

# Set colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${YELLOW}FridayAI Download Files Deployment${NC}"

# Check if running with correct permissions
if [ "$(id -u)" != "0" ] && [ "$EUID" != "0" ]; then
    echo -e "${YELLOW}Notice: This script might need elevated permissions to deploy files to production.${NC}"
    echo -e "You might be asked for your password if needed.\n"
fi

# First, make sure all files are ready
echo -e "${YELLOW}Step 1: Verifying download files...${NC}"
if ! "$SCRIPT_DIR/verify-downloads.sh"; then
    echo -e "\n${RED}Deployment aborted due to verification failure.${NC}"
    exit 1
fi

# Ask for deployment confirmation
echo
echo -e "${YELLOW}Step 2: Confirm deployment${NC}"
echo "This will deploy the following download files to production:"
ls -lh "$ROOT_DIR/downloads" | grep -v ".bak" | grep -v ".DS_Store"
echo
read -p "Do you want to continue with deployment? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Deployment cancelled.${NC}"
    exit 0
fi

# Create a backup of current production files
echo -e "${YELLOW}Step 3: Creating backup of current production files...${NC}"
TIMESTAMP=$(date +"%Y%m%d-%H%M%S")
BACKUP_DIR="$ROOT_DIR/downloads/backup-$TIMESTAMP"
mkdir -p "$BACKUP_DIR"

# In production, change this to the real production directory
# DEPLOY_DIR="/var/www/html/fridayai/downloads"
DEPLOY_DIR="$ROOT_DIR/deploy/production/downloads"

# Copy current production files to backup
if [ -d "$DEPLOY_DIR" ] && [ "$(ls -A "$DEPLOY_DIR" 2>/dev/null)" ]; then
    cp $DEPLOY_DIR/* "$BACKUP_DIR/" 2>/dev/null
    echo -e "${GREEN}Backup created at:${NC} $BACKUP_DIR"
else
    echo -e "${YELLOW}No production files found - skipping backup${NC}"
fi

# Deploy files to production
echo -e "${YELLOW}Step 4: Deploying files to production...${NC}"

# Check if deploy directory exists, create if needed
if [ ! -d "$DEPLOY_DIR" ]; then
    echo "Creating deployment directory: $DEPLOY_DIR"
    mkdir -p "$DEPLOY_DIR"
fi

# Copy files to production
cp "$ROOT_DIR/downloads/"*.dmg "$ROOT_DIR/downloads/"*.zip "$ROOT_DIR/downloads/"*.tar.gz "$ROOT_DIR/downloads/checksums.txt" "$DEPLOY_DIR/"

# Set proper permissions
chmod 644 "$DEPLOY_DIR/"*

echo -e "${GREEN}✓ Files successfully deployed to production!${NC}"
echo -e "${GREEN}✓ FridayAI downloads are now ready for users.${NC}"

exit 0
