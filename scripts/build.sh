#!/bin/bash

# Install PHP dependencies
if [ -f "composer.json" ]; then
  composer install --no-dev --optimize-autoloader
fi

# Build Tailwind CSS
if [ -f "package.json" ]; then
  npm ci
  npm run build:css
fi

# Create .env file from Vercel environment variables if needed
if [ ! -f ".env" ] && [ "$VERCEL_ENV" = "production" ]; then
  echo "Creating .env file from Vercel environment variables"
  echo "APP_NAME=${APP_NAME:-FridayAI}" > .env
  echo "APP_ENV=${APP_ENV:-production}" >> .env
  echo "APP_DEBUG=${APP_DEBUG:-false}" >> .env
  echo "STRIPE_API_KEY=${STRIPE_API_KEY}" >> .env
  echo "STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET}" >> .env
  echo "OPENAI_API_KEY=${OPENAI_API_KEY}" >> .env
  echo "WEBHOOK_URL=${WEBHOOK_URL:-https://fridayai.me/api/webhook}" >> .env
fi

# Ensure directory permissions are set correctly
chmod -R 755 public
chmod -R 755 api

echo "Build completed successfully!"
