@echo off
setlocal EnableDelayedExpansion
title HeavyTrack WMS - Setup & Validasi Offline (Laragon Windows)
color 0A

:: Pastikan berjalan di direktori tempat file .bat ini berada
cd /d "%~dp0"

:: ----------------------------------------------------------------------------
:: 0. CEK APAKAH SUDAH PERNAH DI-SETUP SEBELUMNYA
:: ----------------------------------------------------------------------------
if exist ".setup_done" if not "%1"=="force" if not "%1"=="auto" (
    echo ============================================================================
    echo   HEAVYTRACK WMS - MITRA ABADI NUSANTARA
    echo ============================================================================
    echo.
    echo   [INFO] Sistem ini SUDAH PERNAH DI-SETUP sebelumnya di komputer ini!
    echo   Anda TIDAK PERLU menjalankan file 'setup-offline.bat' ini lagi.
    echo.
    echo   Untuk operasional sehari-hari, cukup klik ganda file:
    echo   --^> JALANKAN_SISTEM.bat
    echo.
    echo ============================================================================
    echo.
    set /p FORCE_AGAIN="Apakah Anda sengaja ingin melakukan SETUP ULANG dari nol? (Y/N): "
    if /i not "!FORCE_AGAIN!"=="Y" (
        echo [INFO] Membatalkan proses. Silakan buka 'JALANKAN_SISTEM.bat'.
        timeout /t 3 >nul
        exit /b 0
    )
    cls
)

:: ----------------------------------------------------------------------------
:: 1. DETEKSI OTOMATIS PATH PHP & MYSQL LARAGON (Jika diklik 2x dari Explorer)
:: ----------------------------------------------------------------------------
set "LARAGON_ROOT=C:\laragon"
if exist "D:\laragon" set "LARAGON_ROOT=D:\laragon"

where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "%LARAGON_ROOT%\bin\php" (
        for /d %%D in ("%LARAGON_ROOT%\bin\php\php-8*") do set "PHP_DIR=%%D"
        if defined PHP_DIR (
            echo [INFO] Menambahkan PHP Laragon ke PATH: !PHP_DIR!
            set "PATH=!PHP_DIR!;%LARAGON_ROOT%\bin\composer;%PATH%"
        )
    )
)

where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "%LARAGON_ROOT%\bin\mysql" (
        for /d %%M in ("%LARAGON_ROOT%\bin\mysql\mysql-*") do set "MYSQL_DIR=%%M\bin"
        if defined MYSQL_DIR (
            echo [INFO] Menambahkan MySQL Laragon ke PATH: !MYSQL_DIR!
            set "PATH=!MYSQL_DIR!;%PATH%"
        )
    )
)

:: Cek ketersediaan PHP
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    color 0C
    echo [ERROR] PHP tidak ditemukan!
    echo Pastikan Laragon sudah terinstall di C:\laragon atau jalankan file ini dari Terminal Laragon.
    pause
    exit /b 1
)

for /f "tokens=*" %%v in ('php -r "echo PHP_VERSION;"') do set PHP_VER=%%v
echo [OK] PHP Terdeteksi: Versi %PHP_VER%

:: ----------------------------------------------------------------------------
:: 2. CEK EKSTENSI PHP WAJIB (intl, zip, gd, pdo_mysql)
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 1/7] Memeriksa Ekstensi PHP Wajib (intl, zip, gd, pdo_mysql)...
php -r "$reqs=['intl','zip','gd','pdo_mysql','mbstring','fileinfo','openssl']; $missing=[]; foreach($reqs as $r){ if(!extension_loaded($r)) $missing[]=$r; } if($missing){ echo '[ERROR] Ekstensi PHP belum aktif: '.implode(', ',$missing).PHP_EOL; exit(1); } echo '[OK] Semua ekstensi PHP wajib sudah aktif!'.PHP_EOL;"
if %ERRORLEVEL% NEQ 0 (
    color 0E
    echo.
    echo [PERINGATAN] Ada ekstensi PHP yang belum aktif di Laragon!
    echo Mencoba mengaktifkan otomatis di php.ini...
    for /f "tokens=*" %%i in ('php -r "echo php_ini_loaded_file();"') do set PHP_INI=%%i
    if exist "!PHP_INI!" (
        powershell -NoProfile -Command "(Get-Content '!PHP_INI!') -replace ';extension=intl','extension=intl' -replace ';extension=zip','extension=zip' -replace ';extension=gd','extension=gd' -replace ';extension=pdo_mysql','extension=pdo_mysql' -replace 'memory_limit = 128M','memory_limit = 512M' | Set-Content '!PHP_INI!'"
        echo [OK] Berhasil mengaktifkan ekstensi di !PHP_INI!
        echo [PENTING] Jangan lupa klik Reload/Restart Apache di Laragon setelah proses ini selesai.
    ) else (
        echo Silakan buka Laragon -^> Klik Kanan -^> PHP -^> Extensions, lalu centang: intl, zip, gd.
        pause
        exit /b 1
    )
)

