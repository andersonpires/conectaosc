FROM php:8.2-apache

# Enable mod_rewrite for .htaccess routing
RUN a2enmod rewrite

# Install system libraries and PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Redirect root to app
RUN sed -i 's|</VirtualHost>|\tRedirectMatch ^/$ /conectaosc/\n</VirtualHost>|' /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Workdir em /var/www/html/conectaosc para que basename() retorne 'conectaosc'
# e o app calcule corretamente a URL base como /conectaosc
WORKDIR /var/www/html/conectaosc

# Install dependencies (layer separado para cache)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application
COPY . .

# Create writable directories
RUN mkdir -p storage temp \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
