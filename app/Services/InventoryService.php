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
            $stock = StockBalance::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                ['qty_on_hand' => 0, 'qty_reserved' => 0]
            );

            // Kunci record untuk mencegah race condition pada update bersamaan
            $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

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
                $part = \App\Models\Part::find($partId);
                $partName = $part ? "{$part->part_number} ({$part->name})" : "Part ID #{$partId}";
                $available = $stock ? $stock->qty_on_hand : 0;
                throw new Exception("Stok fisik tidak mencukupi untuk sparepart {$partName}. Sisa stok di gudang ini: {$available}, dibutuhkan: {$qty}.");
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
            $stock = StockBalance::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                ['qty_on_hand' => 0, 'qty_reserved' => 0]
            );

            $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

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

    /**
     * Audit dan rekonsiliasi integritas stok antara saldo fisik (StockBalance) dan mutasi (StockLedger).
     *
     * @return array{audited: int, discrepancies: int, details: array}
     */
    public function reconcileStock(): array
    {
        return DB::transaction(function () {
            $balances = StockBalance::with(['part', 'warehouse'])->get();
            $discrepancies = 0;
            $details = [];

            foreach ($balances as $balance) {
                // Hitung total mutasi dari seluruh kartu stok untuk gudang & part ini
                $ledgerSum = (int) StockLedger::where('warehouse_id', $balance->warehouse_id)
                    ->where('part_id', $balance->part_id)
                    ->sum('qty_change');

                if ($balance->qty_on_hand !== $ledgerSum) {
                    $discrepancies++;
                    $details[] = [
                        'warehouse' => $balance->warehouse?->name ?? "Gudang #{$balance->warehouse_id}",
                        'part_number' => $balance->part?->part_number ?? '-',
                        'part_name' => $balance->part?->name ?? '-',
                        'balance_before' => $balance->qty_on_hand,
                        'ledger_sum' => $ledgerSum,
                    ];

                    // Rekonsiliasi otomatis: sesuaikan balance fisik agar sama dengan akumulasi ledger
                    $balance->qty_on_hand = max(0, $ledgerSum);
                    $balance->save();
                }
            }

            return [
                'audited' => $balances->count(),
                'discrepancies' => $discrepancies,
                'details' => $details,
            ];
        });
    }
}