:: ----------------------------------------------------------------------------
:: 3. SIAPKAN FILE .env OFFLINE
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 2/7] Memeriksa Konfigurasi Environment (.env)...
if not exist ".env" (
    if exist ".env.offline" (
        copy /y ".env.offline" ".env" >nul
        echo [OK] File .env dibuat otomatis dari template .env.offline.
    ) else (
        copy /y ".env.example" ".env" >nul
        echo [OK] File .env dibuat dari .env.example.
    )
) else (
    echo [OK] File .env sudah ada.
)

:: Pastikan APP_KEY terisi
php -r "$env=file_get_contents('.env'); if(strpos($env,'APP_KEY=base64:GENERATE_OR_PASTE')!==false || preg_match('/^APP_KEY=\s*$/m',$env)){ exit(1); }"
if %ERRORLEVEL% NEQ 0 (
    echo [INFO] Meng-generate APP_KEY baru...
    call php artisan key:generate --force
)

:: Pastikan folder storage & bootstrap/cache lengkap (penting untuk DomPDF & Session)
if not exist "storage\fonts" mkdir "storage\fonts"
if not exist "storage\app\public" mkdir "storage\app\public"
if not exist "storage\app\private" mkdir "storage\app\private"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"

:: ----------------------------------------------------------------------------
:: 4. CEK KONEKSI MYSQL & BUAT DATABASE heavytrack_wms
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 3/7] Memeriksa Service MySQL ^& Membuat Database 'heavytrack_wms'...
where mysql >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS heavytrack_wms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
    if !ERRORLEVEL! EQU 0 (
        echo [OK] Database 'heavytrack_wms' siap digunakan di MySQL.
    ) else (
        color 0E
        echo [PERINGATAN] Gagal terhubung ke MySQL dengan user 'root' tanpa password.
        echo Pastikan MySQL di Laragon sudah berstatus 'Running' ^(klik Start All di Laragon^).
        pause
    )
) else (
    echo [PERINGATAN] Command 'mysql' tidak ditemukan di PATH. Pastikan MySQL Laragon aktif.
)

:: ----------------------------------------------------------------------------
:: 5. IMPORT FILE SQL DARI RAILWAY (JIKA ADA FILE heavytrack_backup.sql)
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 4/7] Memeriksa File Backup Database Railway (heavytrack_backup.sql)...
if exist "heavytrack_backup.sql" (
    echo [DITEMUKAN] File 'heavytrack_backup.sql' ditemukan di folder project!
    set /p IMPORT_CONFIRM="Apakah Anda ingin meng-import 'heavytrack_backup.sql' ke database sekarang? (Y/N): "
    if /i "!IMPORT_CONFIRM!"=="Y" (
        echo [PROSES] Sedang meng-import database dari heavytrack_backup.sql...
        mysql -u root heavytrack_wms < "heavytrack_backup.sql"
        if !ERRORLEVEL! EQU 0 (
            echo [OK] Import database Railway BERHASIL 100%%!
        ) else (
            echo [INFO] Mencoba membersihkan collation utf8mb4_0900_ai_ci dan meng-import ulang...
            powershell -NoProfile -Command "(Get-Content 'heavytrack_backup.sql') -replace 'utf8mb4_0900_ai_ci','utf8mb4_unicode_ci' | Set-Content 'heavytrack_backup_clean.sql'"
            mysql -u root heavytrack_wms < "heavytrack_backup_clean.sql"
            if !ERRORLEVEL! EQU 0 (
                echo [OK] Import database Railway BERHASIL setelah penyesuaian collation!
            ) else (
                color 0C
                echo [ERROR] Gagal meng-import database. Periksa pesan error di atas.
                pause
            )
        )
    ) else (
        echo [SKIP] Import database dilewati sesuai pilihan Anda.
    )
) else (
    echo [INFO] File 'heavytrack_backup.sql' tidak ditemukan di folder ini.
    echo        ^(Jika Anda punya backup dari Railway, beri nama 'heavytrack_backup.sql'
    echo        dan letakkan di C:\laragon\www\heavytrack-wms lalu jalankan script ini lagi^).
)

