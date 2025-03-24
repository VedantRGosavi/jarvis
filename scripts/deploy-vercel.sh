#!/bin/bash

# Build Tailwind CSS
echo "Building Tailwind CSS..."
npm run build:css

# Install dependencies
echo "Installing dependencies..."
npm install
composer install --no-dev --optimize-autoloader

# Deploy to Vercel
echo "Deploying to Vercel..."
vercel --prod

echo "Done!"
