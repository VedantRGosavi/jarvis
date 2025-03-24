#!/bin/bash

# Build the front-end assets
echo "Building front-end assets..."
npm run build:css

# Download latest composer
echo "Downloading composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer

# Install PHP dependencies using a specific version of PHP if available
echo "Installing PHP dependencies..."
if command -v php8.0 &> /dev/null; then
    /tmp/composer install --no-dev --optimize-autoloader
elif command -v php7.4 &> /dev/null; then
    php7.4 /tmp/composer install --no-dev --optimize-autoloader
else
    /tmp/composer install --no-dev --optimize-autoloader
fi

# Create dist directory if it doesn't exist
mkdir -p dist

# Copy necessary files to dist
echo "Copying files to dist..."
cp -r public/* dist/
cp -r api dist/
cp -r app dist/
cp -r vendor dist/
cp .env dist/ || true

echo "Build completed!"
