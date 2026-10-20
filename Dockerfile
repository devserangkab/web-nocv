# syntax=docker/dockerfile:1

# =============================================================================
# Web-NOCV — Dockerfile
# Runtime  : FrankenPHP (Caddy + PHP 8.2) + Laravel Octane (Laravel 12)
# Multi-stage:
#   1. assets  -> build bundle front-end (Vite + Tailwind 4)
#   2. vendor  -> composer install tanpa dependency dev
#   3. app     -> image runtime FrankenPHP, Octane listen di port 8000
#
# Port container = 8000 (unprivileged), jadi tidak butuh CAP_NET_BIND_SERVICE.
# Yang menghadap host di port 8080 adalah container nginx (reverse proxy)
# yang dijalankan lewat docker-compose.yml.
# =============================================================================

ARG NODE_VERSION=22
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1-php8.2-bookworm

# -----------------------------------------------------------------------------
# Stage 1 — Front-end assets (Vite)
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# public/build dikecualikan .dockerignore; pastikan bersih lalu build ulang.
RUN rm -rf public/build && npm run build


# -----------------------------------------------------------------------------
# Stage 2 — Dependency Composer
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# --ignore-platform-reqs aman di sini: image runtime (stage 3) memasang sendiri
# seluruh ekstensi yang dibutuhkan aplikasi.
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
        --ignore-platform-reqs


# -----------------------------------------------------------------------------
# Stage 3 — Runtime (FrankenPHP + Octane)
# -----------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS app

# APP_BASE_PATH / APP_PUBLIC_PATH dibaca oleh template Caddyfile Octane
# (worker file = {$APP_PUBLIC_PATH}/frankenphp-worker.php).
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_BASE_PATH=/app \
    APP_PUBLIC_PATH=/app/public \
    OCTANE_SERVER=frankenphp

# Ekstensi PHP yang dipakai aplikasi: pdo_mysql (database), gd (thumbnail media
# di app/Actions/Media/StoreMedia.php), zip (Composer/dompdf), exif, bcmath,
# pcntl (signal handling worker Octane), dan opcache.
RUN install-php-extensions \
        pdo_mysql \
        gd \
        zip \
        exif \
        bcmath \
        pcntl \
        opcache

WORKDIR /app

# php.ini kustom (variables_order=EGPCS wajib agar env container terbaca).
COPY docker/php/php.ini ${PHP_INI_DIR}/conf.d/zz-web-nocv.ini

# Dependency Composer hasil stage 2.
COPY --from=vendor /app/vendor ./vendor

# Source aplikasi.
COPY . .

# Asset hasil build Vite.
COPY --from=assets /build/public/build ./public/build

# Autoloader optimal untuk kode aplikasi (app/, database/).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts

# Direktori writable: storage Laravel, bootstrap/cache, dan direktori Caddy.
RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/app/privates \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /config/caddy \
        /data/caddy \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:".(getenv("OCTANE_PORT") ?: "8000")."/up") === false ? 1 : 0);'

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]