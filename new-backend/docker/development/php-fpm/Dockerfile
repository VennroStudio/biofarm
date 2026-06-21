FROM php:8.4-fpm-alpine

RUN apk update && apk add --no-cache unzip mysql-dev linux-headers \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev libavif-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install pdo_mysql gd exif \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Копируем php.ini для development
RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

# Копируем кастомные конфигурации PHP (limit.ini, security.ini, opcache.ini)
COPY ./common/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /app