# --- Etapa 1: compilación de assets (Vite + Tailwind) ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
RUN npm run build

# --- Etapa 2: dependencias PHP ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# --- Etapa 3: imagen final PHP 8.3 + Apache ---
FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip gd intl bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo 'ServerTokens Prod\nServerSignature Off' > /etc/apache2/conf-enabled/seguridad.conf \
    && echo 'expose_php = Off' > /usr/local/etc/php/conf.d/seguridad.ini \
    && echo 'upload_max_filesize = 6M\npost_max_size = 8M' > /usr/local/etc/php/conf.d/subidas.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN rm -f .env && chown -R www-data:www-data storage bootstrap/cache && chmod +x docker/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
