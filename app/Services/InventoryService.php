<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\GoodsReceipt;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\DB;

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
                $part = Part::withTrashed()->find($partId);
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
     * Sinkronisasi penuh antara dokumen Surat Jalan (DeliveryOrder) beserta seluruh itemnya
     * terhadap StockBalance dan StockLedger secara idempoten.
     */
    public function syncDeliveryOrderStock(DeliveryOrder $do, bool $isDeleting = false, bool $duringReconcile = false): int
    {
        return DB::transaction(function () use ($do, $isDeleting, $duringReconcile) {
            $triggerStatuses = ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'];
            $isActive = !$isDeleting && !$do->trashed() && in_array($do->status, $triggerStatuses, true);

            $items = $do->items()->get();
            $expectedOutByPart = [];
            if ($isActive && $do->warehouse_id) {
                foreach ($items as $item) {
                    $partId = (int) $item->part_id;
                    $qty = max(0, (int) $item->qty);
                    if ($partId > 0 && $qty > 0) {
                        $expectedOutByPart[$partId] = ($expectedOutByPart[$partId] ?? 0) + $qty;
                    }
                }
            }

            $existingLedgers = StockLedger::where('reference_id', $do->id)
                ->whereIn('reference_type', ['DELIVERY_ORDER', 'DELIVERY_ORDER_VOID'])
                ->get();

            $pairs = [];
            if ($do->warehouse_id) {
                foreach (array_keys($expectedOutByPart) as $partId) {
                    $pairs[$do->warehouse_id . ':' . $partId] = [(int) $do->warehouse_id, (int) $partId];
                }
            }
            foreach ($existingLedgers as $ledger) {
                $pairs[$ledger->warehouse_id . ':' . $ledger->part_id] = [(int) $ledger->warehouse_id, (int) $ledger->part_id];
            }

            $changes = 0;
            foreach ($pairs as [$warehouseId, $partId]) {
                $targetNet = ($isActive && $warehouseId === (int) $do->warehouse_id)
                    ? -($expectedOutByPart[$partId] ?? 0)
                    : 0;

                $currentNet = (int) StockLedger::where('warehouse_id', $warehouseId)
                    ->where('part_id', $partId)
                    ->where('reference_id', $do->id)
                    ->whereIn('reference_type', ['DELIVERY_ORDER', 'DELIVERY_ORDER_VOID'])
                    ->sum('qty_change');

                $delta = $targetNet - $currentNet;
                if ($delta === 0) {
                    continue;
                }

                $changes++;

                if ($delta < 0) {
                    $qtyToDeduct = abs($delta);
                    $note = 'Pengeluaran Surat Jalan: ' . $do->do_number . ' (' . $do->status . ')';

                    if (!$duringReconcile) {
                        $this->deductStock($warehouseId, $partId, $qtyToDeduct, 'DELIVERY_ORDER', $do->id, $do->do_number, $note);
                    } else {
                        $stock = StockBalance::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                            ['qty_on_hand' => 0, 'qty_reserved' => 0]
                        );
                        $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

                        $hasLaterOpname = $do->created_at && StockLedger::where('warehouse_id', $warehouseId)
                            ->where('part_id', $partId)
                            ->where('reference_type', 'STOCK_ADJUSTMENT')
                            ->where('created_at', '>', $do->created_at)
                            ->exists();

                        $before = (int) $stock->qty_on_hand;
                        if (!$hasLaterOpname && $stock->qty_on_hand >= $qtyToDeduct) {
                            $stock->qty_on_hand -= $qtyToDeduct;
                            $stock->save();
                        }

                        $ledger = new StockLedger([
                            'warehouse_id' => $warehouseId,
                            'part_id' => $partId,
                            'reference_type' => 'DELIVERY_ORDER',
                            'reference_id' => $do->id,
                            'reference_number' => $do->do_number,
                            'qty_change' => -$qtyToDeduct,
                            'balance_before' => $before,
                            'balance_after' => (int) $stock->qty_on_hand,
                            'notes' => $note,
                            'created_by' => $do->created_by ?? auth()->id() ?? 1,
                        ]);
                        if ($do->created_at) {
                            $ledger->created_at = $do->created_at;
                            $ledger->updated_at = $do->updated_at ?? $do->created_at;
                        }
                        $ledger->save();
                    }
                } else {
                    $qtyToAdd = $delta;
                    $note = $isActive
                        ? ('Koreksi Item Surat Jalan: ' . $do->do_number)
                        : (($isDeleting || $do->trashed())
                            ? ('Penghapusan Dokumen Surat Jalan: ' . $do->do_number . ' (Stok Dikembalikan)')
                            : ('Pembatalan Surat Jalan: ' . $do->do_number));

                    if (!$duringReconcile) {
                        $this->addStock($warehouseId, $partId, $qtyToAdd, 'DELIVERY_ORDER_VOID', $do->id, $do->do_number, $note);
                    } else {
                        $stock = StockBalance::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                            ['qty_on_hand' => 0, 'qty_reserved' => 0]
                        );
                        $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

                        $hasLaterOpname = $do->updated_at && StockLedger::where('warehouse_id', $warehouseId)
                            ->where('part_id', $partId)
                            ->where('reference_type', 'STOCK_ADJUSTMENT')
                            ->where('created_at', '>', $do->updated_at)
                            ->exists();

                        $before = (int) $stock->qty_on_hand;
                        if (!$hasLaterOpname) {
                            $stock->qty_on_hand += $qtyToAdd;
                            $stock->save();
                        }

                        StockLedger::create([
                            'warehouse_id' => $warehouseId,
                            'part_id' => $partId,
                            'reference_type' => 'DELIVERY_ORDER_VOID',
                            'reference_id' => $do->id,
                            'reference_number' => $do->do_number,
                            'qty_change' => $qtyToAdd,
                            'balance_before' => $before,
                            'balance_after' => (int) $stock->qty_on_hand,
                            'notes' => $note,
                            'created_by' => $do->created_by ?? auth()->id() ?? 1,
                        ]);
                    }
                }
            }

            return $changes;
        });
    }

    /**
     * Sinkronisasi penuh antara dokumen Penerimaan Barang (GoodsReceipt / LPB) beserta seluruh itemnya
     * terhadap StockBalance dan StockLedger secara idempoten.
     */
    public function syncGoodsReceiptStock(GoodsReceipt $gr, bool $isDeleting = false, bool $duringReconcile = false): int
    {
        return DB::transaction(function () use ($gr, $isDeleting, $duringReconcile) {
            $isActive = !$isDeleting && !$gr->trashed() && $gr->status === 'RECEIVED';

            $items = $gr->items()->get();
            $expectedInByPart = [];
            if ($isActive && $gr->warehouse_id) {
                foreach ($items as $item) {
                    $partId = (int) $item->part_id;
                    $qty = max(0, (int) $item->qty_received);
                    if ($partId > 0 && $qty > 0) {
                        $expectedInByPart[$partId] = ($expectedInByPart[$partId] ?? 0) + $qty;
                    }
                }
            }

            $existingLedgers = StockLedger::where('reference_id', $gr->id)
                ->whereIn('reference_type', ['GOODS_RECEIPT', 'GOODS_RECEIPT_VOID'])
                ->get();

            $pairs = [];
            if ($gr->warehouse_id) {
                foreach (array_keys($expectedInByPart) as $partId) {
                    $pairs[$gr->warehouse_id . ':' . $partId] = [(int) $gr->warehouse_id, (int) $partId];
                }
            }
            foreach ($existingLedgers as $ledger) {
                $pairs[$ledger->warehouse_id . ':' . $ledger->part_id] = [(int) $ledger->warehouse_id, (int) $ledger->part_id];
            }

            $changes = 0;
            foreach ($pairs as [$warehouseId, $partId]) {
                $targetNet = ($isActive && $warehouseId === (int) $gr->warehouse_id)
                    ? ($expectedInByPart[$partId] ?? 0)
                    : 0;

                $currentNet = (int) StockLedger::where('warehouse_id', $warehouseId)
                    ->where('part_id', $partId)
                    ->where('reference_id', $gr->id)
                    ->whereIn('reference_type', ['GOODS_RECEIPT', 'GOODS_RECEIPT_VOID'])
                    ->sum('qty_change');

                $delta = $targetNet - $currentNet;
                if ($delta === 0) {
                    continue;
                }

                $changes++;

                if ($delta > 0) {
                    $qtyToAdd = $delta;
                    $note = 'Penerimaan LPB: ' . $gr->receipt_number . ($gr->supplier_do_number ? ' (SJ: ' . $gr->supplier_do_number . ')' : '');

                    if (!$duringReconcile) {
                        $this->addStock($warehouseId, $partId, $qtyToAdd, 'GOODS_RECEIPT', $gr->id, $gr->receipt_number, $note);
                    } else {
                        $stock = StockBalance::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                            ['qty_on_hand' => 0, 'qty_reserved' => 0]
                        );
                        $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

                        $hasLaterOpname = $gr->created_at && StockLedger::where('warehouse_id', $warehouseId)
                            ->where('part_id', $partId)
                            ->where('reference_type', 'STOCK_ADJUSTMENT')
                            ->where('created_at', '>', $gr->created_at)
                            ->exists();

                        $before = (int) $stock->qty_on_hand;
                        if (!$hasLaterOpname) {
                            $stock->qty_on_hand += $qtyToAdd;
                            $stock->save();
                        }

                        $ledger = new StockLedger([
                            'warehouse_id' => $warehouseId,
                            'part_id' => $partId,
                            'reference_type' => 'GOODS_RECEIPT',
                            'reference_id' => $gr->id,
                            'reference_number' => $gr->receipt_number,
                            'qty_change' => $qtyToAdd,
                            'balance_before' => $before,
                            'balance_after' => (int) $stock->qty_on_hand,
                            'notes' => $note,
                            'created_by' => $gr->created_by ?? auth()->id() ?? 1,
                        ]);
                        if ($gr->created_at) {
                            $ledger->created_at = $gr->created_at;
                            $ledger->updated_at = $gr->updated_at ?? $gr->created_at;
                        }
                        $ledger->save();
                    }
                } else {
                    $qtyToDeduct = abs($delta);
                    $note = $isActive
                        ? ('Koreksi Item LPB: ' . $gr->receipt_number)
                        : (($isDeleting || $gr->trashed())
                            ? ('Penghapusan Dokumen LPB: ' . $gr->receipt_number . ' (Stok Ditarik Kembali)')
                            : ('Pembatalan Dokumen LPB: ' . $gr->receipt_number));

                    if (!$duringReconcile) {
                        $this->deductStock($warehouseId, $partId, $qtyToDeduct, 'GOODS_RECEIPT_VOID', $gr->id, $gr->receipt_number, $note);
                    } else {
                        $stock = StockBalance::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'part_id' => $partId],
                            ['qty_on_hand' => 0, 'qty_reserved' => 0]
                        );
                        $stock = StockBalance::where('id', $stock->id)->lockForUpdate()->first();

                        $hasLaterOpname = $gr->updated_at && StockLedger::where('warehouse_id', $warehouseId)
                            ->where('part_id', $partId)
                            ->where('reference_type', 'STOCK_ADJUSTMENT')
                            ->where('created_at', '>', $gr->updated_at)
                            ->exists();

                        $before = (int) $stock->qty_on_hand;
                        if (!$hasLaterOpname && $stock->qty_on_hand >= $qtyToDeduct) {
                            $stock->qty_on_hand -= $qtyToDeduct;
                            $stock->save();
                        }

                        StockLedger::create([
                            'warehouse_id' => $warehouseId,
                            'part_id' => $partId,
                            'reference_type' => 'GOODS_RECEIPT_VOID',
                            'reference_id' => $gr->id,
                            'reference_number' => $gr->receipt_number,
                            'qty_change' => -$qtyToDeduct,
                            'balance_before' => $before,
                            'balance_after' => (int) $stock->qty_on_hand,
                            'notes' => $note,
                            'created_by' => $gr->created_by ?? auth()->id() ?? 1,
                        ]);
                    }
                }
            }

            return $changes;
        });
    }

    /**
     * Audit dan rekonsiliasi integritas stok antara saldo fisik (StockBalance),
     * dokumen transaksi (DeliveryOrder & GoodsReceipt), lokasi gudang (Part.bin_location),
     * dan riwayat mutasi kartu stok (StockLedger).
     *
     * @return array{audited: int, discrepancies: int, details: array}
     */
    public function reconcileStock(): array
    {
        return DB::transaction(function () {
            $discrepancies = 0;
            $details = [];
            $repairedPairKeys = [];

            $recordDiscrepancy = function (int $warehouseId, int $partId, int $before, int $after) use (&$discrepancies, &$details, &$repairedPairKeys) {
                $key = "{$warehouseId}:{$partId}";
                if (isset($repairedPairKeys[$key])) {
                    // Update nilai akhir pada detail yang sudah tercatat
                    foreach ($details as &$d) {
                        if (($d['_key'] ?? '') === $key) {
                            $d['ledger_sum'] = $after;
                            break;
                        }
                    }
                    return;
                }
                $repairedPairKeys[$key] = true;
                $discrepancies++;
                $wh = Warehouse::withTrashed()->find($warehouseId);
                $part = Part::withTrashed()->find($partId);
                $details[] = [
                    '_key' => $key,
                    'warehouse' => $wh?->name ?? "Gudang #{$warehouseId}",
                    'part_number' => $part?->part_number ?? '-',
                    'part_name' => $part?->name ?? '-',
                    'balance_before' => $before,
                    'ledger_sum' => $after,
                ];
            };

            // 1. Bersihkan StockLedger yatim (part_id atau warehouse_id yang sudah tidak ada sama sekali di DB)
            $validPartIds = Part::withTrashed()->pluck('id')->toArray();
            $validWarehouseIds = Warehouse::withTrashed()->pluck('id')->toArray();

            if (!empty($validPartIds)) {
                StockLedger::whereNotIn('part_id', $validPartIds)->delete();
                StockBalance::whereNotIn('part_id', $validPartIds)->delete();
            }
            if (!empty($validWarehouseIds)) {
                StockLedger::whereNotIn('warehouse_id', $validWarehouseIds)->delete();
                StockBalance::whereNotIn('warehouse_id', $validWarehouseIds)->delete();
            }

            // 2. Pastikan seluruh produk yang sudah dihapus (soft-deleted) memiliki saldo fisik 0
            $trashedPartIds = Part::onlyTrashed()->pluck('id')->toArray();
            if (!empty($trashedPartIds)) {
                $trashedBalances = StockBalance::whereIn('part_id', $trashedPartIds)->where('qty_on_hand', '!=', 0)->get();
                foreach ($trashedBalances as $tb) {
                    $oldQty = (int) $tb->qty_on_hand;
                    $tb->qty_on_hand = 0;
                    $tb->save();
                    $recordDiscrepancy($tb->warehouse_id, $tb->part_id, $oldQty, 0);
                }
            }

            // 3. Sinkronkan seluruh dokumen Penerimaan Barang (GoodsReceipt) dan Surat Jalan (DeliveryOrder)
            $allGoodsReceipts = GoodsReceipt::withTrashed()->orderBy('id')->get();
            foreach ($allGoodsReceipts as $gr) {
                $changes = $this->syncGoodsReceiptStock($gr, false, true);
                if ($changes > 0 && $gr->warehouse_id) {
                    foreach ($gr->items as $item) {
                        $bal = StockBalance::where('warehouse_id', $gr->warehouse_id)->where('part_id', $item->part_id)->first();
                        $recordDiscrepancy($gr->warehouse_id, $item->part_id, (int) ($bal?->qty_on_hand ?? 0), (int) ($bal?->qty_on_hand ?? 0));
                    }
                }
            }

            $allDeliveryOrders = DeliveryOrder::withTrashed()->orderBy('id')->get();
            foreach ($allDeliveryOrders as $do) {
                $changes = $this->syncDeliveryOrderStock($do, false, true);
                if ($changes > 0 && $do->warehouse_id) {
                    foreach ($do->items as $item) {
                        $bal = StockBalance::where('warehouse_id', $do->warehouse_id)->where('part_id', $item->part_id)->first();
                        $recordDiscrepancy($do->warehouse_id, $item->part_id, (int) ($bal?->qty_on_hand ?? 0), (int) ($bal?->qty_on_hand ?? 0));
                    }
                }
            }

            // 4. Perbaiki anomali mutasi pindah gudang asimetris (TRF/) dari versi sebelum commit 389d80a
            $gma = Warehouse::where('code', 'GMA')->first() ?? Warehouse::where('name', 'like', '%MITRA ABADI%')->first();
            $gar = Warehouse::where('code', 'GAR')->first() ?? Warehouse::where('name', 'like', '%ANEKARASA%')->first();

            $activeParts = Part::all();
            foreach ($activeParts as $part) {
                // Pastikan setiap produk aktif memiliki minimal 1 record StockBalance pada gudang lokasinya
                if ($gma && $gar && filled($part->bin_location)) {
                    $isAnekarasa = str_contains(strtoupper($part->bin_location), 'ANEKARASA');
                    $targetWh = $isAnekarasa ? $gar : $gma;
                    $otherWh = $isAnekarasa ? $gma : $gar;

                    StockBalance::firstOrCreate(
                        ['warehouse_id' => $targetWh->id, 'part_id' => $part->id],
                        ['qty_on_hand' => 0, 'qty_reserved' => 0]
                    );

                    // Cek apakah ada TRF/ keluar di otherWh yang menyebabkan saldo ledger otherWh negatif
                    $otherLedgerSum = (int) StockLedger::where('warehouse_id', $otherWh->id)->where('part_id', $part->id)->sum('qty_change');
                    $targetLedgerSum = (int) StockLedger::where('warehouse_id', $targetWh->id)->where('part_id', $part->id)->sum('qty_change');
                    $otherBal = StockBalance::where('warehouse_id', $otherWh->id)->where('part_id', $part->id)->first();
                    $targetBal = StockBalance::where('warehouse_id', $targetWh->id)->where('part_id', $part->id)->first();

                    if ($otherLedgerSum < 0) {
                        $negativeTrf = StockLedger::where('warehouse_id', $otherWh->id)
                            ->where('part_id', $part->id)
                            ->where('reference_type', 'MANUAL_ADJUSTMENT')
                            ->where('reference_number', 'like', 'TRF/%')
                            ->where('qty_change', '<', 0)
                            ->orderBy('id')
                            ->first();

                        if ($negativeTrf) {
                            $missingQty = abs($otherLedgerSum);
                            $timestamp = $negativeTrf->created_at ? $negativeTrf->created_at->copy()->subSecond() : now()->subMinutes(5);

                            // Catat mutasi keluar dari targetWh dan masuk ke otherWh yang dulu belum tercatat
                            $outLedger = new StockLedger([
                                'warehouse_id' => $targetWh->id,
                                'part_id' => $part->id,
                                'reference_type' => 'MANUAL_ADJUSTMENT',
                                'reference_id' => $part->id,
                                'reference_number' => 'TRF/SYNC/' . $part->id,
                                'qty_change' => -$missingQty,
                                'balance_before' => $missingQty,
                                'balance_after' => 0,
                                'notes' => 'Sinkronisasi riwayat pemindahan barang ke ' . $otherWh->name,
                                'created_by' => $negativeTrf->created_by ?? 1,
                            ]);
                            $outLedger->created_at = $timestamp;
                            $outLedger->updated_at = $timestamp;
                            $outLedger->save();

                            $inLedger = new StockLedger([
                                'warehouse_id' => $otherWh->id,
                                'part_id' => $part->id,
                                'reference_type' => 'MANUAL_ADJUSTMENT',
                                'reference_id' => $part->id,
                                'reference_number' => 'TRF/SYNC/' . $part->id,
                                'qty_change' => $missingQty,
                                'balance_before' => 0,
                                'balance_after' => $missingQty,
                                'notes' => 'Sinkronisasi riwayat penerimaan barang di ' . $otherWh->name,
                                'created_by' => $negativeTrf->created_by ?? 1,
                            ]);
                            $inLedger->created_at = $timestamp;
                            $inLedger->updated_at = $timestamp;
                            $inLedger->save();

                            // Jika targetBal sempat tergandakan oleh rekonsiliasi lama, kembalikan ke nilai ledger yang benar
                            $newTargetLedgerSum = (int) StockLedger::where('warehouse_id', $targetWh->id)->where('part_id', $part->id)->sum('qty_change');
                            if ($targetBal && $targetBal->qty_on_hand !== $newTargetLedgerSum && $newTargetLedgerSum >= 0) {
                                $oldTargetQty = (int) $targetBal->qty_on_hand;
                                $targetBal->qty_on_hand = $newTargetLedgerSum;
                                $targetBal->save();
                                $recordDiscrepancy($targetWh->id, $part->id, $oldTargetQty, $newTargetLedgerSum);
                            } else {
                                $recordDiscrepancy($otherWh->id, $part->id, 0, 0);
                            }
                        }
                    }

                    // Cek jika produk berlokasi di targetWh (misal Anekarasa), tapi riwayat ledger masih tertinggal di otherWh (Mitra Abadi)
                    $otherLedgerSum = (int) StockLedger::where('warehouse_id', $otherWh->id)->where('part_id', $part->id)->sum('qty_change');
                    $targetLedgerSum = (int) StockLedger::where('warehouse_id', $targetWh->id)->where('part_id', $part->id)->sum('qty_change');
                    $otherQty = (int) ($otherBal?->qty_on_hand ?? 0);
                    $targetQty = (int) ($targetBal?->qty_on_hand ?? 0);

                    if ($otherQty === 0 && $otherLedgerSum > 0 && $targetQty === $otherLedgerSum && $targetLedgerSum === 0) {
                        // Pindahan gudang dilakukan sebelum commit 389d80a: fisik sudah di targetWh, ledger masih di otherWh
                        StockLedger::create([
                            'warehouse_id' => $otherWh->id,
                            'part_id' => $part->id,
                            'reference_type' => 'MANUAL_ADJUSTMENT',
                            'reference_id' => $part->id,
                            'reference_number' => 'TRF/SYNC/' . $part->id,
                            'qty_change' => -$otherLedgerSum,
                            'balance_before' => $otherLedgerSum,
                            'balance_after' => 0,
                            'notes' => 'Sinkronisasi pemindahan fisik barang ke ' . $targetWh->name,
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        StockLedger::create([
                            'warehouse_id' => $targetWh->id,
                            'part_id' => $part->id,
                            'reference_type' => 'MANUAL_ADJUSTMENT',
                            'reference_id' => $part->id,
                            'reference_number' => 'TRF/SYNC/' . $part->id,
                            'qty_change' => $otherLedgerSum,
                            'balance_before' => 0,
                            'balance_after' => $otherLedgerSum,
                            'notes' => 'Sinkronisasi penerimaan fisik barang di ' . $targetWh->name,
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        $recordDiscrepancy($targetWh->id, $part->id, $targetQty, $targetQty);
                    } elseif ($otherQty > 0 && $otherLedgerSum === $otherQty && $targetQty === 0 && $targetLedgerSum === 0) {
                        // Produk memiliki bin_location = targetWh, tetapi saldo fisik & ledger masih di otherWh
                        if ($otherBal) {
                            $otherBal->qty_on_hand = 0;
                            $otherBal->save();
                        }
                        if ($targetBal) {
                            $targetBal->qty_on_hand = $otherQty;
                            $targetBal->save();
                        }

                        StockLedger::create([
                            'warehouse_id' => $otherWh->id,
                            'part_id' => $part->id,
                            'reference_type' => 'MANUAL_ADJUSTMENT',
                            'reference_id' => $part->id,
                            'reference_number' => 'TRF/SYNC/' . $part->id,
                            'qty_change' => -$otherQty,
                            'balance_before' => $otherQty,
                            'balance_after' => 0,
                            'notes' => 'Sinkronisasi lokasi fisik barang ke ' . $targetWh->name,
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        StockLedger::create([
                            'warehouse_id' => $targetWh->id,
                            'part_id' => $part->id,
                            'reference_type' => 'MANUAL_ADJUSTMENT',
                            'reference_id' => $part->id,
                            'reference_number' => 'TRF/SYNC/' . $part->id,
                            'qty_change' => $otherQty,
                            'balance_before' => 0,
                            'balance_after' => $otherQty,
                            'notes' => 'Sinkronisasi penerimaan fisik barang di ' . $targetWh->name,
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        $recordDiscrepancy($targetWh->id, $part->id, 0, $otherQty);
                    }
                }
            }

            // 5. Pastikan setiap pasangan (warehouse_id, part_id) yang ada di StockLedger memiliki record StockBalance
            $ledgerPairs = StockLedger::select('warehouse_id', 'part_id')->distinct()->get();
            foreach ($ledgerPairs as $lp) {
                StockBalance::firstOrCreate(
                    ['warehouse_id' => $lp->warehouse_id, 'part_id' => $lp->part_id],
                    ['qty_on_hand' => 0, 'qty_reserved' => 0]
                );
            }

            // 6. Rekonsiliasi setiap StockBalance dengan akumulasi StockLedger
            $balances = StockBalance::with(['part' => fn ($q) => $q->withTrashed(), 'warehouse'])->get();

            foreach ($balances as $balance) {
                $ledgerCount = StockLedger::where('warehouse_id', $balance->warehouse_id)
                    ->where('part_id', $balance->part_id)
                    ->count();

                $ledgerSum = (int) StockLedger::where('warehouse_id', $balance->warehouse_id)
                    ->where('part_id', $balance->part_id)
                    ->sum('qty_change');

                $hasPositiveBaseline = StockLedger::where('warehouse_id', $balance->warehouse_id)
                    ->where('part_id', $balance->part_id)
                    ->whereIn('reference_type', ['INITIAL_STOCK', 'STOCK_ADJUSTMENT', 'GOODS_RECEIPT', 'MANUAL_ADJUSTMENT'])
                    ->where('qty_change', '>', 0)
                    ->exists();

                if ((int) $balance->qty_on_hand !== $ledgerSum) {
                    $beforeQty = (int) $balance->qty_on_hand;

                    if ($balance->part && !$balance->part->trashed() && $beforeQty > 0 && ($ledgerCount === 0 || !$hasPositiveBaseline || $ledgerSum <= 0)) {
                        // Produk aktif di website memiliki stok fisik, namun belum memiliki catatan saldo awal di StockLedger
                        $neededInitial = $beforeQty - $ledgerSum;
                        $earliest = StockLedger::where('warehouse_id', $balance->warehouse_id)
                            ->where('part_id', $balance->part_id)
                            ->orderBy('created_at')
                            ->orderBy('id')
                            ->first();

                        $initLedger = new StockLedger([
                            'warehouse_id' => $balance->warehouse_id,
                            'part_id' => $balance->part_id,
                            'reference_type' => 'INITIAL_STOCK',
                            'reference_id' => $balance->part_id,
                            'reference_number' => 'INIT/' . ($balance->part->part_number ?? $balance->part_id),
                            'qty_change' => $neededInitial,
                            'balance_before' => 0,
                            'balance_after' => $neededInitial,
                            'notes' => 'Sinkronisasi Saldo Awal Stok Fisik Gudang',
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        if ($earliest && $earliest->created_at) {
                            $initLedger->created_at = $earliest->created_at->copy()->subSecond();
                            $initLedger->updated_at = $earliest->created_at->copy()->subSecond();
                        }
                        $initLedger->save();

                        $recordDiscrepancy($balance->warehouse_id, $balance->part_id, $beforeQty, $beforeQty);
                    } elseif ($beforeQty === 0 && $ledgerSum < 0) {
                        // Gudang kosong tetapi akumulasi ledger negatif karena saldo awal belum tercatat
                        $neededInitial = abs($ledgerSum);
                        $earliest = StockLedger::where('warehouse_id', $balance->warehouse_id)
                            ->where('part_id', $balance->part_id)
                            ->orderBy('created_at')
                            ->orderBy('id')
                            ->first();

                        $initLedger = new StockLedger([
                            'warehouse_id' => $balance->warehouse_id,
                            'part_id' => $balance->part_id,
                            'reference_type' => 'INITIAL_STOCK',
                            'reference_id' => $balance->part_id,
                            'reference_number' => 'INIT/' . ($balance->part?->part_number ?? $balance->part_id),
                            'qty_change' => $neededInitial,
                            'balance_before' => 0,
                            'balance_after' => $neededInitial,
                            'notes' => 'Sinkronisasi Saldo Awal Sebelum Mutasi Keluar',
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        if ($earliest && $earliest->created_at) {
                            $initLedger->created_at = $earliest->created_at->copy()->subSecond();
                            $initLedger->updated_at = $earliest->created_at->copy()->subSecond();
                        }
                        $initLedger->save();

                        $recordDiscrepancy($balance->warehouse_id, $balance->part_id, 0, 0);
                    } else {
                        // Sesuaikan balance fisik agar selaras dengan akumulasi buku mutasi
                        $targetQty = ($balance->part && $balance->part->trashed()) ? 0 : max(0, $ledgerSum);
                        $balance->qty_on_hand = $targetQty;
                        $balance->save();

                        if ($targetQty === 0 && $ledgerSum !== 0) {
                            StockLedger::create([
                                'warehouse_id' => $balance->warehouse_id,
                                'part_id' => $balance->part_id,
                                'reference_type' => 'STOCK_ADJUSTMENT',
                                'reference_id' => $balance->id,
                                'reference_number' => 'SYNC/' . date('Ymd') . '/' . $balance->part_id,
                                'qty_change' => -$ledgerSum,
                                'balance_before' => $ledgerSum,
                                'balance_after' => 0,
                                'notes' => 'Penyesuaian otomatis produk non-aktif/terhapus',
                                'created_by' => auth()->id() ?? 1,
                            ]);
                        }

                        $recordDiscrepancy($balance->warehouse_id, $balance->part_id, $beforeQty, $targetQty);
                    }
                }
            }

            // 7. Hitung ulang rantai balance_before dan balance_after secara kronologis di seluruh StockLedger
            $allPairs = StockLedger::select('warehouse_id', 'part_id')->distinct()->get();
            foreach ($allPairs as $pair) {
                $rows = StockLedger::where('warehouse_id', $pair->warehouse_id)
                    ->where('part_id', $pair->part_id)
                    ->orderByRaw("CASE WHEN reference_type = 'INITIAL_STOCK' THEN 0 ELSE 1 END ASC")
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $running = 0;
                $chainFixed = false;
                foreach ($rows as $row) {
                    $expectedBefore = $running;
                    $expectedAfter = $running + (int) $row->qty_change;

                    if ((int) $row->balance_before !== $expectedBefore || (int) $row->balance_after !== $expectedAfter) {
                        $row->balance_before = $expectedBefore;
                        $row->balance_after = $expectedAfter;
                        $row->saveQuietly();
                        $chainFixed = true;
                    }

                    $running = $expectedAfter;
                }

                if ($chainFixed) {
                    $recordDiscrepancy($pair->warehouse_id, $pair->part_id, $running, $running);
                }
            }

            // Bersihkan field internal _key sebelum return
            foreach ($details as &$d) {
                unset($d['_key']);
            }

            return [
                'audited' => $balances->count(),
                'discrepancies' => $discrepancies,
                'details' => $details,
            ];
        });
    }
}
