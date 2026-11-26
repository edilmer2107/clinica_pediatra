#!/usr/bin/env bash
set -e
cd /var/www/html

# Puerto asignado por el hosting (Render usa la variable PORT)
PORT="${PORT:-10000}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Laravel exige APP_KEY con prefijo base64:
if [ -n "$APP_KEY" ] && [[ "$APP_KEY" != base64:* ]]; then
  export APP_KEY="base64:${APP_KEY}"
fi

php artisan package:discover --ansi
php artisan migrate --force

# Datos de prueba ficticios solo si la base está vacía y se solicitó
if [ "${SEED_DEMO:-false}" = "true" ]; then
  if [ "$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n1)" = "0" ]; then
    php artisan db:seed --force
  fi
fi

php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# Planificador (recordatorios automáticos) en segundo plano
su -s /bin/bash www-data -c "php artisan schedule:work" > /dev/null 2>&1 &

exec apache2-foreground
