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

RUN apk update && apk add --no-cache python3 py3-pip
RUN pip3 install tidal-dl-ng --upgrade --break-system-packages
COPY ./common/tidal_dl_ng/settings.json /root/.config/tidal_dl_ng/settings.json

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY ./common/php/conf.d /usr/local/etc/php/conf.d

COPY ./common/wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 555 /usr/local/bin/wait-for-it

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /app

USER app
