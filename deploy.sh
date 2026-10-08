#!/bin/bash
set -e

echo "🚀 Memulai proses deployment HeavyTrack WMS ke Production..."

# 1. Pindah ke direktori proyek (sesuaikan path)
# cd /var/www/heavytrack-wms

# 2. Aktifkan Maintenance Mode
echo "🔧 Mengaktifkan Maintenance Mode..."
php artisan down --render="errors::503" || true

# 3. Pull kode terbaru (Jika menggunakan Git)
# git pull origin main

# 4. Install Dependencies tanpa package dev
echo "📦 Menginstal dependensi Composer..."
composer install --no-dev --optimize-autoloader --quiet

# 5. Konfigurasi Hak Akses Direktori (Security Hardening)
echo "🔒 Menyesuaikan permission untuk storage dan bootstrap/cache..."
# chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Note: /storage/app/prints tetap privat karena storage:link hanya mengekspos /storage/app/public
echo "🔗 Membuat symbolic link storage (jika belum ada)..."
php artisan storage:link || true

# 6. Jalankan Migrasi Database
echo "🗄️ Menjalankan database migration..."
php artisan migrate --force

# 7. Production Caching
echo "⚡ Membuat Cache Configuration, Routes, Views, dan Filament..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan event:cache

# 8. Restart Queue Worker (Agar mengambil perubahan kode baru)
echo "🔄 Merestart Queue Worker..."
php artisan queue:restart

# 9. Nonaktifkan Maintenance Mode
echo "✅ Menonaktifkan Maintenance Mode..."
php artisan up

echo "🎉 Deployment selesai 100%!"
