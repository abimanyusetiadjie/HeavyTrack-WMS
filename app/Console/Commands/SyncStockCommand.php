<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

class SyncStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit dan rekonsiliasi integritas saldo stok fisik dengan riwayat buku mutasi (StockLedger)';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $this->info('Memulai audit dan sinkronisasi saldo stok...');

        $result = $inventoryService->reconcileStock();

        $this->info("Total data stok yang diaudit: {$result['audited']}");

        if ($result['discrepancies'] === 0) {
            $this->info('✓ SEMUA STOK 100% SINKRON! Tidak ditemukan selisih antara saldo fisik dan buku mutasi.');
            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$result['discrepancies']} varian stok yang berselisih dan telah direkonsiliasi otomatis:");

        $tableData = [];
        foreach ($result['details'] as $detail) {
            $tableData[] = [
                $detail['warehouse'],
                $detail['part_number'],
                $detail['part_name'],
                $detail['balance_before'],
                $detail['ledger_sum'],
            ];
        }

        $this->table(['Gudang', 'Part Number', 'Nama Barang', 'Saldo Sebelum', 'Hasil Mutasi Ledger'], $tableData);
        $this->info('✓ Rekonsiliasi selesai.');

        return self::SUCCESS;
    }
}
