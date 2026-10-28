#!/usr/bin/env bash
# ==============================================================================
# Script Otomatisasi Deploy HeavyTrack WMS (Filament v5 / Laravel 12) di VPS Ubuntu/Debian
# ==============================================================================
set -e

echo "=== [1/6] Memeriksa Direktori & Environment ==="
cd /var/www/heavytrack-wms || exit 1

if [ ! -f .env ]; then
    echo "Membuat file .env dari template..."
    cp .env.example .env
fi

echo "=== [2/6] Memperbaiki Permission Direktori Storage & Cache ==="
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

echo "=== [3/6] Menginstal Dependensi PHP (Composer) ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [4/6] Menyiapkan Application Key & Database ==="
php artisan key:generate --force

# Pastikan file sqlite ada jika menggunakan sqlite
if grep -q "DB_CONNECTION=sqlite" .env; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
    chmod 664 database/database.sqlite
fi

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

echo "=== [6/6] Menghubungkan Storage Symlink ==="
php artisan storage:link || true

echo ""
echo "=============================================================================="
echo "  [SUKSES] HeavyTrack WMS Berhasil Dikonfigurasi di VPS!"
echo "  Akun Default:"
echo "  - Owner : username 'owner' | password '12345678'"
echo "  - Admin : username 'admin' | password '12345678'"
echo "=============================================================================="
