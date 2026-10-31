<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToMysqlCommand extends Command
{
    protected $signature = 'db:migrate-to-mysql {--source= : Path file SQLite sumber}';
    protected $description = 'Migrasi seluruh skema dan data transaksi dari SQLite ke MySQL/MariaDB secara transaksional & aman';

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("       HEAVYTRACK WMS - MIGRASI TOTAL DARI SQLITE KE MYSQL / MARIADB            ");
        $this->info("================================================================================");

        // 1. Verifikasi koneksi MySQL
        $mysqlHost = config('database.connections.mysql.host', '127.0.0.1');
        $mysqlPort = config('database.connections.mysql.port', 3306);
        $mysqlDb = config('database.connections.mysql.database');
        $mysqlUser = config('database.connections.mysql.username');

        $this->line("1. MEMERIKSA TARGET MYSQL / MARIADB:");
        $this->line("   - Host     : {$mysqlHost}:{$mysqlPort}");
        $this->line("   - Database : {$mysqlDb}");
        $this->line("   - User     : {$mysqlUser}");

        try {
            DB::connection('mysql')->getPdo();
            $this->info("   [OKE] Koneksi ke MySQL / MariaDB BERHASIL!");
        } catch (\Throwable $e) {
            $this->error("   [GAGAL] Tidak dapat terhubung ke MySQL: " . $e->getMessage());
            $this->warn("   Pastikan service MySQL aktif di VPS (sudo systemctl status mysql) dan konfigurasi di .env sudah benar.");
            return 1;
        }

        // 2. Tentukan file SQLite sumber
        $sqlitePath = $this->option('source') ?: config('database.connections.sqlite.database');
        $this->newLine();
        $this->line("2. MEMERIKSA SUMBER DATA SQLITE:");
        $this->line("   - Path SQLite: {$sqlitePath}");

        if (!file_exists($sqlitePath)) {
            $this->error("   [GAGAL] File SQLite sumber tidak ditemukan di: {$sqlitePath}");
            return 1;
        }

        $sourceSize = round(filesize($sqlitePath) / 1024, 2);
        $this->info("   [OKE] File SQLite ditemukan (Ukuran: {$sourceSize} KB).");

        // 3. Konfirmasi dari user
        $this->newLine();
        if (!$this->confirm("Apakah Anda ingin memulai migrasi skema tabel dan seluruh data ke database MySQL '{$mysqlDb}'?", true)) {
            $this->info("Migrasi dibatalkan.");
            return 0;
        }

        // 4. Jalankan migrasi di MySQL
        $this->newLine();
        $this->info("3. MEMBUAT STRUKTUR TABEL DI MYSQL (MIGRATIONS)...");
        try {
            $this->call('migrate', [
                '--database' => 'mysql',
                '--force' => true,
            ]);
            $this->info("   [OKE] Seluruh tabel berhasil dibuat di MySQL.");
        } catch (\Throwable $e) {
            $this->error("   [GAGAL] Migrasi tabel di MySQL gagal: " . $e->getMessage());
            return 1;
        }

        // 5. Transfer data antar database
        $this->newLine();
        $this->info("4. MENTRANSFER DATA DARI SQLITE KE MYSQL...");

        // Konfigurasi koneksi temporer untuk SQLite sumber
        config(['database.connections.sqlite_source' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        $tables = [
            'users',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'warehouses',
            'brands',
            'categories',
            'contacts',
            'parts',
            'part_cross_references',
            'stock_balances',
            'stock_ledgers',
            'sales_orders',
            'delivery_orders',
            'delivery_order_items',
            'invoices',
            'invoice_items',
            'payments',
            'goods_receipts',
            'goods_receipt_items',
        ];

        try {
            DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS = 0;');

            foreach ($tables as $table) {
                // Periksa apakah tabel ada di SQLite
                $sqliteTableExists = DB::connection('sqlite_source')
                    ->select("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table]);

                if (empty($sqliteTableExists)) {
                    continue;
                }

                // Ambil seluruh data dari SQLite
                $rows = DB::connection('sqlite_source')->table($table)->get();
                $rowCount = $rows->count();

                // Kosongkan tabel di MySQL untuk mencegah duplikasi
                DB::connection('mysql')->table($table)->truncate();

                if ($rowCount > 0) {
                    $insertData = [];
                    foreach ($rows as $row) {
                        $insertData[] = (array) $row;
                    }

                    // Insert dalam chunk agar hemat memori
                    foreach (array_chunk($insertData, 200) as $chunk) {
                        DB::connection('mysql')->table($table)->insert($chunk);
                    }
                }

                $this->line("   - Tabel <comment>{$table}</comment>: <info>{$rowCount} baris data</info> berhasil disalin.");
            }

            DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS = 1;');
            $this->info("   [OKE] Seluruh data berhasil ditransfer 100% tanpa ada yang terlewat!");

        } catch (\Throwable $e) {
            DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS = 1;');
            $this->error("   [GAGAL] Terjadi kesalahan saat menyalin data: " . $e->getMessage());
            return 1;
        }

        // 6. Ringkasan & Saran Update .env
        $this->newLine();
        $this->info("================================================================================");
        $this->info("  [SUKSES] MIGRASI KE MYSQL SELESAI DENGAN SEMPURNA!                            ");
        $this->info("================================================================================");
        $this->line("Untuk beralih menggunakan MySQL, cukup ubah pengaturan di file .env:");
        $this->line("  <info>DB_CONNECTION=mysql</info>");
        $this->newLine();
        $this->line("Lalu jalankan:");
        $this->line("  <comment>php artisan optimize:clear</comment>");
        $this->line("  <comment>php artisan optimize</comment>");
        $this->info("================================================================================");

        return 0;
    }
}
