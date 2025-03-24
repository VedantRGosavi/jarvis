#!/bin/bash

# Build the front-end assets
echo "Building front-end assets..."
npm run build:css

# Set PHP version explicitly
export PHP_VERSION=8.2

# Create a custom PHP configuration
echo "Creating PHP configuration..."
mkdir -p /tmp/php
cat > /tmp/php/php.ini << EOL
[PHP]
display_errors = Off
log_errors = On
error_log = /tmp/php_errors.log
memory_limit = 128M

[OpenSSL]
openssl.cafile = /etc/ssl/certs/ca-certificates.crt

[Extensions]
extension=openssl.so
extension=pdo.so
extension=pdo_sqlite.so
extension=sqlite3.so
extension=json.so
EOL

# Download composer
echo "Downloading composer..."
curl -sS https://getcomposer.org/installer | \
PHP_INI_SCAN_DIR=/tmp/php \
php -- --install-dir=/tmp --filename=composer

# Install PHP dependencies
echo "Installing PHP dependencies..."
PHP_INI_SCAN_DIR=/tmp/php \
php /tmp/composer install --no-dev --optimize-autoloader

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
