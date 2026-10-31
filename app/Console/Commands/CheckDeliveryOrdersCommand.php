<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Warehouse;

class CheckDeliveryOrdersCommand extends Command
{
    protected $signature = 'do:check {--restore-all : Otomatis pulihkan surat jalan yang terhapus atau hilang}';
    protected $description = 'Periksa, temukan, dan pulihkan data Surat Jalan di MySQL maupun file cadangan SQLite';

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("           HEAVYTRACK WMS - DIAGNOSA & PEMULIHAN SURAT JALAN                    ");
        $this->info("================================================================================");

        $activeConn = config('database.default');
        $this->line("1. DATABASE AKTIF SAAT INI: <comment>{$activeConn}</comment>");

        // 1. Cek isi tabel delivery_orders di database aktif
        $totalDoActive = DeliveryOrder::count();
        $totalDoTrashed = DeliveryOrder::onlyTrashed()->count();
        $allDos = DeliveryOrder::withTrashed()->get();

        $this->line("   - Surat Jalan Aktif        : <info>{$totalDoActive}</info>");
        $this->line("   - Surat Jalan Terhapus/Trash: <comment>{$totalDoTrashed}</comment>");

        if ($allDos->isNotEmpty()) {
            $this->newLine();
            $this->info("DAFTAR SURAT JALAN DI DATABASE AKTIF:");
            $headers = ['ID', 'Nomor SJ', 'Tgl Kirim', 'Pelanggan ID', 'Status', 'Kondisi'];
            $rows = [];
            foreach ($allDos as $do) {
                $rows[] = [
                    $do->id,
                    $do->do_number,
                    $do->delivery_date ?? '-',
                    $do->contact_id ?? '-',
                    $do->status,
                    $do->trashed() ? '<fg=red>TERHAPUS (Soft-Deleted)</fg=red>' : '<info>AKTIF</info>',
                ];
            }
            $this->table($headers, $rows);

            if ($totalDoTrashed > 0) {
                $this->warn("Terdapat {$totalDoTrashed} Surat Jalan yang berstatus terhapus (Soft-Deleted) sehingga tidak muncul di web.");
                if ($this->option('restore-all') || $this->confirm("Apakah Anda ingin memulihkan (restore) seluruh Surat Jalan yang terhapus agar tampil kembali di web?", true)) {
                    DeliveryOrder::onlyTrashed()->restore();
                    $this->info("[SUKSES] Seluruh Surat Jalan berhasil dipulihkan!");
                }
            }
        }

        // 2. Cek hubungan dengan tabel Invoices (Faktur Penjualan)
        $this->newLine();
        $this->info("2. MEMERIKSA FAKTUR PENJUALAN YANG TERSEDIA:");
        $invoices = Invoice::with(['contact', 'items'])->get();
        $this->line("   Ditemukan total <comment>{$invoices->count()}</comment> Faktur di database.");

        $missingDoInvoices = [];
        if ($invoices->isNotEmpty()) {
            $invHeaders = ['ID Faktur', 'Nomor Faktur', 'Tgl Faktur', 'Pelanggan', 'Total Tagihan', 'Ref Surat Jalan ID'];
            $invRows = [];
            foreach ($invoices as $inv) {
                $hasDo = $inv->delivery_order_id && DeliveryOrder::where('id', $inv->delivery_order_id)->exists();
                $doTag = $inv->delivery_order_id 
                    ? ($hasDo ? "<info>DO #{$inv->delivery_order_id}</info>" : "<fg=red>DO #{$inv->delivery_order_id} (HILANG)</fg=red>")
                    : "<comment>Tanpa Surat Jalan</comment>";

                if ($inv->delivery_order_id && !$hasDo) {
                    $missingDoInvoices[] = $inv;
                }

                $invRows[] = [
                    $inv->id,
                    $inv->invoice_number,
                    $inv->invoice_date ?? '-',
                    $inv->contact?->company_name ?? ('ID #' . $inv->contact_id),
                    'Rp ' . number_format((float) ($inv->grand_total ?? 0), 0, ',', '.'),
                    $doTag,
                ];
            }
            $this->table($invHeaders, $invRows);
        }

        // 3. Scan file SQLite di server untuk mencari data Surat Jalan lama
        $this->newLine();
        $this->info("3. MEMINDAI FILE CADANGAN SQLITE DI SERVER...");
        $scanDirs = [
            base_path('Backup_HeavyTrack'),
            base_path('storage/app/backups'),
            base_path('database'),
            base_path(),
            dirname(base_path()),
        ];

