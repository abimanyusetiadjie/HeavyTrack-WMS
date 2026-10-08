<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Exception;

class InventoryService
{
    /**
     * Menambah stok secara atomik
     */
    public function addStock(int $warehouseId, int $partId, int $qty, string $referenceType, int $referenceId, string $referenceNumber, string $notes = '')
    {
        if ($qty <= 0) {
            throw new Exception("Kuantitas penambahan stok harus lebih besar dari 0.");
        }

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $referenceType, $referenceId, $referenceNumber, $notes) {
            // Gunakan lockForUpdate untuk mencegah race condition
            $stock = StockBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                // Buat jika belum ada
                $stock = StockBalance::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ]);
            }

            $before = $stock->qty_on_hand;
            $stock->qty_on_hand += $qty;
            $stock->save();

            // Catat ke ledger
            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'qty_change' => $qty, // Positif
                'balance_before' => $before,
                'balance_after' => $stock->qty_on_hand,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            return $stock;
        });
    }

    /**
     * Mengurangi stok secara atomik
     */
    public function deductStock(int $warehouseId, int $partId, int $qty, string $referenceType, int $referenceId, string $referenceNumber, string $notes = '')
    {
        if ($qty <= 0) {
            throw new Exception("Kuantitas pengurangan stok harus lebih besar dari 0.");
        }

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $referenceType, $referenceId, $referenceNumber, $notes) {
            $stock = StockBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->qty_on_hand < $qty) {
                throw new Exception("Stok tidak mencukupi untuk Part ID: " . $partId);
            }

            $before = $stock->qty_on_hand;
            $stock->qty_on_hand -= $qty;
            $stock->save();

            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'qty_change' => -$qty, // Negatif
                'balance_before' => $before,
                'balance_after' => $stock->qty_on_hand,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            return $stock;
        });
    }
}
