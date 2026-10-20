@echo off
title HeavyTrack WMS - Ambil Database langsung dari Railway (Tanpa DBeaver)
color 0B

echo ============================================================================
echo   DOWNLOAD DATABASE DARI RAILWAY KE heavytrack_backup.sql
echo   (Tanpa DBeaver / Tanpa Perlu Laragon Menyala)
echo ============================================================================
echo.
set /p RAILWAY_URL="Paste MYSQL_PUBLIC_URL dari Railway di sini lalu tekan ENTER: "

if "%RAILWAY_URL%"=="" (
    color 0C
    echo [ERROR] URL tidak boleh kosong!
    pause
    exit /b 1
)

cd /d "%~dp0"

:: Cari PHP dari PATH, XAMPP, atau Laragon
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
    if exist "C:\laragon\bin\php" (
        for /d %%D in ("C:\laragon\bin\php\php-8*") do set "PATH=%%D;%PATH%"
    )
)

echo.
php dump-railway.php "%RAILWAY_URL%" "%~dp0heavytrack_backup.sql"

if %ERRORLEVEL% EQU 0 (
    color 0A
    echo ============================================================================
    echo   SELESAI! File heavytrack_backup.sql sudah siap di folder project Anda.
    echo ============================================================================
) else (
    color 0C
    echo [ERROR] Gagal mengunduh database dari Railway.
)

echo.
pause
