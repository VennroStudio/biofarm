FROM php:8.2-fpm-alpine

RUN apk update  \
    && apk add --no-cache linux-headers unzip git zip libzip-dev ffmpeg \
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

RUN apk update && apk add --no-cache python3 py3-pip
RUN pip3 install tidal-dl-ng --upgrade --break-system-packages
COPY ./common/tidal_dl_ng/settings.json /root/.config/tidal_dl_ng/settings.json
# COPY ./common/tidal_dl_ng/token.json /root/.config/tidal_dl_ng/token.json

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY ./common/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /app

COPY ./development/php-fpm/entrypoint.sh /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint
