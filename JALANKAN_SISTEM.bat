@echo off
setlocal EnableDelayedExpansion
title HeavyTrack WMS - Mitra Abadi Nusantara (Sistem Toko & Pergudangan)
color 0A

:: Pastikan berjalan di folder project
cd /d "%~dp0"

:: 1. Deteksi Path PHP & MySQL (Laragon di C: atau D:, atau XAMPP)
set "LARAGON_ROOT=C:\laragon"
if exist "D:\laragon" set "LARAGON_ROOT=D:\laragon"

where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "%LARAGON_ROOT%\bin\php" (
        for /d %%D in ("%LARAGON_ROOT%\bin\php\php-8*") do set "PATH=%%D;%LARAGON_ROOT%\bin\composer;!PATH!"
    )
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;!PATH!"
    )
)

where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "%LARAGON_ROOT%\bin\mysql" (
        for /d %%M in ("%LARAGON_ROOT%\bin\mysql\mysql-*") do set "PATH=%%M\bin;!PATH!"
    )
    if exist "C:\xampp\mysql\bin" (
        set "PATH=C:\xampp\mysql\bin;!PATH!"
    )
)

where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    color 0C
    echo ============================================================================
    echo   [ERROR] Program PHP tidak ditemukan di sistem Windows ini!
    echo   Pastikan Laragon atau XAMPP sudah terpasang di komputer Anda.
    echo ============================================================================
    echo.
    pause
    exit /b 1
)

:: 2. DETEKSI OTOMATIS: APAKAH SISTEM SUDAH PERNAH DI-SETUP?
set "NEED_SETUP=0"

if not exist ".env" set "NEED_SETUP=1"
if not exist "storage\framework\views" set "NEED_SETUP=1"

:: Cek apakah database dan environment sudah siap digunakan
if "!NEED_SETUP!"=="0" (
    php -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); try { \Illuminate\Support\Facades\DB::connection()->getPdo(); } catch (\Throwable $e) { exit(1); }" 2>nul
    if !ERRORLEVEL! NEQ 0 (
        set "NEED_SETUP=1"
    )
)

:: Jika belum di-setup, jalankan wizard setup otomatis di latar belakang
if "!NEED_SETUP!"=="1" (
    cls
    echo ============================================================================
    echo   HEAVYTRACK WMS - PERSIAPAN SISTEM OTOMATIS (FIRST-TIME SETUP)
    echo ============================================================================
    echo.
    echo   [INFO] Sistem mendeteksi komputer ini belum di-setup atau database belum aktif.
    echo   Sedang melakukan konfigurasi otomatis... Harap tunggu sebentar...
    echo.
    call setup-offline.bat auto
    cls
)

:: 3. CEK APAKAH MYSQL LARAGON SUDAH MENYALA (JIKA PAKAI MYSQL)
php -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); if (config('database.default') === 'mysql') exit(1); exit(0);" 2>nul
if %ERRORLEVEL% NEQ 0 (
    netstat -ano | findstr ":3306" >nul 2>nul
    if !ERRORLEVEL! NEQ 0 (
        color 0E
        echo ============================================================================
        echo   [PERINGATAN] SERVICE MYSQL BELUM DIAKTIFKAN DI LARAGON!
        echo ============================================================================
        echo   Laragon Anda belum menyalakan database MySQL.
        echo   Silakan buka aplikasi LARAGON lalu klik tombol: [Start All]
        echo ============================================================================
        echo.
        if exist "%LARAGON_ROOT%\laragon.exe" (
            echo   Membuka aplikasi Laragon untuk Anda...
            start "" "%LARAGON_ROOT%\laragon.exe"
        )
        echo   Tekan tombol apa saja setelah mengklik 'Start All' di Laragon...
        pause >nul
    )
)

:: 4. Deteksi IP Lokal untuk akses via HP / Tablet di Wi-Fi Toko
set "LOCAL_IP=127.0.0.1"
for /f "tokens=4" %%a in ('route print 0.0.0.0 2^>nul ^| findstr "0.0.0.0.*0.0.0.0"') do (
    set "LOCAL_IP=%%a"
)

cls
echo ============================================================================
echo   HEAVYTRACK WMS - MITRA ABADI NUSANTARA
echo   Sistem Manajemen Stok Sparepart ^& Dokumen Faktur / Surat Jalan
echo ============================================================================
echo.
echo   [STATUS] Server Lokal OFFLINE Aktif dan Berjalan!
echo.
echo   ----------------------------------------------------------------------
echo   AKSES DARI KOMPUTER INI:
echo   --^> Buka Browser: http://localhost:8000/admin
echo.
echo   AKSES DARI HP / LAPTOP LAIN (Wi-Fi Toko):
echo   --^> Buka Browser: http://!LOCAL_IP!:8000/admin
echo   ----------------------------------------------------------------------
echo.
echo   KREDENSIAL LOGIN:
echo   - Role Owner : Username = owner  ^| Password = 12345678
echo   - Role Kasir : Username = admin  ^| Password = 12345678
echo.
echo ============================================================================
echo   PERINGATAN: JANGAN TUTUP JENDELA HITAM INI SELAMA APLIKASI DIGUNAKAN!
echo   (Jika ingin mematikan sistem, tekan tombol silang [X] atau Ctrl+C)
echo ============================================================================
echo.

:: Otomatis buka browser default ke halaman login admin
start "" "http://localhost:8000/admin"

:: Jalankan server lokal Laravel
php artisan serve --host=0.0.0.0 --port=8000
pause
