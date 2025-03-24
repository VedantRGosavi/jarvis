#!/bin/bash

# Set colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check Heroku CLI is installed
if ! command -v heroku &> /dev/null; then
    echo -e "${RED}Error: Heroku CLI not found.${NC}"
    echo -e "Please install the Heroku CLI: https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

# Check if logged in to Heroku
heroku whoami &> /dev/null
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}You are not logged in to Heroku. Please log in:${NC}"
    heroku login
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to log in to Heroku. Exiting.${NC}"
        exit 1
    fi
fi

# Set Heroku app name (can be customized)
HEROKU_APP=${HEROKU_APP:-"fridayai-prod"}

# Check if app exists
heroku apps:info --app "$HEROKU_APP" &> /dev/null
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}App '$HEROKU_APP' does not exist. Would you like to create it? (y/n)${NC}"
    read -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Creating Heroku app: $HEROKU_APP${NC}"
        heroku create "$HEROKU_APP"

        echo -e "${YELLOW}Adding PostgreSQL add-on...${NC}"
        heroku addons:create heroku-postgresql:hobby-dev --app "$HEROKU_APP"

        # Ask for S3 configuration
        echo -e "${YELLOW}Please enter your S3 configuration:${NC}"

        echo -n "S3 Bucket Name: "
        read S3_BUCKET

        echo -n "S3 Region (default: us-east-1): "
        read S3_REGION
        S3_REGION=${S3_REGION:-"us-east-1"}

        echo -n "AWS Access Key ID: "
        read AWS_ACCESS_KEY_ID

        echo -n "AWS Secret Access Key: "
        read -s AWS_SECRET_ACCESS_KEY
        echo

        # Set config vars
        echo -e "${YELLOW}Setting environment variables...${NC}"
        heroku config:set S3_BUCKET="$S3_BUCKET" --app "$HEROKU_APP"
        heroku config:set S3_REGION="$S3_REGION" --app "$HEROKU_APP"
        heroku config:set AWS_ACCESS_KEY_ID="$AWS_ACCESS_KEY_ID" --app "$HEROKU_APP"
        heroku config:set AWS_SECRET_ACCESS_KEY="$AWS_SECRET_ACCESS_KEY" --app "$HEROKU_APP"
        heroku config:set APP_ENV=production --app "$HEROKU_APP"
        heroku config:set APP_DEBUG=false --app "$HEROKU_APP"
    else
        echo -e "${YELLOW}Deployment cancelled.${NC}"
        exit 0
    fi
fi

# Get S3 bucket from environment or use default
S3_BUCKET=${S3_BUCKET:-"fridayai-downloads-20250324"}
S3_REGION=${S3_REGION:-"us-east-1"}

# Push to Heroku
echo -e "${YELLOW}Deploying to Heroku...${NC}"
git push heroku main

if [ $? -ne 0 ]; then
    echo -e "${RED}Deployment failed. Check the error messages above.${NC}"
    exit 1
fi

# Ask to run build script
echo -e "${YELLOW}Would you like to build and upload installers to S3? (y/n)${NC}"
read -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Building and uploading installers...${NC}"
    heroku run bash scripts/build-installers.sh --force-upload --app "$HEROKU_APP"
fi

echo -e "${GREEN}Deployment complete!${NC}"
echo -e "Your app is running at: https://$HEROKU_APP.herokuapp.com"
exit 0
