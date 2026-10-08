# WMS Deployment & Queue Guide

## 1. Initial Deployment (Deploy Awal)
Langkah-langkah berikut digunakan saat pertama kali men-deploy aplikasi ke server production atau staging:

```bash
# 1. Clone repository & masuk ke folder aplikasi
git clone <repo-url>
cd heavytrack-wms

# 2. Install dependency PHP (tanpa package dev)
composer install --optimize-autoloader --no-dev

# 3. Salin .env dan generate App Key
cp .env.example .env
php artisan key:generate

# 4. Sesuaikan kredensial Database di file .env (MySQL/MariaDB)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=heavytrack_wms
# DB_USERNAME=...
# DB_PASSWORD=...

# 5. Jalankan Migration beserta Seeder untuk Master Data & Admin
php artisan migrate:fresh --seed

# 6. Build frontend assets (jika ada file CSS/JS custom)
npm install
npm run build

# 7. Clear & Cache Konfigurasi untuk performa optimal
php artisan optimize
php artisan filament:optimize
php artisan view:cache
```

## 2. Menjalankan Worker Queue
Jika proses cetak PDF atau pengiriman email dialihkan ke *background job* (Queue) agar tidak membebani response HTTP, pastikan konfigurasi `.env` telah diubah:
```env
QUEUE_CONNECTION=database
# Atau gunakan 'redis' jika tersedia
```

### Command Menjalankan Worker
Jalankan perintah ini di background menggunakan supervisor (direkomendasikan) atau tmux:
```bash
php artisan queue:work --tries=3 --timeout=90
```

### Konfigurasi Supervisor (Ubuntu/Debian)
Agar worker tetap berjalan otomatis saat server direstart, buat file konfigurasi di `/etc/supervisor/conf.d/wms-worker.conf`:
```ini
[program:wms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/heavytrack-wms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/heavytrack-wms/storage/logs/worker.log
stopwaitsecs=3600
```
Lalu aktifkan dengan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wms-worker:*
```
