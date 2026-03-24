FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# ★ SQLite DB を作成（重要）
RUN mkdir -p database && touch database/database.sqlite

# ★ 権限設定（重要）
RUN chmod -R 777 storage bootstrap/cache database

RUN composer install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=8080