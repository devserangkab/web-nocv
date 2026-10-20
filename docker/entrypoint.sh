#!/usr/bin/env bash
#
# Entrypoint container aplikasi Web-NOCV (FrankenPHP + Laravel Octane).
# Urutan: siapkan folder -> tunggu database -> discover paket
#         -> storage link -> migrasi -> (opsional) cache -> jalankan Octane.
#
set -euo pipefail

APP_DIR="${APP_BASE_PATH:-/app}"
PUBLIC_DIR="${APP_PUBLIC_PATH:-${APP_DIR}/public}"
cd "$APP_DIR"

log() {
    printf '\033[1;34m[entrypoint]\033[0m %s\n' "$*"
}

# ---------------------------------------------------------------------------
# 1. Pastikan seluruh direktori writable tersedia
# ---------------------------------------------------------------------------
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/app/privates \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /data/caddy \
    /config/caddy

# ---------------------------------------------------------------------------
# 2. Bersihkan sisa state file Octane dari container sebelumnya
# ---------------------------------------------------------------------------
rm -f storage/logs/octane-server-state.json

# ---------------------------------------------------------------------------
# 3. Tunggu database siap (hanya untuk driver MySQL/MariaDB)
# ---------------------------------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" = "mysql" ] || [ "${DB_CONNECTION:-}" = "mariadb" ]; then
    log "Menunggu database ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."

    attempts=0
    max_attempts="${DB_WAIT_RETRIES:-60}"

    until php -r '
        try {
            new PDO(
                sprintf(
                    "mysql:host=%s;port=%s;dbname=%s",
                    getenv("DB_HOST") ?: "mysql",
                    getenv("DB_PORT") ?: "3306",
                    getenv("DB_DATABASE") ?: "laravel"
                ),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: ""
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        attempts=$((attempts + 1))

        if [ "$attempts" -ge "$max_attempts" ]; then
            log "Database belum siap setelah ${attempts} percobaan — lanjut tanpa migrasi."
            break
        fi

        sleep 2
    done

    log "Database siap."
fi

# ---------------------------------------------------------------------------
# 4. Manifest paket Laravel (dibuat sekali bila belum ada)
# ---------------------------------------------------------------------------
if [ ! -f bootstrap/cache/packages.php ]; then
    log "Menjalankan package:discover"
    php artisan package:discover --ansi || true
fi

# ---------------------------------------------------------------------------
# 5. Symlink storage public (dibutuhkan avatar & icon di views, dan nginx)
# ---------------------------------------------------------------------------
if [ "${RUN_STORAGE_LINK:-true}" = "true" ] && [ ! -e "${PUBLIC_DIR}/storage" ]; then
    log "Membuat symlink public/storage"
    php artisan storage:link || true
fi

# ---------------------------------------------------------------------------
# 6. Migrasi database
# ---------------------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Menjalankan migrasi database"
    php artisan migrate --force --no-interaction
fi

if [ "${RUN_SEEDER:-false}" = "true" ]; then
    log "Menjalankan seeder"
    php artisan db:seed --force --no-interaction
fi

# ---------------------------------------------------------------------------
# 7. Cache config/route (biarkan false untuk local development)
# ---------------------------------------------------------------------------
if [ "${APP_OPTIMIZE:-false}" = "true" ]; then
    log "Mengaktifkan cache Laravel"
    php artisan config:cache
    php artisan route:cache
fi

# ---------------------------------------------------------------------------
# 8. Jalankan Laravel Octane di atas FrankenPHP
# ---------------------------------------------------------------------------
OCTANE_ARGS="--host=${OCTANE_HOST:-0.0.0.0}"
OCTANE_ARGS="$OCTANE_ARGS --port=${OCTANE_PORT:-8000}"
OCTANE_ARGS="$OCTANE_ARGS --admin-host=${OCTANE_ADMIN_HOST:-127.0.0.1}"
OCTANE_ARGS="$OCTANE_ARGS --admin-port=${OCTANE_ADMIN_PORT:-2019}"
OCTANE_ARGS="$OCTANE_ARGS --workers=${OCTANE_WORKERS:-auto}"
OCTANE_ARGS="$OCTANE_ARGS --max-requests=${OCTANE_MAX_REQUESTS:-500}"

# Auto-reload worker saat file berubah (watcher native FrankenPHP — tidak butuh
# Node/chokidar seperti Octane pada server lain). Berguna bila source di-bind
# mount; kalau image dipakai apa adanya (tanpa bind mount) matikan saja.
if [ "${OCTANE_WATCH:-false}" = "true" ]; then
    OCTANE_ARGS="$OCTANE_ARGS --watch"
fi

if [ "${OCTANE_POLL:-false}" = "true" ]; then
    OCTANE_ARGS="$OCTANE_ARGS --poll"
fi

log "Menjalankan Octane (FrankenPHP) di port ${OCTANE_PORT:-8000} ..."
# shellcheck disable=SC2086
exec php artisan octane:frankenphp $OCTANE_ARGS