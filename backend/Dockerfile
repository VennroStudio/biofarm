FROM php:8.2-cli-alpine

# Обновляем пакеты и устанавливаем необходимые расширения PHP и зависимости
RUN apk update && apk upgrade && apk add --no-cache \
    git \
    curl \
    wget \
    zip \
    unzip \
    ca-certificates \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    openssl-dev \
    && update-ca-certificates \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    opcache \
    && rm -rf /var/cache/apk/*

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настраиваем переменные окружения для работы с SSL
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

# Настраиваем Composer для работы с проблемными SSL соединениями
RUN composer global config --no-plugins allow-plugins.symfony/flex true && \
    composer global config repos.packagist composer https://mirror.packagist.com || true && \
    composer global config github-protocols https ssh || true

WORKDIR /app

# Зависимости должны быть установлены локально в проекте
# Этот Dockerfile только запускает PHP сервер
# Установите зависимости командой: docker-compose run --rm backend composer install

# Создаем директорию для логов
RUN mkdir -p var/log && chmod -R 777 var

# Открываем порт
EXPOSE 8000

# Запускаем PHP встроенный сервер
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
