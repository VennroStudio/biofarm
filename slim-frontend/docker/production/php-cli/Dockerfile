FROM php:8.4-cli-alpine AS builder

RUN apk update && apk add --no-cache unzip

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --version=2.9.1 --quiet

WORKDIR /app

COPY ./composer.json ./

RUN composer install --no-dev --prefer-dist --no-progress --optimize-autoloader \
    && rm -rf /root/.composer/cache
##### CLI #####

FROM php:8.4-cli-alpine

RUN apk add --no-cache unzip

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./docker/common/php/conf.d /usr/local/etc/php/conf.d

COPY ./docker/production/php-cli/conf.d /usr/local/etc/php/conf.d

WORKDIR /app

COPY --from=builder /app ./

COPY . /app

RUN mkdir -p var/cache var/log
