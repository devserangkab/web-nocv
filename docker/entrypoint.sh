#!/bin/sh
set -e

cd /app

# =============================================================================
# 1. APP_KEY — generate otomatis jika belum disediakan via environment
# =============================================================================
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY
    echo "[entrypoint] APP_KEY baru dibuat untuk run ini."
    echo "[entrypoint] TIP: set APP_KEY di .env / compose agar tetap stabil antar restart."
fi

# =============================================================================
# 2. Pastikan struktur storage ada (aman untuk volume kosong / image fresh)
# =============================================================================
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# =============================================================================
# 3. Tunggu MySQL siap
# =============================================================================
php docker/wait-db.php

# =============================================================================
# 4. Migrasi + optimize (config/route/view/event cache)
# =============================================================================
echo "[entrypoint] Menjalankan migrasi database..."
php artisan migrate --force

echo "[entrypoint] Membangun cache konfigurasi..."
php artisan config:cache
php artisan view:cache
php artisan event:cache || echo "[entrypoint] event:cache dilewati."
php artisan route:cache || echo "[entrypoint] route:cache dilewati (ada closure di routes)."
php artisan storage:link || echo "[entrypoint] storage:link dilewati."

# =============================================================================
# 5. Start server
#    - Octane terpasang  -> FrankenPHP WORKER MODE (HTTPS self-signed, 8443)
#    - Octane tidak ada  -> fallback PHP biasa (HTTP, 8080)
# =============================================================================
if php -r "require 'vendor/autoload.php'; exit(class_exists('Laravel\\Octane\\Octane') ? 0 : 1);"; then
    echo "[entrypoint] Octane terdeteksi -> FrankenPHP WORKER MODE (https://{$APP_DOMAIN:-} di port 8443)."

    if [ ! -f "${TLS_CERT:-/certs/cert.pem}" ] || [ ! -f "${TLS_KEY:-/certs/key.pem}" ]; then
        echo "[entrypoint] ERROR: sertifikat TLS tidak ditemukan."
        echo "[entrypoint] Sediakan file berikut di server (di-mount read-only ke /certs):"
        echo "[entrypoint]   docker/certs/cert.pem  <- sertifikat + intermediate chain Sectigo"
        echo "[entrypoint]   docker/certs/key.pem   <- private key"
        echo "[entrypoint]   (chmod agar bisa dibaca UID 33/www-data)"
        exit 1
    fi

    # Rakit argumen sebagai satu string agar tidak ada masalah quoting POSIX sh.
    # Tulisan lama yang bermasalah: ${OCTANE_WORKERS:+--workers="${OCTANE_WORKERS}"}
    OCTANE_ARGS="--host=0.0.0.0 --port=8443 --caddyfile=/app/docker/Caddyfile"
    OCTANE_ARGS="$OCTANE_ARGS --max-requests=${OCTANE_MAX_REQUESTS:-500} --log-level=${OCTANE_LOG_LEVEL:-WARN}"
    if [ -n "${OCTANE_WORKERS:-}" ]; then
        OCTANE_ARGS="$OCTANE_ARGS --workers=${OCTANE_WORKERS}"
    fi

    exec php artisan octane:frankenphp ${OCTANE_ARGS}
else
    echo "[entrypoint] Octane TIDAK terdeteksi -> fallback mode HTTP biasa (port 8080)."
    echo "[entrypoint] Jalankan 'composer require laravel/octane' untuk mengaktifkan worker mode."

    exec frankenphp run --config /app/docker/Caddyfile.basic
fi
