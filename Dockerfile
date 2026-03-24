FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# SQLite DB を作成
RUN mkdir -p database && touch database/database.sqlite

# 権限設定
RUN chmod -R 777 storage bootstrap/cache database

RUN composer install --no-dev --optimize-autoloader

# ★ ここを追加（重要）
RUN php artisan migrate --force

CMD php artisan serve --host=0.0.0.0 --port=8080