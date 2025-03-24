#!/bin/bash

# Install npm dependencies
npm install

# Build Tailwind CSS
npm run build:css

# Make the script executable
chmod +x build.sh
