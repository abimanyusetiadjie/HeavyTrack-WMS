@echo off
setlocal EnableDelayedExpansion
title HeavyTrack WMS - Mitra Abadi Nusantara (Sistem Toko & Pergudangan)
color 0A

:: Pastikan berjalan di folder project
cd /d "%~dp0"

:: Deteksi PHP dari PATH, Laragon, atau XAMPP
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\laragon\bin\php" (
        for /d %%D in ("C:\laragon\bin\php\php-8*") do set "PATH=%%D;C:\laragon\bin\composer;!PATH!"
    )
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;!PATH!"
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

:: Deteksi IP Lokal untuk akses via HP / Tablet di Wi-Fi Toko
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
