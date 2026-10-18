@echo off
setlocal EnableDelayedExpansion
title HeavyTrack WMS - Backup Database Harian

:: Masuk ke folder project
cd /d "%~dp0"

:: Deteksi MySQL Laragon
set "LARAGON_ROOT=C:\laragon"
if exist "D:\laragon" set "LARAGON_ROOT=D:\laragon"

where mysqldump >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "%LARAGON_ROOT%\bin\mysql" (
        for /d %%M in ("%LARAGON_ROOT%\bin\mysql\mysql-*") do set "MYSQL_BIN=%%M\bin"
        if defined MYSQL_BIN set "PATH=!MYSQL_BIN!;%PATH%"
    )
)

:: Tentukan folder tujuan backup (Default: D:\Backup_HeavyTrack_WMS, fallback ke C:\Backup_HeavyTrack_WMS)
set "BACKUP_DIR=D:\Backup_HeavyTrack_WMS"
if not exist "D:\" set "BACKUP_DIR=C:\Backup_HeavyTrack_WMS"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: Buat format tanggal YYYY-MM-DD_HHMM
for /f "tokens=*" %%t in ('powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd_HHmm'"') do set "TIMESTAMP=%%t"

set "BACKUP_FILE=%BACKUP_DIR%\heavytrack_wms_%TIMESTAMP%.sql"

echo [PROSES] Mem-backup database 'heavytrack_wms' ke %BACKUP_FILE%...
mysqldump -u root --single-transaction --routines --triggers heavytrack_wms > "%BACKUP_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Backup berhasil disimpan: %BACKUP_FILE%
    :: Hapus backup yang lebih tua dari 30 hari secara otomatis
    forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -30 /c "cmd /c del @path" 2>nul
) else (
    echo [ERROR] Gagal melakukan backup database! Pastikan MySQL Laragon sedang berjalan.
    exit /b 1
)
