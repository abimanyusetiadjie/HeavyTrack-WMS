<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\GoodsReceipt;
use App\Models\Part;
use App\Models\Contact;
use App\Models\Warehouse;

class AuditDatabaseCommand extends Command
{
    protected $signature = 'db:audit';
    protected $description = 'Audit database connection, locate duplicate databases on server, and check transaction history';

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("           HEAVYTRACK WMS - AUDIT INTEGRITAS & LOKASI DATABASE                  ");
        $this->info("================================================================================");

        $defaultConn = config('database.default');
        $this->line("1. KONEKSI AKTIF: <comment>{$defaultConn}</comment>");

        $dbConfig = config("database.connections.{$defaultConn}");
        $driver = $dbConfig['driver'] ?? 'unknown';
        $dbName = $dbConfig['database'] ?? 'unknown';

        $this->line("   - Driver   : {$driver}");
        $this->line("   - Database : {$dbName}");

        if ($driver === 'sqlite') {
            $isAbsolute = str_starts_with($dbName, '/') || str_starts_with($dbName, '\\') || preg_match('/^[a-zA-Z]:/', $dbName);
            $this->line("   - Tipe Path: " . ($isAbsolute ? "<info>ABSOLUT (Aman)</info>" : "<fg=red>RELATIF (Berisiko ganda jika working directory berbeda!)</fg=red>"));
            
            if (file_exists($dbName)) {
                $size = round(filesize($dbName) / 1024, 2);
                $mtime = date('Y-m-d H:i:s', filemtime($dbName));
                $this->line("   - Status   : <info>DITEMUKAN</info> (Ukuran: {$size} KB, Terakhir Diubah: {$mtime})");
            } else {
                $this->error("   - Status   : FILE TIDAK DITEMUKAN DI PATH TERSEBUT!");
            }
        }

        $this->newLine();
        $this->info("2. PEMINDAIAN DATABASE GANDA (SCANNING ALL SQLITE FILES)...");
        $scanDirs = [
            base_path(),
            base_path('database'),
            base_path('public'),
            dirname(base_path()),
        ];
        
