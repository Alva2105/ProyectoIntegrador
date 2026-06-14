FROM php:8.4-cli

RUN apt-get update --fix-missing && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev libsodium-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml opcache sodium

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=$PORT