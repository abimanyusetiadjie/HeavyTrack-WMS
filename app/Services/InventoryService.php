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
            $stock = StockBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
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

            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'qty_change' => $qty,
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
                'qty_change' => -$qty,
                'balance_before' => $before,
                'balance_after' => $stock->qty_on_hand,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            return $stock;
        });
    }

    /**
     * Menyesuaikan stok fisik secara langsung (Stock Opname / Penyesuaian Cepat)
     */
    public function adjustStock(int $warehouseId, int $partId, int $newTargetQty, string $notes = 'Penyesuaian Stok Fisik')
    {
        if ($newTargetQty < 0) {
            throw new Exception("Jumlah stok tidak boleh bernilai negatif.");
        }

        return DB::transaction(function () use ($warehouseId, $partId, $newTargetQty, $notes) {
            $stock = StockBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = StockBalance::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ]);
            }

            $before = $stock->qty_on_hand;
            $diff = $newTargetQty - $before;

            if ($diff === 0) {
                return $stock;
            }

            $stock->qty_on_hand = $newTargetQty;
            $stock->save();

            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'reference_type' => 'STOCK_ADJUSTMENT',
                'reference_id' => $stock->id,
                'reference_number' => 'ADJ/' . date('Ymd') . '/' . $partId,
                'qty_change' => $diff,
                'balance_before' => $before,
                'balance_after' => $stock->qty_on_hand,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            return $stock;
        });
    }
}
