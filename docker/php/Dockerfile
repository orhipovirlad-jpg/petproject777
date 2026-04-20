FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    pdo \
    pdo_sqlite \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
