FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libxml2-dev \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-install -j$(nproc) \
        curl \
        mbstring \
        xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p public/css public/js bootstrap/cache \
    && cp resources/css/app.css public/css/app.css && cp resources/js/app.js public/js/app.js \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php=Off" >> "$PHP_INI_DIR/conf.d/demo-app.ini" \
    && echo "display_errors=Off" >> "$PHP_INI_DIR/conf.d/demo-app.ini"

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
