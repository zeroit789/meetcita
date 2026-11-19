#!/bin/sh
# ==============================================================================
# ES: Arranque del contenedor: crea la BD SQLite si hace falta, migra, cachea
#     la config, las rutas y las vistas y lanza el comando (web o scheduler).
# EN: Container start-up: creates the SQLite DB if needed, migrates, caches
#     the config, routes and views and runs the command (web or scheduler).
# ==============================================================================
set -e
cd /app

# ES: Con SQLite, el fichero vive en el volumen montado en /data.
# EN: With SQLite, the file lives on the volume mounted at /data.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  DB_FILE="${DB_DATABASE:-/data/database.sqlite}"
  [ -f "$DB_FILE" ] || touch "$DB_FILE"
fi

# ES: Solo el servicio web migra (MEETCITA_MIGRATE=false en el scheduler).
# EN: Only the web service migrates (MEETCITA_MIGRATE=false on the scheduler).
if [ "${MEETCITA_MIGRATE:-true}" = "true" ]; then
  php artisan migrate --force
  # ES: Las vistas compiladas (volumen compartido) las prepara solo la web.
  # EN: Compiled views (shared volume) are prepared by the web only.
  php artisan view:cache
fi
php artisan config:cache
php artisan route:cache

exec "$@"
