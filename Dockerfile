FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev libsodium-dev libgd-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml opcache gd \
    && docker-php-ext-enable sodium

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs
RUN npm install && npm run build

EXPOSE 8000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT