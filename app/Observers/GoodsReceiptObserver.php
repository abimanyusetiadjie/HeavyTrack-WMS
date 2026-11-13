<?php

namespace App\Observers;

use App\Models\GoodsReceipt;
use App\Services\InventoryService;

class GoodsReceiptObserver
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function updated(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->wasChanged('status') || $goodsReceipt->wasChanged('warehouse_id')) {
            $this->inventoryService->syncGoodsReceiptStock($goodsReceipt);
        }
    }

    public function deleted(GoodsReceipt $goodsReceipt): void
    {
        // Cegah pengurangan stok ganda jika record trashed di-force delete secara permanen
        if ($goodsReceipt->isForceDeleting() && $goodsReceipt->trashed()) {
            return;
        }

        $this->inventoryService->syncGoodsReceiptStock($goodsReceipt, true);
    }

    public function restored(GoodsReceipt $goodsReceipt): void
    {
        $this->inventoryService->syncGoodsReceiptStock($goodsReceipt);
    }

    public function handleStatusChange(GoodsReceipt $gr, ?string $oldStatus, string $newStatus): void
    {
        $this->inventoryService->syncGoodsReceiptStock($gr);
    }
}
