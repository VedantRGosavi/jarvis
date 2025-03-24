#!/bin/bash

# Build and prepare the project for deployment
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Verify database structure
cd database && bash ./verify_databases.sh

# Build Tailwind CSS
npm run build:css

# Make the script executable
chmod +x build.sh
