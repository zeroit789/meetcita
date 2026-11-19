# ==============================================================================
# Meetcita — imagen de producción / production image
# ==============================================================================
# ES: Tres etapas: (1) compila el CSS/JS con pnpm, (2) instala las dependencias
#     de PHP sin las de desarrollo, (3) imagen final con FrankenPHP (servidor
#     web + PHP en un solo binario). La imagen base va fijada por digest.
# EN: Three stages: (1) builds the CSS/JS with pnpm, (2) installs the PHP
#     dependencies without the dev ones, (3) final image with FrankenPHP (web
#     server + PHP in one binary). The base image is pinned by digest.
# ==============================================================================

# ── 1. Front (CSS/JS) ─────────────────────────────────────────────────────────
FROM node:22-alpine AS front
WORKDIR /app
RUN corepack enable
COPY package.json pnpm-lock.yaml .npmrc ./
RUN pnpm install --frozen-lockfile
COPY vite.config.js ./
COPY resources ./resources
RUN pnpm run build

# ── 2. Dependencias PHP / PHP dependencies ───────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ── 3. Imagen final / Final image ────────────────────────────────────────────
FROM dunglas/frankenphp:1.13.4-php8.5@sha256:c8abcc7925f16b1431f139d0410abe8f48db3fd862bacb646b05a4413337106a
# ES: Extensiones que usa la app (SQLite, MySQL o PostgreSQL; intl para fechas).
# EN: Extensions the app uses (SQLite, MySQL or PostgreSQL; intl for dates).
RUN install-php-extensions pdo_sqlite pdo_mysql pdo_pgsql intl zip opcache pcntl \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/zz-meetcita.ini"
WORKDIR /app
COPY --from=vendor /app /app
COPY --from=front /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/meetcita-entrypoint
# ES: Carpetas escribibles por el usuario del servidor web.
# EN: Folders writable by the web server user.
RUN chmod +x /usr/local/bin/meetcita-entrypoint \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs /data \
 && chown -R www-data:www-data storage bootstrap/cache /data /config/caddy /data/caddy
# ES: Sin root: el servidor y el scheduler corren como www-data y escuchan en
#     el 8080 (un puerto alto no necesita privilegios). La BD SQLite va en
#     /data (volumen), fuera del código, para no tapar las migraciones nuevas.
# EN: No root: the server and the scheduler run as www-data and listen on
#     8080 (a high port needs no privileges). The SQLite DB lives in /data
#     (volume), outside the code, so new migrations are not hidden.
USER www-data
ENV SERVER_NAME=":8080" APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr DB_DATABASE=/data/database.sqlite
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s CMD curl -fsS http://localhost:8080/up || exit 1
ENTRYPOINT ["meetcita-entrypoint"]
CMD ["frankenphp", "php-server", "--root", "public/", "--listen", ":8080"]