:: ----------------------------------------------------------------------------
:: 6. JALANKAN MIGRASI AMAN & SYMLINK STORAGE WINDOWS
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 5/7] Menjalankan Migrasi Skema Database ^& Symlink Storage...
call php artisan migrate --force

:: Jika database masih kosong (belum ada user), jalankan seeder awal
php -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); if(\App\Models\User::count() === 0) exit(1);" 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [INFO] Database baru terdeteksi: Mengisi data awal (Akun Owner, Admin, Gudang, ^& 19 Master Produk)...
    call php artisan db:seed --force
)

if exist "public\storage" (
    rmdir "public\storage" 2>nul
    del /f /q "public\storage" 2>nul
)
call php artisan storage:link
call php artisan filament:assets

:: ----------------------------------------------------------------------------
:: 7. CLEAR CACHE LINUX LAMA & OPTIMASI KECEPATAN OFFLINE
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 6/7] Membersihkan Cache Lama ^& Mengoptimalkan Performa Offline...
call php artisan optimize:clear
call php artisan permission:cache-reset
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache
call php artisan icons:cache

:: ----------------------------------------------------------------------------
:: 8. VALIDASI JUMLAH DATA (REKONSILIASI POST-MIGRATION)
:: ----------------------------------------------------------------------------
echo.
echo [TAHAP 7/7] Memvalidasi Integritas Data di Database Lokal...
echo ----------------------------------------------------------------------------
php artisan tinker --execute="echo '  - Total User             : ' . \App\Models\User::count() . PHP_EOL . '  - Total Master Part      : ' . \App\Models\Part::count() . PHP_EOL . '  - Total Gudang (WH)      : ' . \App\Models\Warehouse::count() . PHP_EOL . '  - Total Kontak/Klien     : ' . \App\Models\Contact::count() . PHP_EOL . '  - Total Goods Receipt    : ' . \App\Models\GoodsReceipt::count() . PHP_EOL . '  - Total Surat Jalan (DO) : ' . \App\Models\DeliveryOrder::count() . PHP_EOL . '  - Total Faktur (Invoice) : ' . \App\Models\Invoice::count() . PHP_EOL . '  - Total Saldo Stok Fisik : ' . number_format((float)\App\Models\StockBalance::sum('qty_on_hand'), 0, ',', '.') . PHP_EOL;"
echo ----------------------------------------------------------------------------

:: Buat file penanda permanen bahwa setup telah selesai
echo SETUP_COMPLETED on %DATE% %TIME% > "%~dp0.setup_done"
echo [OK] Tanda setup berhasil (.setup_done) telah disimpan.

echo.
echo ============================================================================
echo   SETUP OFFLINE SELESAI!
echo   1. Buka browser ke: http://localhost:8000/admin (jika menggunakan JALANKAN_SISTEM.bat)
echo   2. Atau http://heavytrack-wms.test/admin (jika via Virtual Host Laragon)
echo   3. Untuk penggunaan sehari-hari, cukup klik ganda: JALANKAN_SISTEM.bat
echo ============================================================================
echo.
if "%1"=="auto" (
    echo [INFO] Setup otomatis selesai. Melanjutkan ke peluncuran aplikasi...
    timeout /t 2 >nul
    exit /b 0
)
pause
