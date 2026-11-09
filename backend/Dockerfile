# Backend (Laravel 12, PHP 8.2).
# En Railway: Root Directory = /backend. Variables: ver la seccion "Despliegue" del README.
FROM php:8.2-cli
RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev unzip \
 && docker-php-ext-install pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --no-progress
COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache

# Varios procesos para atender varias peticiones a la vez
ENV PHP_CLI_SERVER_WORKERS=4
CMD ["sh", "iniciar.sh"]
