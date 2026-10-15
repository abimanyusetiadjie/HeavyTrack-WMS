<?php

namespace App\Observers;

use App\Models\GoodsReceipt;
use App\Services\InventoryService;
use Exception;

class GoodsReceiptObserver
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function updated(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->wasChanged('status')) {
            $this->handleStatusChange($goodsReceipt, $goodsReceipt->getOriginal('status'), $goodsReceipt->status);
        }
    }

    public function handleStatusChange(GoodsReceipt $gr, ?string $oldStatus, string $newStatus)
    {
        // Transisi dari DRAFT ke RECEIVED -> Tambah stok
        if ($oldStatus !== 'RECEIVED' && $newStatus === 'RECEIVED') {
            $gr->loadMissing('items');
            foreach ($gr->items as $item) {
                $this->inventoryService->addStock(
                    $gr->warehouse_id,
                    $item->part_id,
                    $item->qty_received,
                    'GOODS_RECEIPT',
                    $gr->id,
                    $gr->receipt_number,
                    'Penerimaan LPB: ' . $gr->receipt_number . ($gr->supplier_do_number ? ' (SJ: ' . $gr->supplier_do_number . ')' : '')
                );
            }
        }

        // Transisi dari RECEIVED ke VOID -> Batalkan penambahan stok (kurangi kembali)
        if ($oldStatus === 'RECEIVED' && $newStatus === 'VOID') {
            $gr->loadMissing('items');
            foreach ($gr->items as $item) {
                $this->inventoryService->deductStock(
                    $gr->warehouse_id,
                    $item->part_id,
                    $item->qty_received,
                    'GOODS_RECEIPT_VOID',
                    $gr->id,
                    $gr->receipt_number,
                    'Pembatalan Dokumen LPB: ' . $gr->receipt_number
                );
            }
        }
    }
}
