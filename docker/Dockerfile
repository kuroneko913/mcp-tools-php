FROM php:8.2-cli

WORKDIR /app

# 必要なパッケージとzip拡張
RUN apt-get update \
    && apt-get install -y unzip git libzip-dev \
    && docker-php-ext-install zip

COPY composer.json .
COPY composer.lock .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY src/ ./src/
COPY tools.json ./tools.json
COPY vendor/ ./vendor/

RUN composer install

CMD ["php", "src/server.php"] 
