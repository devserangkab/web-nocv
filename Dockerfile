# syntax=docker/dockerfile:1

# Production image — Laravel 12 + Livewire pada FrankenPHP (Octane worker mode)
# Build: docker compose up -d --build

# --- Stage 1: build aset frontend (Vite + Tailwind) ---------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# --- Stage 2: runtime FrankenPHP ----------------------------------------------
# Tag dipin: 1.13.2 = PHP 8.3.36 (patch php2shell CVE-2026-109618/109619).
FROM dunglas/frankenphp:1.13.2-php8.3-bookworm

ENV TZ=Asia/Jakarta

# Guard: tolak build bila PHP runtime belum memuat fix php2shell
RUN php -r 'exit(version_compare(PHP_VERSION, "8.3.36", ">=") ? 0 : 1);' \
    || (echo "ERROR: PHP < 8.3.36 — php2shell belum di-patch." && exit 1)

# pdo_mysql: DB | gd+exif: dompdf/gambar | intl: Laravel | zip: arsip/dompdf
# bcmath+pcntl: Octane | opcache: wajib untuk worker mode
RUN set -eux; \
    install-php-extensions pdo_mysql gd intl zip bcmath exif pcntl opcache; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl; \
    rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app-production.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Layer dependensi (ter-cache selama composer.lock tidak berubah).
# --no-scripts: package:discover dijalankan eksplisit setelah COPY source.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --optimize-autoloader \
    && composer clear-cache

# Guard: laravel/octane wajib ada (worker mode bergantung padanya)
RUN test -d vendor/laravel/octane \
    || (echo "ERROR: laravel/octane tidak ter-install." && exit 1)

COPY . .

# public/frankenphp-worker.php di-gitignore — fallback ke stub Octane,
# lalu rebuild autoload + hak akses.
RUN set -eux; \
    if [ ! -f public/frankenphp-worker.php ]; then \
        cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php; \
    fi; \
    composer dump-autoload --optimize --classmap-authoritative --no-interaction; \
    chmod 0755 docker/entrypoint.sh; \
    chown -R www-data:www-data storage bootstrap/cache

COPY --from=assets /app/public/build public/build

# Container berjalan sebagai root: binary FrankenPHP memakai file-cap (setcap)
# yang EPERM di bawah no-new-privileges/cap_drop ALL jika non-root.
# Port HTTPS = APP_PORT (default 9446); 8080 = fallback HTTP tanpa Octane.
EXPOSE 8080 9446

ENTRYPOINT ["/app/docker/entrypoint.sh"]
