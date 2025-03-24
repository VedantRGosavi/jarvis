FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring exif pcntl bcmath gd zip

# Install AWS CLI
RUN apt-get update && \
    apt-get install -y \
    python3 \
    python3-pip \
    && pip3 install awscli

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf
RUN mv /var/www/html /var/www/public

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage /var/www/data

# Create data directories if they don't exist
RUN mkdir -p /var/www/data/game_data
RUN chmod -R 777 /var/www/data

# Copy Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf || echo "Apache config file not found, using default"

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
