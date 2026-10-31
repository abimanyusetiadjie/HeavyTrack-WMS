#!/usr/bin/env bash
# ==============================================================================
# HeavyTrack WMS - Script Optimasi Performa & RAM Ekstrem (VPS 1-2 GB RAM)
# ==============================================================================
set -e

echo "=============================================================================="
echo "  MEMULAI OPTIMASI KECEPATAN & RAM SERVER VPS HEAVYTRACK WMS"
echo "=============================================================================="

# 1. Buat Swap Memory 2GB jika belum ada (Mencegah Server Hang / Out-of-Memory)
echo "=== [1/5] Memeriksa & Mengaktifkan 2GB Swap Memory ==="
if [ $(swapon --show | wc -l) -eq 0 ]; then
    if [ ! -f /swapfile ]; then
        echo "Membuat Swapfile 2GB..."
        sudo fallocate -l 2G /swapfile || sudo dd if=/dev/zero of=/swapfile bs=1M count=2048
        sudo chmod 600 /swapfile
        sudo mkswap /swapfile
    fi
    sudo swapon /swapfile || true
    if ! grep -q "/swapfile" /etc/fstab; then
        echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
    fi
    sudo sysctl vm.swappiness=10 >/dev/null 2>&1 || true
    echo "-> Swap 2GB berhasil diaktifkan!"
else
    echo "-> Swap memory sudah aktif."
fi

# 2. Tuning PHP OPcache + JIT (Simpan Kode PHP di RAM, Hemat CPU 70%)
echo "=== [2/5] Mengoptimalkan PHP OPcache & JIT Compiler di RAM ==="
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
OPCACHE_INI="/etc/php/${PHP_VER}/fpm/conf.d/99-heavytrack-speed.ini"

if [ -d "/etc/php/${PHP_VER}/fpm/conf.d" ]; then
    sudo tee "$OPCACHE_INI" > /dev/null <<EOF
; HeavyTrack WMS High-Performance OPcache & JIT Configuration
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=60
opcache.save_comments=1
opcache.jit=1255
opcache.jit_buffer_size=64M
realpath_cache_size=4096K
realpath_cache_ttl=600
EOF
    echo "-> Konfigurasi OPcache & JIT diterapkan pada $OPCACHE_INI"
fi

# 3. Tuning PHP-FPM Worker Pool (Stabil untuk VPS 1-2 GB RAM)
echo "=== [3/5] Mengoptimalkan Worker PHP-FPM ==="
FPM_POOL="/etc/php/${PHP_VER}/fpm/pool.d/www.conf"
if [ -f "$FPM_POOL" ]; then
    sudo sed -i 's/^pm.max_children = .*/pm.max_children = 10/' "$FPM_POOL"
    sudo sed -i 's/^;pm.max_requests = .*/pm.max_requests = 500/' "$FPM_POOL"
    sudo sed -i 's/^pm.max_requests = .*/pm.max_requests = 500/' "$FPM_POOL"
    sudo systemctl restart "php${PHP_VER}-fpm"
    echo "-> PHP-FPM (${PHP_VER}) berhasil di-restart dengan konfigurasi cepat."
fi

# 4. Aktifkan Gzip Compression di Nginx
echo "=== [4/5] Memastikan Kompresi Gzip Aktif di Nginx ==="
NGINX_GZIP="/etc/nginx/conf.d/heavytrack-gzip.conf"
if [ -d "/etc/nginx/conf.d" ]; then
    sudo tee "$NGINX_GZIP" > /dev/null <<EOF
gzip_vary on;
gzip_proxied any;
gzip_comp_level 5;
gzip_min_length 256;
gzip_types
    application/atom+xml
    application/geo+json
    application/javascript
    application/x-javascript
    application/json
    application/ld+json
    application/manifest+json
    application/rdf+xml
    application/rss+xml
    application/xhtml+xml
    application/xml
    font/eot
    font/otf
    font/ttf
    image/svg+xml
    text/css
    text/javascript
    text/plain
    text/xml;
EOF
    sudo nginx -t && sudo systemctl reload nginx
    echo "-> Kompresi Gzip Nginx berhasil diaktifkan (ukuran transfer hemat 80%)."
fi

# 5. Bangun Seluruh Cache Produksi Laravel & Filament
echo "=== [5/5] Membangun Cache Komponen Filament, Ikon, View & Route ==="
cd /var/www/heavytrack-wms
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache database

echo ""
echo "=============================================================================="
echo "  [SELESAI] VPS Anda Kini Berjalan dalam Mode Kecepatan Maksimum!"
echo "=============================================================================="
