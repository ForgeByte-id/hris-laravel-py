FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Clear cached config on startup so docker-compose environment variables
# (FACE_SERVICE_URL, FACE_TEMP_DIR, etc.) are always picked up live.
# This runs before php-fpm starts.
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
