#!/bin/sh
# Arranque del backend en la nube (Railway): prepara la base y levanta el servidor.
set -e

php artisan config:cache
php artisan migrate --force
# Crea el primer administrador si ADMIN_EMAIL y ADMIN_PASSWORD estan definidos
php artisan db:seed --force
# Las fotos subidas viven en storage/app/public (en Railway, un Volume montado ahi)
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan l5-swagger:generate >/dev/null 2>&1 || echo "AVISO: no se genero la documentacion Swagger"

# El servidor debe arrancar DENTRO de public/ (server.php usa la carpeta actual).
# Los errores de PHP van al registro (logs), nunca a la pantalla del visitante.
cd public
exec php -d display_errors=stderr -S "0.0.0.0:${PORT:-8000}" \
  ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
