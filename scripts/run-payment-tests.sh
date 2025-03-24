#!/bin/bash

# Script to run all Stripe payment integration tests

# Text formatting
BOLD='\033[1m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BOLD}${YELLOW}⚠️  WARNING: RUNNING STRIPE PAYMENT TESTS ⚠️${NC}"
echo -e "This script will run tests using ${BOLD}your actual Stripe account${NC}."
echo -e "These tests will create test customers, products, and subscriptions in your Stripe account."
echo -e "While no actual charges will be made (test cards are used), real data will be created."
echo ""
echo -e "${BOLD}Do you want to continue? (y/n)${NC}"
read -r response
if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])+$ ]]; then
    echo "Tests cancelled."
    exit 0
fi

# Navigate to the base directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR/.." || { echo "Failed to navigate to base directory"; exit 1; }

# Ensure .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found in project root.${NC}"
    echo "Please create a .env file with your Stripe API keys."
    exit 1
fi

# Check for required PHP extensions
echo -e "\n${BLUE}Checking PHP extensions...${NC}"
for ext in curl json sqlite3; do
    if php -r "echo extension_loaded('$ext') ? 'yes' : 'no';" | grep -q "no"; then
        echo -e "${RED}Error: PHP $ext extension is required but not installed.${NC}"
        exit 1
    else
        echo -e "${GREEN}✓ PHP $ext extension is installed${NC}"
    fi
done

# Check for Stripe keys
echo -e "\n${BLUE}Checking Stripe configuration...${NC}"
STRIPE_SECRET_KEY=$(grep -o 'STRIPE_SECRET_KEY=.*' .env | cut -d '=' -f2)
STRIPE_WEBHOOK_SECRET=$(grep -o 'STRIPE_WEBHOOK_SECRET=.*' .env | cut -d '=' -f2)

if [ -z "$STRIPE_SECRET_KEY" ]; then
    echo -e "${RED}Error: STRIPE_SECRET_KEY not found in .env file.${NC}"
    exit 1
else
    echo -e "${GREEN}✓ STRIPE_SECRET_KEY found${NC}"
fi

if [ -z "$STRIPE_WEBHOOK_SECRET" ]; then
    echo -e "${YELLOW}Warning: STRIPE_WEBHOOK_SECRET not found in .env file.${NC}"
    echo "Some webhook tests may fail."
else
    echo -e "${GREEN}✓ STRIPE_WEBHOOK_SECRET found${NC}"
fi

# Run tests with proper formatting
run_test() {
    test_name=$1
    script_path=$2

    echo -e "\n${BOLD}${BLUE}Running Test: $test_name${NC}"
    echo -e "${BLUE}$(printf '=%.0s' {1..50})${NC}"

    if php "$script_path"; then
        echo -e "${GREEN}✓ $test_name test passed${NC}"
        return 0
    else
        echo -e "${RED}✗ $test_name test failed${NC}"
        return 1
    fi
}

# Run all tests
test1_result=0
test2_result=0
test3_result=0

run_test "Basic Stripe Integration" "scripts/payment-test.php"
test1_result=$?

run_test "Trial to Paid Subscription" "scripts/trial-to-paid-test.php"
test2_result=$?

run_test "Webhook Event Handling" "scripts/webhook-test.php"
test3_result=$?

# Summary
echo -e "\n${BOLD}${BLUE}Test Summary${NC}"
echo -e "${BLUE}$(printf '=%.0s' {1..50})${NC}"

if [ $test1_result -eq 0 ]; then
    echo -e "${GREEN}✓ Basic Stripe Integration: PASSED${NC}"
else
    echo -e "${RED}✗ Basic Stripe Integration: FAILED${NC}"
fi

if [ $test2_result -eq 0 ]; then
    echo -e "${GREEN}✓ Trial to Paid Subscription: PASSED${NC}"
else
    echo -e "${RED}✗ Trial to Paid Subscription: FAILED${NC}"
fi

if [ $test3_result -eq 0 ]; then
    echo -e "${GREEN}✓ Webhook Event Handling: PASSED${NC}"
else
    echo -e "${RED}✗ Webhook Event Handling: FAILED${NC}"
fi

if [ $test1_result -eq 0 ] && [ $test2_result -eq 0 ] && [ $test3_result -eq 0 ]; then
    echo -e "\n${GREEN}${BOLD}All tests passed successfully!${NC}"
    exit_code=0
else
    echo -e "\n${RED}${BOLD}Some tests failed. See details above.${NC}"
    exit_code=1
fi

echo -e "\n${YELLOW}Note: Test data has been preserved in your Stripe account for review.${NC}"
echo -e "See scripts/README-payment-tests.md for cleanup instructions."

exit $exit_code
