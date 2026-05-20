FROM php:8.2-apache

ENV APP_ENV=production \
    APP_DEBUG=false \
    MYSQLDUMP_PATH=/usr/bin/mysqldump

RUN a2enmod rewrite headers

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
        ca-certificates \
        default-mysql-client \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && sed -i 's|</VirtualHost>|\tRedirectMatch ^/$ /conectaosc/\n</VirtualHost>|' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/conectaosc

COPY composer.json composer.lock* ./
RUN if [ -f composer.lock ]; then \
        php -r 'json_decode(file_get_contents("composer.lock")); if (json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, "composer.lock invalido: " . json_last_error_msg() . PHP_EOL); exit(1); }'; \
    fi \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist

COPY . .

RUN cp cron/crontab /etc/cron.d/conectaosc \
    && chmod 0644 /etc/cron.d/conectaosc \
    && chmod +x docker-entrypoint.sh \
    && cp docker-entrypoint.sh /usr/local/bin/conectaosc-entrypoint.sh \
    && chmod +x /usr/local/bin/conectaosc-entrypoint.sh

RUN mkdir -p /var/lib/conectaosc/img-backup \
    && if [ -d app/assets/img ]; then cp -r app/assets/img/. /var/lib/conectaosc/img-backup/; fi

RUN mkdir -p storage temp api/cron/logs app/assets/img/fotos \
    && chown -R www-data:www-data /var/www/html/conectaosc storage temp api/cron/logs app/assets/img/fotos

EXPOSE 80

CMD ["/usr/local/bin/conectaosc-entrypoint.sh"]
