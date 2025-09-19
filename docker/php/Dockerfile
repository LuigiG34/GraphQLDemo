FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    git icu-dev libpq-dev libzip-dev oniguruma-dev autoconf gcc g++ make bash

# PHP extensions you typically need with Symfony + GraphQL
RUN docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) \
    intl pdo_pgsql opcache zip

# GD (optional but often handy)
RUN apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) gd

# Composer
ENV COMPOSER_HOME=/tmp/composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Opcache recommended defaults for dev (fast enough, auto-reload)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/html

# Faster dev: install vendor once if composer.json exists
# (will still use bind mount; volume keeps vendor between rebuilds)
RUN set -xe; \
    if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-scripts || true; fi

CMD ["php-fpm", "-F"]
