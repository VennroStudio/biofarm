FROM php:8.4-cli-alpine AS vendor

RUN apk update && apk add --no-cache unzip

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --version=2.9.1 --quiet

WORKDIR /app

COPY ./composer.json ./composer.lock ./

RUN composer install --no-dev --prefer-dist --no-progress --optimize-autoloader \
    && rm -rf /root/.composer/cache

FROM node:20-alpine AS assets

WORKDIR /app

COPY ./package.json ./package-lock.json ./

RUN npm ci

COPY ./assets ./assets
COPY ./tsconfig.json ./vite.config.ts ./

RUN npm run build
##### CLI #####

FROM php:8.4-cli-alpine

RUN apk add --no-cache unzip

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./docker/common/php/conf.d /usr/local/etc/php/conf.d

COPY ./docker/production/php-cli/conf.d /usr/local/etc/php/conf.d

WORKDIR /app

COPY --from=vendor /app/composer.json /app/composer.lock ./
COPY --from=vendor /app/vendor ./vendor

COPY ./config ./config
COPY ./public ./public
COPY --from=assets /app/public/build ./public/build
COPY ./src ./src
COPY ./templates ./templates

RUN mkdir -p var/cache var/log
