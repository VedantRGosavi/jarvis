#!/bin/bash

# Build the front-end assets
echo "Building front-end assets..."
npm run build:css

# Set PHP version explicitly
export PHP_VERSION=7.4

# Create a custom PHP configuration
echo "Creating PHP configuration..."
mkdir -p /tmp/php
cat > /tmp/php/php.ini << EOL
extension=openssl.so
extension=pdo.so
extension=pdo_sqlite.so
extension=sqlite3.so
extension=json.so
EOL

# Download composer with specific PHP version and configuration
echo "Downloading composer..."
PHP_INI_SCAN_DIR=/tmp/php curl -sS https://getcomposer.org/installer | php7.4 -- --install-dir=/tmp --filename=composer

# Install PHP dependencies
echo "Installing PHP dependencies..."
PHP_INI_SCAN_DIR=/tmp/php php7.4 /tmp/composer install --no-dev --optimize-autoloader

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
