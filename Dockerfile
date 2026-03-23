FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    COMPOSER_ALLOW_SUPERUSER=1

# System dependencies and PHP extensions used by the app.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        gd \
        intl \
    && a2enmod rewrite headers expires \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}/!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

# Composer official binary.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app source code.
COPY . /var/www/html

# Install project PHP dependencies.
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Ensure write permissions for runtime directories.
RUN mkdir -p storage temp \
    && chown -R www-data:www-data storage temp \
    && chmod -R 775 storage temp

# Production-safe PHP defaults.
RUN { \
    echo "display_errors=Off"; \
    echo "display_startup_errors=Off"; \
    echo "log_errors=On"; \
    echo "expose_php=Off"; \
  } > /usr/local/etc/php/conf.d/99-production.ini

EXPOSE 80
