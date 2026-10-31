<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore-sqlite {source? : Path file sqlite sumber yang ingin dipulihkan}';
    protected $description = 'Pulihkan data dari file SQLite cadangan ke database aktif dengan aman';

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("           HEAVYTRACK WMS - PEMULIHAN DATABASE DARI CADANGAN (RESTORE)          ");
        $this->info("================================================================================");

        $defaultConn = config('database.default');
        if ($defaultConn !== 'sqlite') {
            $this->error("Koneksi aktif bukan SQLite ({$defaultConn}). Fitur ini khusus database SQLite.");
            return 1;
        }

        $activeDbPath = config('database.connections.sqlite.database');
        $this->line("Target Database Aktif: <comment>{$activeDbPath}</comment>");

        $source = $this->argument('source');

        // Jika tidak ada argumen source, cari otomatis file-file sqlite di sekitar sistem
        if (!$source) {
            $scanDirs = [
                base_path('Backup_HeavyTrack'),
                base_path('storage/app/backups'),
                base_path('database'),
                base_path(),
                dirname(base_path()),
            ];

            $candidates = [];
            foreach ($scanDirs as $dir) {
                if (!is_dir($dir)) continue;
                $files = glob($dir . '/*.sqlite*') ?: [];
                foreach ($files as $f) {
                    if (str_ends_with($f, '-shm') || str_ends_with($f, '-wal')) continue;
                    $real = realpath($f) ?: $f;
                    if ($real === realpath($activeDbPath)) continue;
                    if (!in_array($real, $candidates)) {
                        $candidates[] = $real;
                    }
                }
            }

            if (empty($candidates)) {
                $this->warn("Tidak ditemukan file cadangan SQLite otomatis di folder backup.");
                $source = $this->ask("Masukkan path lengkap file sqlite yang ingin dipulihkan");
            } else {
                $this->info("Ditemukan file cadangan SQLite berikut:");
                $choices = [];
                foreach ($candidates as $idx => $c) {
                    $cSize = round(filesize($c) / 1024, 2);
                    $cTime = date('Y-m-d H:i:s', filemtime($c));
                    
                    // Cek jumlah transaksi DO di dalamnya
                    $doCount = 0;
                    try {
                        $pdo = new \PDO("sqlite:" . $c);
                        $stmt = $pdo->query("SELECT count(*) FROM delivery_orders");
                        if ($stmt) $doCount = $stmt->fetchColumn();
                    } catch (\Throwable $e) {}

                    $label = "[{$idx}] {$c} ({$cSize} KB, {$cTime}) - {$doCount} Surat Jalan";
                    $choices[$c] = $label;
                }

                $source = $this->choice("Pilih file cadangan yang ingin di-restore ke database aktif", array_keys($choices));
            }
        }

        if (!file_exists($source)) {
            $this->error("File sumber tidak ditemukan di: {$source}");
            return 1;
        }

        if (!$this->confirm("Apakah Anda yakin ingin mengganti database aktif dengan file: {$source}?", true)) {
            $this->info("Operasi dibatalkan.");
            return 0;
        }

        // 1. Buat cadangan database aktif saat ini terlebih dahulu sebagai pengaman (safety backup)
        if (file_exists($activeDbPath)) {
            $safetyBackup = $activeDbPath . '.pre_restore_' . date('Ymd_His');
            copy($activeDbPath, $safetyBackup);
            $this->info("1. Database saat ini telah diamankan ke: <comment>{$safetyBackup}</comment>");
        }

        // 2. Hapus file WAL & SHM lama agar tidak terjadi konflik data memori
        $wal = $activeDbPath . '-wal';
        $shm = $activeDbPath . '-shm';
        if (file_exists($wal)) @unlink($wal);
        if (file_exists($shm)) @unlink($shm);

        // 3. Salin file cadangan ke database aktif
        if (copy($source, $activeDbPath)) {
            $this->info("2. File database berhasil disalin ke: <comment>{$activeDbPath}</comment>");
        } else {
            $this->error("Gagal menyalin file database. Periksa permission folder.");
            return 1;
        }

        // 4. Bersihkan cache framework
        $this->call('optimize:clear');
        $this->call('optimize');

        $this->info("================================================================================");
        $this->info("  [SUKSES] Pemulihan database selesai!");
        $this->info("  Silakan jalankan 'php artisan db:audit' untuk memverifikasi data Anda.");
        $this->info("================================================================================");

        return 0;
    }
}