        $foundSqlite = [];
        foreach ($scanDirs as $dir) {
            if (!is_dir($dir)) continue;
            try {
                $files = glob($dir . '/*.sqlite*') ?: [];
                foreach ($files as $f) {
                    if (str_ends_with($f, '-shm') || str_ends_with($f, '-wal')) continue;
                    $real = realpath($f) ?: $f;
                    if (!in_array($real, $foundSqlite)) {
                        $foundSqlite[] = $real;
                    }
                }
                // Check subdirectories database/
                $subFiles = glob($dir . '/*/*.sqlite*') ?: [];
                foreach ($subFiles as $f) {
                    if (str_ends_with($f, '-shm') || str_ends_with($f, '-wal')) continue;
                    $real = realpath($f) ?: $f;
                    if (!in_array($real, $foundSqlite)) {
                        $foundSqlite[] = $real;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $this->line("   Ditemukan total <comment>" . count($foundSqlite) . "</comment> file SQLite di sekitar aplikasi:");
        foreach ($foundSqlite as $idx => $f) {
            $fSize = round(filesize($f) / 1024, 2);
            $fTime = date('Y-m-d H:i:s', filemtime($f));
            $isCurrent = ($driver === 'sqlite' && realpath($dbName) === $f);
            $tag = $isCurrent ? " <fg=cyan>[DATABASE YANG DIGUNAKAN SAAT INI]</fg=cyan>" : "";
            $this->line("   [" . ($idx + 1) . "] {$f} ({$fSize} KB, {$fTime}){$tag}");

            // Cek isi transaksi di file ini jika bukan database aktif
            if (!$isCurrent) {
                try {
                    $pdo = new \PDO("sqlite:" . $f);
                    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
                    $stmt = $pdo->query("SELECT count(*) FROM delivery_orders");
                    if ($stmt !== false) {
                        $cnt = $stmt->fetchColumn();
                        $this->line("       -> Memiliki <comment>{$cnt}</comment> data Surat Jalan di dalamnya!");
                        if ($cnt > 0) {
                            $stmt2 = $pdo->query("SELECT id, do_number, delivery_date, created_at, status FROM delivery_orders ORDER BY id DESC LIMIT 3");
                            while ($r = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                                $this->line("          * ID {$r['id']} | {$r['do_number']} | {$r['delivery_date']} | {$r['created_at']} | {$r['status']}");
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        $this->newLine();
        $this->info("3. RINGKASAN DATA PADA DATABASE AKTIF SAAT INI:");
        try {
            $totalDO = DeliveryOrder::count();
            $trashedDO = DeliveryOrder::onlyTrashed()->count();
            $totalInv = Invoice::count();
            $totalGR = GoodsReceipt::count();
            $totalParts = Part::count();
            $totalContacts = Contact::count();
            $totalWarehouses = Warehouse::count();

            $this->line("   - Surat Jalan Aktif    : <comment>{$totalDO}</comment>");
            $this->line("   - Surat Jalan Dihapus (Soft Delete): <comment>{$trashedDO}</comment>");
            $this->line("   - Invoice Penjualan    : <comment>{$totalInv}</comment>");
            $this->line("   - Penerimaan Barang    : <comment>{$totalGR}</comment>");
            $this->line("   - Master Produk/Part   : <comment>{$totalParts}</comment>");
            $this->line("   - Kontak/Pelanggan     : <comment>{$totalContacts}</comment>");
            $this->line("   - Gudang Aktif         : <comment>{$totalWarehouses}</comment>");
        } catch (\Throwable $e) {
            $this->error("   Gagal membaca tabel database: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info("4. RIWAYAT SURAT JALAN TERAKHIR (TERMASUK STATUS & TANGGAL):");
        $allDos = DeliveryOrder::withTrashed()->orderByDesc('id')->take(10)->get();
        if ($allDos->isEmpty()) {
            $this->warn("   Tidak ada data Surat Jalan sama sekali pada database aktif ini.");
        } else {
            $headers = ['ID', 'Nomor SJ', 'Tgl Kirim', 'Dibuat Pada', 'Status', 'Kondisi'];
            $rows = [];
            foreach ($allDos as $do) {
                $kondisi = $do->trashed() ? 'TERHAPUS (Trash)' : 'AKTIF';
                $rows[] = [
                    $do->id,
                    $do->do_number,
                    $do->delivery_date ?? '-',
                    $do->created_at ? $do->created_at->format('Y-m-d H:i:s') : '-',
                    $do->status,
                    $kondisi,
                ];
            }
            $this->table($headers, $rows);
        }

        $this->newLine();
        $this->info("5. AUDIT PRODUK, LOKASI GUDANG & HARGA (TOP 30 PRODUK):");
        $parts = Part::with(['stockBalances.warehouse'])->orderBy('part_number')->take(30)->get();
        if ($parts->isEmpty()) {
            $this->warn("   Belum ada data produk di database.");
        } else {
            $partHeaders = ['ID', 'Nomor Part', 'Nama Barang', 'Lokasi (bin)', 'Rincian Stok Per Gudang', 'Total Stok', 'Harga Jual'];
            $partRows = [];
            foreach ($parts as $p) {
                $totStock = $p->stockBalances->sum('qty_on_hand');
                $perWh = $p->stockBalances->map(function ($sb) {
                    $code = $sb->warehouse?->code ?? ('WH#' . $sb->warehouse_id);
                    return "{$code}: {$sb->qty_on_hand}";
                })->implode(', ');
                $partRows[] = [
                    $p->id,
                    $p->part_number,
                    \Illuminate\Support\Str::limit($p->name, 22),
                    $p->bin_location ?? '-',
                    $perWh !== '' ? $perWh : '-',
                    $totStock . ' ' . ($p->unit ?? 'PCS'),
                    \App\Filament\Resources\ProductResource::formatPriceDisplay($p->selling_price),
                ];
            }
            $this->table($partHeaders, $partRows);
        }

        $this->newLine();
        $this->info("6. AUDIT MUTASI STOK TERAKHIR (LATEST STOCK LEDGER):");
        $ledgers = \App\Models\StockLedger::with('part')->orderByDesc('id')->take(6)->get();
        if ($ledgers->isEmpty()) {
            $this->warn("   Belum ada catatan mutasi stok di database.");
        } else {
            $ledgerHeaders = ['ID', 'Waktu', 'Nomor Part', 'Jenis Transaksi', 'No Ref', 'Qty Mutasi', 'Stok Akhir'];
            $ledgerRows = [];
            foreach ($ledgers as $l) {
                $ledgerRows[] = [
                    $l->id,
                    $l->created_at ? $l->created_at->format('Y-m-d H:i') : '-',
                    $l->part?->part_number ?? '-',
                    $l->reference_type,
                    $l->reference_number ?? '-',
                    ($l->qty_change > 0 ? '+' : '') . $l->qty_change,
                    $l->balance_after,
                ];
            }
            $this->table($ledgerHeaders, $ledgerRows);
        }

        $this->newLine();
        $this->info("7. AUDIT KESELARASAN STOK WEBSITE VS HISTORI DATABASE (stock_balances vs stock_ledgers):");
        $balances = \App\Models\StockBalance::with(['part' => fn ($q) => $q->withTrashed(), 'warehouse'])->get();
        $unsyncedRows = [];
        foreach ($balances as $b) {
            $ledgerSum = (int) \App\Models\StockLedger::where('warehouse_id', $b->warehouse_id)
                ->where('part_id', $b->part_id)
                ->sum('qty_change');
            if ((int) $b->qty_on_hand !== $ledgerSum) {
                $unsyncedRows[] = [
                    $b->warehouse?->name ?? ('Gudang #' . $b->warehouse_id),
                    $b->part?->part_number ?? ('Part #' . $b->part_id),
                    $b->qty_on_hand,
                    $ledgerSum,
                    (int) $b->qty_on_hand - $ledgerSum,
                ];
            }
        }
        if (empty($unsyncedRows)) {
            $this->info("   [SINKRON 100%] Seluruh saldo stok fisik di website cocok 100% dengan histori mutasi database (0 selisih).");
        } else {
            $this->warn("   [PERINGATAN] Ditemukan " . count($unsyncedRows) . " data stok yang belum sinkron (jalankan: php artisan stock:sync):");
            $this->table(['Gudang', 'Nomor Part', 'Stok Website', 'Total Mutasi DB', 'Selisih'], $unsyncedRows);
        }

        $this->newLine();
        $this->info("7B. AUDIT INVOICE PENJUALAN VS SURAT JALAN & MUTASI STOK RIIL (stock_ledgers):");
        $activeInvoices = Invoice::with(['contact', 'deliveryOrder', 'items.part.stockBalances'])
            ->where('status', '!=', 'VOID')
            ->orderBy('id')
            ->get();

        $unsyncedInvoices = [];
        foreach ($activeInvoices as $inv) {
            $analysis = \App\Filament\Resources\InvoiceResource::analyzeInvoiceStockSync($inv);
            if (! $analysis['is_synced']) {
                $itemSummary = $inv->items->map(function ($it) use ($analysis) {
                    $pn = $it->part?->part_number ?? $it->part_number_snapshot ?? ('Part#' . $it->part_id);
                    $curStock = $it->part ? (int) $it->part->stockBalances->sum('qty_on_hand') : 0;
                    $missing = $analysis['missing_qty_by_part'][(int) $it->part_id] ?? 0;
                    return "{$pn} (Qty Invoice: {$it->qty} | Belum Mutasi: {$missing} | Stok Kini: {$curStock})";
                })->implode('; ');
                $unsyncedInvoices[] = [
                    $inv->invoice_number,
                    $inv->invoice_date ?? '-',
                    $inv->contact?->company_name ?? '-',
                    $inv->deliveryOrder?->do_number ?? '-',
                    $analysis['status_label'],
                    $itemSummary ?: '(0 item)',
                ];
            }
        }

        if (empty($unsyncedInvoices)) {
            $this->info("   [OKE] Seluruh Invoice aktif telah terhubung ke Surat Jalan & 100% terpotong di StockLedger.");
        } else {
            $this->warn("   Ditemukan " . count($unsyncedInvoices) . " Invoice yang BELUM terpotong stoknya / mutasinya tidak cocok di database StockLedger:");
            $this->table(['No. Invoice', 'Tanggal', 'Pelanggan', 'Ref SJ', 'Status Riil Database', 'Rincian Item & Stok'], $unsyncedInvoices);
            $this->line("   Tips: Di menu Daftar Invoice web, klik tombol 'Buat SJ & Potong Stok' pada invoice yang bersangkutan.");
        }

        $this->newLine();
        $this->info("8. EVALUASI DAN REKOMENDASI QA:");
        if ($trashedDO > 0) {
            $this->warn("   [INFO] Terdapat {$trashedDO} data Surat Jalan yang berstatus 'Soft-Deleted'.");
            $this->line("   Pada Filament, data ini otomatis disembunyikan oleh filter.");
            $this->line("   Untuk melihatnya di web: Buka menu Surat Jalan -> Buka panel Filter -> Pilih 'Data Dihapus' -> 'Dengan Data Dihapus'.");
        }

        if ($driver === 'mysql') {
            $this->info("   [OKE] Sistem menggunakan database MySQL ({$dbName}) 100% secara terpusat.");
            if (count($foundSqlite) > 0) {
                $this->warn("   [PERINGATAN] Masih ditemukan " . count($foundSqlite) . " file SQLite sisa di server yang sebaiknya dihapus.");
            }
        } else {
            if (count($foundSqlite) > 1) {
                $this->warn("   [PERINGATAN] Ditemukan lebih dari 1 file SQLite di server!");
                $this->line("   Pastikan di file .env menggunakan path absolut: DB_DATABASE=/var/www/heavytrack-wms/database/database.sqlite");
            } else {
                $this->info("   [OKE] Lokasi database SQLite tunggal dan konsisten.");
            }
        }

        $this->info("================================================================================");
        return 0;
    }
}
