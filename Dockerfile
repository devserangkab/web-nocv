# syntax=docker/dockerfile:1

# =============================================================================
# Production image — Laravel 12 + Livewire pada FrankenPHP (Octane worker mode)
# Build:  docker compose up -d --build
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: build aset frontend (Vite + Tailwind)
# -----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: runtime FrankenPHP
# -----------------------------------------------------------------------------
# Tag 1.13.2 = FrankenPHP yang menyertakan PHP 8.3.36 (release 2026-10-21, satu
# batch dengan versi php8.4 yang dipakai bahagiafix di server). Jangan pakai tag
# mengambang — patch PHP bisa berubah antar build tanpa disadari.
FROM dunglas/frankenphp:1.13.2-php8.3-bookworm

ENV TZ=Asia/Jakarta

# Build-time guard: tolak build bila PHP runtime belum memuat fix php2shell.
RUN php -r 'exit(version_compare(PHP_VERSION, "8.3.36", ">=") ? 0 : 1);' \
    || (echo "ERROR: PHP < 8.3.36 — php2shell (CVE-2026-109618/109619) belum di-patch." && exit 1)

# Ekstensi PHP untuk production:
#   pdo_mysql      : koneksi MySQL
#   gd, exif       : dompdf (render gambar) + manipulasi file
#   intl           : Laravel (number/date formatting, spatie/laravel-permission)
#   zip            : arsip & dompdf
#   bcmath, pcntl  : perhitungan & signal handling Octane
#   opcache        : wajib untuk worker mode
RUN set -eux; \
    install-php-extensions pdo_mysql gd intl zip bcmath exif pcntl opcache; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl; \
    rm -rf /var/lib/apt/lists/*

# PHP ini production
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app-production.ini

# Composer untuk instalasi dependensi PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Layer dependensi (ter-cache selama composer.lock tidak berubah) ---------
# --no-scripts: post-autoload-dump (package:discover) TIDAK bisa jalan di layer
# ini karena source aplikasi belum di-copy; discovery dijalankan eksplisit
# setelah COPY (dengan pola yang terbukti di bahagiafix).
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --optimize-autoloader \
    && composer clear-cache

# Guard: laravel/octane wajib ter-install — worker mode & stub worker bergantung
# padanya. Pakai cek direktori vendor, bukan class_exists: pada `php -r` tanpa
# require autoload, class_exists SELALU false (tidak ada autoloader terdaftar).
RUN test -d vendor/laravel/octane \
    || (echo "ERROR: laravel/octane tidak ter-install — pastikan checkout server memuat composer.lock yang memuat laravel/octane." && exit 1)

# --- Source code -------------------------------------------------------------
COPY . .

# public/frankenphp-worker.php di-gitignore — pastikan selalu ada di image
# (fallback: stub dari package Octane), lalu rebuild autoload + hak akses.
RUN set -eux; \
    if [ ! -f public/frankenphp-worker.php ]; then \
        cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php; \
    fi; \
    composer dump-autoload --optimize --classmap-authoritative --no-interaction; \
    chmod 0755 docker/entrypoint.sh; \
    chown -R www-data:www-data storage bootstrap/cache

# --- Aset frontend hasil build Vite ------------------------------------------
COPY --from=assets /app/public/build public/build

# (Opsi A — pola terbukti bahagiafix) container berjalan sebagai root:
# binary FrankenPHP bawaan image memakai file-cap (setcap) yang pengaksesannya
# EPERM kalau dieksekusi proses non-root di bawah no-new-privileges/cap_drop ALL.
# Root juga memudahkan symlink public/storage dan chown storage.
EXPOSE 8080 8443

ENTRYPOINT ["/app/docker/entrypoint.sh"]
