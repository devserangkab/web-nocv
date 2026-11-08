#!/bin/sh
set -e

cd /app

# 1. APP_KEY — generate jika belum disediakan via environment
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY
    echo "[entrypoint] APP_KEY baru dibuat untuk run ini — set di .env agar stabil antar restart."
fi

# 2. Pastikan struktur storage ada (volume kosong / image fresh)
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# 3. Tunggu MySQL siap
php docker/wait-db.php

# 4. Migrasi + cache
echo "[entrypoint] Menjalankan migrasi database..."
php artisan migrate --force

echo "[entrypoint] Membangun cache konfigurasi..."
php artisan config:cache
php artisan view:cache
php artisan event:cache || echo "[entrypoint] event:cache dilewati."
php artisan route:cache || echo "[entrypoint] route:cache dilewati (ada closure di routes)."
php artisan storage:link || echo "[entrypoint] storage:link dilewati."

# 5. Start: Octane -> FrankenPHP worker mode (HTTP, APP_PORT); tanpa Octane -> HTTP 8080
if php -r "require 'vendor/autoload.php'; exit(class_exists('Laravel\\Octane\\Octane') ? 0 : 1);"; then
    echo "[entrypoint] Octane terdeteksi -> FrankenPHP WORKER MODE (http://0.0.0.0:${APP_PORT:-80})."

    # Satu string agar quoting aman di POSIX sh
    OCTANE_ARGS="--host=0.0.0.0 --port=${APP_PORT:-80} --caddyfile=/app/docker/Caddyfile"
    OCTANE_ARGS="$OCTANE_ARGS --max-requests=${OCTANE_MAX_REQUESTS:-500} --log-level=${OCTANE_LOG_LEVEL:-WARN}"
    if [ -n "${OCTANE_WORKERS:-}" ]; then
        OCTANE_ARGS="$OCTANE_ARGS --workers=${OCTANE_WORKERS}"
    fi

    exec php artisan octane:frankenphp ${OCTANE_ARGS}
else
    echo "[entrypoint] Octane TIDAK terdeteksi -> fallback HTTP (port 8080)."
    exec frankenphp run --config /app/docker/Caddyfile.basic
fi
