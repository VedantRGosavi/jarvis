#!/bin/bash

# Install PHP and its dependencies
apt-get update && apt-get install -y \
    php \
    php-cli \
    php-fpm \
    php-json \
    php-common \
    php-mysql \
    php-zip \
    php-gd \
    php-mbstring \
    php-curl \
    php-xml \
    php-bcmath \
    php-json

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chmod -R 755 .

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
