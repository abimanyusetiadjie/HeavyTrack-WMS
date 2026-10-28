#!/usr/bin/env bash
# ==============================================================================
# Script Otomatisasi Deploy HeavyTrack WMS (Filament v5 / Laravel 12) di VPS Ubuntu/Debian
# ==============================================================================
set -e
export COMPOSER_ALLOW_SUPERUSER=1

echo "=== [1/6] Memeriksa Direktori & Environment ==="
cd /var/www/heavytrack-wms || exit 1

if [ ! -f .env ]; then
    echo "Membuat file .env dari template..."
    cp .env.example .env
    # Set absolute path database SQLite agar 100% aman
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=/var/www/heavytrack-wms/database/database.sqlite|g" .env
    sed -i "s|APP_ENV=.*|APP_ENV=production|g" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|g" .env
fi

echo "=== [2/6] Menyiapkan File Database & Permission ==="
mkdir -p database storage bootstrap/cache
touch database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database
chmod 664 database/database.sqlite

echo "=== [3/6] Menginstal Dependensi PHP (Composer) ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [4/6] Menyiapkan Application Key & Database ==="
php artisan key:generate --force

echo "Menjalankan migrasi database..."
php artisan migrate --force

echo "Memastikan akun admin dan katalog data terisi..."
php artisan db:seed --force

echo "=== [5/6] Mengaktifkan Optimasi Produksi Filament v5 ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

echo "=== [6/6] Menghubungkan Storage Symlink & Final Permission ==="
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

echo ""
echo "=============================================================================="
echo "  [SUKSES] HeavyTrack WMS Berhasil Dikonfigurasi di VPS!"
echo "  Akun Default:"
echo "  - Owner : username 'owner' | password '12345678'"
echo "  - Admin : username 'admin' | password '12345678'"
echo "=============================================================================="

