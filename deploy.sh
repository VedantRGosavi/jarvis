#!/bin/bash

# Colors for terminal output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

APP_NAME="fridayai-prod"

echo -e "${YELLOW}Starting deployment process for FridayAI...${NC}"

# Step 1: Verify database structure
echo -e "${YELLOW}Verifying database structure...${NC}"
if [ ! -f "data/system.sqlite" ]; then
    echo -e "${RED}Error: system.sqlite database not found!${NC}"
    exit 1
fi

# Check for game data directory
if [ ! -d "data/game_data" ]; then
    echo -e "${YELLOW}Creating game_data directory...${NC}"
    mkdir -p data/game_data
fi

# Step 2: Install dependencies for production
echo -e "${YELLOW}Installing production dependencies...${NC}"
composer install --no-dev --optimize-autoloader || echo "Composer not installed, skipping..."
npm install
npm run build

# Step 3: Ensure all necessary files are committed
echo -e "${YELLOW}Checking git status...${NC}"
git status

read -p "Continue with deployment? (y/n) " CONT
if [ "$CONT" != "y" ]; then
    echo -e "${RED}Deployment aborted.${NC}"
    exit 1
fi

# Step 4: Create Heroku app if it doesn't exist
echo -e "${YELLOW}Checking for existing Heroku app...${NC}"
heroku apps:info --app $APP_NAME || {
    echo -e "${YELLOW}Creating Heroku app...${NC}"
    heroku create $APP_NAME

    echo -e "${YELLOW}Adding PostgreSQL add-on...${NC}"
    heroku addons:create heroku-postgresql:hobby-dev --app $APP_NAME
}

# Step 5: Configure Heroku environment variables
echo -e "${YELLOW}Setting up environment variables...${NC}"
heroku config:set APP_ENV=production --app $APP_NAME
heroku config:set APP_DEBUG=false --app $APP_NAME
heroku config:set S3_BUCKET=fridayai-downloads-2025 --app $APP_NAME
heroku config:set S3_REGION=us-east-1 --app $APP_NAME

# Step 6: Deploy to Heroku
echo -e "${YELLOW}Deploying to Heroku...${NC}"
git push heroku main

# Step 7: Run post-deployment tasks
echo -e "${YELLOW}Running post-deployment tasks...${NC}"
heroku run php artisan config:cache
heroku run php artisan route:cache

# Step 8: Open the application in a browser
echo -e "${GREEN}Deployment completed! Opening application...${NC}"
heroku open --app $APP_NAME

echo -e "${GREEN}Deployment successful!${NC}"
echo -e "${YELLOW}To monitor the application, run:${NC} heroku logs --tail --app $APP_NAME"
