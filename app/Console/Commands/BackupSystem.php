<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupSystem extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Melakukan backup database WMS dan menyimpan log/dump secara otomatis';

    public function handle()
    {
        $this->info('Memulai proses backup database MySQL/MariaDB...');
        
        $db = env('DB_DATABASE');
        $user = env('DB_USERNAME');
        $pass = env('DB_PASSWORD');
        $host = env('DB_HOST');
        $port = env('DB_PORT', 3306);
        
        $date = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path('app/backups');
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = "{$backupDir}/db_backup_{$date}.sql";
        
        // Command untuk MySQL/MariaDB (mysqldump)
        $command = "mysqldump -h {$host} -P {$port} -u {$user} -p\"{$pass}\" {$db} > {$filename}";
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            $this->info("Backup berhasil dibuat: {$filename}");
            Log::info("Database backup created successfully at {$filename}");
        } else {
            $this->error("Backup gagal. Pastikan command mysqldump tersedia di server.");
            Log::error("Database backup failed.");
        }
    }
}
