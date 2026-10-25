@echo off
setlocal EnableDelayedExpansion
title HeavyTrack WMS - Mitra Abadi Nusantara (Sistem Toko & Pergudangan)
color 0A

:: Pastikan selalu berjalan di folder project
cd /d "%~dp0"

:: ----------------------------------------------------------------------------
:: 1. Deteksi Path PHP & MySQL (Laragon di C: atau D:, atau XAMPP)
:: ----------------------------------------------------------------------------
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

:: ----------------------------------------------------------------------------
:: 2. DETEKSI STATUS SETUP (HANYA BERJALAN 1 KALI SEUMUR HIDUP DI PC BARU)
:: ----------------------------------------------------------------------------
if not exist ".setup_done" (
    if not exist ".env" (
        cls
        echo ============================================================================
        echo   HEAVYTRACK WMS - PERSIAPAN SISTEM AWAL (HANYA 1 KALI DI PC INI)
        echo ============================================================================
        echo.
        echo   Sistem mendeteksi komputer ini baru pertama kali dipasang.
        echo   Menyiapkan konfigurasi otomatis... Harap tunggu sebentar...
        echo.
        call setup-offline.bat auto
        echo SETUP_COMPLETED on %DATE% %TIME% > ".setup_done"
        cls
    ) else (
        echo SETUP_COMPLETED on %DATE% %TIME% > ".setup_done"
    )
)

:: ----------------------------------------------------------------------------
:: 3. PASTIKAN CACHE PRODUKSI SUDAH AKTIF (AGAR LOADING SUPER CEPAT)
:: ----------------------------------------------------------------------------
if not exist "bootstrap\cache\config.php" (
    echo [INFO] Mengoptimalkan kecepatan sistem...
    call php artisan optimize >nul 2>nul
)

:: ----------------------------------------------------------------------------
:: 4. CEK KONEKSI MYSQL (HANYA JIKA .env DISET MENGGUNAKAN MYSQL)
:: ----------------------------------------------------------------------------
if exist ".env" (
    findstr /i "DB_CONNECTION=mysql" .env >nul 2>nul
    if !ERRORLEVEL! EQU 0 (
        netstat -ano | findstr ":3306" >nul 2>nul
        if !ERRORLEVEL! NEQ 0 (
            color 0E
            echo ============================================================================
            echo   [INFO] Menunggu database MySQL aktif...
            echo ============================================================================
            net start mysql >nul 2>nul
            timeout /t 2 >nul
            netstat -ano | findstr ":3306" >nul 2>nul
            if !ERRORLEVEL! NEQ 0 (
                echo   Membuka aplikasi Laragon...
                if exist "%LARAGON_ROOT%\laragon.exe" (
                    start "" "%LARAGON_ROOT%\laragon.exe"
                )
                echo.
                echo   Silakan klik tombol [Start All] di Laragon,
                echo   lalu tekan sembarang tombol di sini untuk melanjutkan...
                pause >nul
                color 0A
            )
        )
    )
)

:: ----------------------------------------------------------------------------
:: 5. BERSIHKAN PORT 8000 JIKA MASIH TERTINGGAL DARI SESI SEBELUMNYA
:: ----------------------------------------------------------------------------
for /f "tokens=5" %%p in ('netstat -aon 2^>nul ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%p >nul 2>nul
)

:: ----------------------------------------------------------------------------
:: 6. DETEKSI IP LOKAL UNTUK AKSES VIA HP / TABLET DI WI-FI TOKO
:: ----------------------------------------------------------------------------
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
echo   [STATUS] Server Lokal OFFLINE Aktif dan Berjalan Cepat!
echo.
echo   ----------------------------------------------------------------------
echo   AKSES DARI KOMPUTER INI:
echo   --^> Buka Browser: http://127.0.0.1:8000/admin
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
echo   (Jika ingin mematikan sistem saat tutup toko, cukup tutup jendela ini [X])
echo ============================================================================
echo.

:: Otomatis buka browser default ke alamat IP 127.0.0.1 (Menghindari delay IPv6 localhost)
start "" "http://127.0.0.1:8000/admin"

:: Aktifkan Multi-Worker PHP Server agar bisa memproses beberapa asset bersamaan
set PHP_CLI_SERVER_WORKERS=4

:: Jalankan server lokal Laravel
php artisan serve --host=0.0.0.0 --port=8000
pause
