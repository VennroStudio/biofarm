FROM php:8.4-cli-alpine AS builder

RUN apk update && apk add --no-cache unzip mysql-dev linux-headers \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev libavif-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install pdo_mysql gd exif \
    && docker-php-ext-enable redis \
    && apk del .build-deps

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
COPY ./templates ./templates
COPY ./tsconfig.json ./vite.config.ts ./tailwind.config.ts ./postcss.config.js ./

RUN npm run build
##### CLI #####

FROM php:8.4-cli-alpine

RUN apk add --no-cache mysql-dev linux-headers \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev libavif-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install pdo_mysql gd exif \
    && docker-php-ext-enable redis \
    && apk del .build-deps

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./docker/common/php/conf.d /usr/local/etc/php/conf.d

COPY ./docker/production/php-cli/conf.d /usr/local/etc/php/conf.d

WORKDIR /app

COPY --from=builder /app/composer.json /app/composer.lock ./
COPY --from=builder /app/vendor ./vendor

COPY ./bin ./bin
COPY ./config ./config
COPY ./public ./public
COPY ./src ./src
COPY ./templates ./templates
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p var/cache var/log