        $foundSourcesWithDo = [];
        foreach ($scanDirs as $dir) {
            if (!is_dir($dir)) continue;
            $files = glob($dir . '/*.sqlite*') ?: [];
            foreach ($files as $f) {
                if (str_ends_with($f, '-shm') || str_ends_with($f, '-wal')) continue;
                $real = realpath($f) ?: $f;
                if (in_array($real, $foundSourcesWithDo)) continue;

                try {
                    $pdo = new \PDO("sqlite:" . $real);
                    $stmt = $pdo->query("SELECT count(*) FROM delivery_orders");
                    if ($stmt) {
                        $cnt = (int) $stmt->fetchColumn();
                        if ($cnt > 0) {
                            $foundSourcesWithDo[$real] = $cnt;
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        if (!empty($foundSourcesWithDo)) {
            $this->info("Ditemukan file cadangan yang menyimpan data Surat Jalan:");
            foreach ($foundSourcesWithDo as $filePath => $count) {
                $this->line("   - <comment>{$filePath}</comment> : <info>{$count} Surat Jalan</info>");
            }

            $firstBackup = array_key_first($foundSourcesWithDo);
            if ($this->option('restore-all') || $this->confirm("Apakah Anda ingin mengimpor {$foundSourcesWithDo[$firstBackup]} Surat Jalan dari '{$firstBackup}' ke database aktif?", true)) {
                $this->importFromSqlite($firstBackup);
            }
        } else {
            $this->line("   Tidak ada file SQLite eksternal dengan Surat Jalan.");
        }

        // 4. Jika ada faktur yang kehilangan DO dan tabel DO masih kosong, buatkan DO otomatis dari faktur
        if (DeliveryOrder::count() === 0 && $invoices->isNotEmpty()) {
            $this->newLine();
            $this->warn("Tabel Surat Jalan masih kosong, tetapi Faktur Anda memiliki rincian barang.");
            if ($this->option('restore-all') || $this->confirm("Apakah Anda ingin otomatis meng-generate Surat Jalan dari Faktur Penjualan yang ada?", true)) {
                $this->generateDoFromInvoices($invoices);
            }
        }

        // Bersihkan cache Filament
        $this->call('optimize:clear');
        $this->call('optimize');

        $this->newLine();
        $this->info("================================================================================");
        $this->info("  Pemeriksaan selesai. Total Surat Jalan aktif sekarang: " . DeliveryOrder::count());
        $this->info("================================================================================");

        return 0;
    }

    protected function importFromSqlite(string $sqlitePath): void
    {
        $this->line("Mengimpor data Surat Jalan dari SQLite: {$sqlitePath}...");
        config(['database.connections.sqlite_import' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        } catch (\Throwable $e) {}

        $dos = DB::connection('sqlite_import')->table('delivery_orders')->get();
        $doItems = DB::connection('sqlite_import')->table('delivery_order_items')->get();

        foreach ($dos as $do) {
            $arr = (array) $do;
            $arr['deleted_at'] = null; // Pastikan aktif
            DB::table('delivery_orders')->updateOrInsert(['id' => $arr['id']], $arr);
        }

        foreach ($doItems as $item) {
            $arr = (array) $item;
            DB::table('delivery_order_items')->updateOrInsert(['id' => $arr['id']], $arr);
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        } catch (\Throwable $e) {}

        $this->info("[SUKSES] " . count($dos) . " Surat Jalan dan " . count($doItems) . " rincian barang berhasil diimpor!");
    }

    protected function generateDoFromInvoices($invoices): void
    {
        $defaultWarehouseId = Warehouse::where('is_active', true)->value('id') ?? 1;

        foreach ($invoices as $inv) {
            $seq = str_pad($inv->id, 4, '0', STR_PAD_LEFT);
            $doNumber = 'SJ/' . date('Ym', strtotime($inv->invoice_date ?? now())) . '/' . $seq;

            $do = DeliveryOrder::create([
                'id' => $inv->delivery_order_id ?: $inv->id,
                'do_number' => $doNumber,
                'warehouse_id' => $defaultWarehouseId,
                'contact_id' => $inv->contact_id,
                'delivery_date' => $inv->invoice_date ?? now()->toDateString(),
                'status' => 'CONFIRMED',
                'created_by' => $inv->created_by ?? 1,
            ]);

            // Update faktur agar menghubungkan ke DO ini
            $inv->update(['delivery_order_id' => $do->id]);

            // Salin rincian barang dari faktur
            foreach ($inv->items as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'part_id' => $item->part_id,
                    'qty' => $item->qty,
                    'unit' => $item->unit ?? 'PCS',
                ]);
            }

            $this->line("   - Dibuatkan Surat Jalan <info>{$doNumber}</info> untuk Faktur {$inv->invoice_number}");
        }

        $this->info("[SUKSES] Seluruh Surat Jalan berhasil dibuat dari data Faktur!");
    }
}
