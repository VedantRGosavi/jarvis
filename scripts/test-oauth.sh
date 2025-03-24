#!/bin/bash

# Script to quickly test OAuth configurations for FridayAI

# Colors for better output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found. Please create it first:${NC}"
    echo -e "cp .env.example .env"
    exit 1
fi

# Function to check required environment variables
check_env_vars() {
    echo -e "\n${BLUE}=== Checking OAuth Configuration ===${NC}"

    # Check Frontend URL
    FRONTEND_URL=$(grep -E "^FRONTEND_URL=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    if [ -z "$FRONTEND_URL" ]; then
        echo -e "${RED}✗ FRONTEND_URL is not set in .env${NC}"
    else
        echo -e "${GREEN}✓ FRONTEND_URL is set to: $FRONTEND_URL${NC}"
    fi

    # Check Google OAuth
    GOOGLE_CLIENT_ID=$(grep -E "^GOOGLE_CLIENT_ID=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    GOOGLE_CLIENT_SECRET=$(grep -E "^GOOGLE_CLIENT_SECRET=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")

    if [ -z "$GOOGLE_CLIENT_ID" ] || [ "$GOOGLE_CLIENT_ID" = "your_google_client_id" ]; then
        echo -e "${RED}✗ Google OAuth is not configured${NC}"
    else
        echo -e "${GREEN}✓ Google OAuth is configured${NC}"
    fi

    # Check GitHub OAuth
    GITHUB_CLIENT_ID=$(grep -E "^GITHUB_CLIENT_ID=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    GITHUB_CLIENT_SECRET=$(grep -E "^GITHUB_CLIENT_SECRET=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")

    if [ -z "$GITHUB_CLIENT_ID" ] || [ "$GITHUB_CLIENT_ID" = "your_github_client_id" ]; then
        echo -e "${RED}✗ GitHub OAuth is not configured${NC}"
    else
        echo -e "${GREEN}✓ GitHub OAuth is configured${NC}"
    fi

    # Check PlayStation OAuth
    PLAYSTATION_CLIENT_ID=$(grep -E "^PLAYSTATION_CLIENT_ID=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    PLAYSTATION_CLIENT_SECRET=$(grep -E "^PLAYSTATION_CLIENT_SECRET=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")

    if [ -z "$PLAYSTATION_CLIENT_ID" ] || [ "$PLAYSTATION_CLIENT_ID" = "your_playstation_client_id" ]; then
        echo -e "${RED}✗ PlayStation OAuth is not configured${NC}"
    else
        echo -e "${GREEN}✓ PlayStation OAuth is configured${NC}"
    fi

    # Check Steam OAuth
    STEAM_API_KEY=$(grep -E "^STEAM_API_KEY=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")

    if [ -z "$STEAM_API_KEY" ] || [ "$STEAM_API_KEY" = "your_steam_api_key" ]; then
        echo -e "${RED}✗ Steam OAuth is not configured${NC}"
    else
        echo -e "${GREEN}✓ Steam OAuth is configured${NC}"
    fi
}

# Function to launch the OAuth test tool
launch_test_tool() {
    echo -e "\n${BLUE}=== OAuth Test Tool ===${NC}"

    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        echo -e "${RED}Error: PHP is not installed or not in PATH${NC}"
        exit 1
    fi

    # Set development mode for testing
    export APP_ENV=development

    # Get the server URL
    FRONTEND_URL=$(grep -E "^FRONTEND_URL=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    if [ -z "$FRONTEND_URL" ]; then
        FRONTEND_URL="http://localhost:8000"
    fi

    # Extract the port from FRONTEND_URL
    PORT=$(echo $FRONTEND_URL | sed -E 's/.*:([0-9]+).*/\1/')
    if [ "$PORT" = "$FRONTEND_URL" ]; then
        # If no port specified, use default
        PORT=8000
    fi

    echo -e "${YELLOW}Starting PHP development server on port $PORT...${NC}"
    echo -e "${GREEN}OAuth Test Tool will be available at: $FRONTEND_URL/app/tools/oauth-test.php${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"

    # Start PHP development server
    php -S "0.0.0.0:$PORT" -t .
}

# Function to display help
show_help() {
    echo -e "\n${BLUE}FridayAI OAuth Test Script${NC}"
    echo -e "\nThis script helps you test your OAuth configurations for FridayAI."
    echo -e "\nUsage: ./scripts/test-oauth.sh [OPTION]"
    echo -e "\nOptions:"
    echo -e "  ${GREEN}check${NC}     Check the OAuth configuration in your .env file"
    echo -e "  ${GREEN}start${NC}     Start the PHP development server and launch the OAuth test tool"
    echo -e "  ${GREEN}help${NC}      Display this help message"
    echo -e "\nExample:"
    echo -e "  ./scripts/test-oauth.sh check"
    echo -e "  ./scripts/test-oauth.sh start"
}

# Main script
if [ "$1" = "check" ]; then
    check_env_vars
elif [ "$1" = "start" ]; then
    check_env_vars
    launch_test_tool
elif [ "$1" = "help" ] || [ -z "$1" ]; then
    show_help
else
    echo -e "${RED}Unknown option: $1${NC}"
    show_help
    exit 1
fi

exit 0
