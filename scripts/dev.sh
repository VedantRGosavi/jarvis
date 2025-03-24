#!/bin/bash

# Navigate to project root directory
cd "$(dirname "$0")/.."

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP to run the development server."
    exit 1
fi

# Start PHP development server
echo "Starting development server at http://localhost:8000"
php -S localhost:8000 -t public
