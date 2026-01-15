FROM php:8.2-cli-alpine AS builder

RUN apk update  \
    && apk add --no-cache linux-headers ffmpeg \
    && docker-php-ext-install pdo pdo_mysql sockets opcache

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl update-channels \
    && pecl install redis \
    && docker-php-ext-enable redis.so

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions http

RUN apk add --no-cache unzip

RUN apk add --update --no-cache freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    NPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) \
    && docker-php-ext-install -j$(nproc) gd \
    && apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev \
    && mkdir /var/lib/php  \
    && chown www-data:www-data /var/lib/php -R

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

RUN apk add --no-cache libpng libpng-dev && docker-php-ext-install gd && apk del libpng-dev

RUN apk add --no-cache zip libzip-dev && docker-php-ext-configure zip && docker-php-ext-install zip

WORKDIR /app

COPY ./composer.json ./composer.lock ./

RUN composer install --no-dev --prefer-dist --no-progress --no-suggest --optimize-autoloader \
    && rm -rf /root/.composer/cache

### CLI ###

FROM php:8.2-cli-alpine

RUN apk update  \
    && apk add --no-cache linux-headers unzip bash coreutils git zip libzip-dev ffmpeg \
    && docker-php-ext-install pdo pdo_mysql sockets bcmath zip \
    && apk del git

RUN apk add --no-cache bash curl chromium nss freetype harfbuzz ttf-freefont
RUN ln -s /usr/bin/chromium-browser /usr/bin/google-chrome

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis.so

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions http

RUN apk add --no-cache unzip

RUN apk add --update --no-cache freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    NPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) \
    && docker-php-ext-install -j$(nproc) gd \
    && apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev \
    && mkdir /var/lib/php  \
    && chown www-data:www-data /var/lib/php -R

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./docker/common/php/conf.d /usr/local/etc/php/conf.d
COPY ./docker/production/php/conf.d /usr/local/etc/php/conf.d

RUN apk update && apk add --no-cache python3 py3-pip
RUN pip3 install tidal-dl-ng --upgrade --break-system-packages
COPY ./docker/common/tidal_dl_ng/settings.json /root/.config/tidal_dl_ng/settings.json
RUN mkdir -p /home/app/.config/tidal_dl_ng
COPY --chown=1000:1000 ./docker/common/tidal_dl_ng/settings.json /home/app/.config/tidal_dl_ng/settings.json

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app
ENV HOME=/home/app \
    XDG_CONFIG_HOME=/home/app/.config
RUN chown -R app:app /home/app/.config && \
    chmod -R u+rwX /home/app/.config/tidal_dl_ng

WORKDIR /app

COPY --from=builder /app ./
COPY ./ ./

USER app
