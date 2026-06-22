FROM php:8.4-fpm-alpine AS builder

RUN apk update && apk add --no-cache unzip

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --version=2.9.1 --quiet

WORKDIR /app

COPY ./composer.json ./

RUN composer install --no-dev --prefer-dist --no-progress --optimize-autoloader \
    && rm -rf /root/.composer/cache
##### FPM #####

FROM php:8.4-fpm-alpine

RUN apk add --no-cache unzip \
    && docker-php-ext-install opcache

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./docker/common/php/conf.d /usr/local/etc/php/conf.d

COPY ./docker/production/php-fpm/conf.d /usr/local/etc/php/conf.d

COPY ./docker/production/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /app

COPY --from=builder /app ./

COPY . /app

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var
