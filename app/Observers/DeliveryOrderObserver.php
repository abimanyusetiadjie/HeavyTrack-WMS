<?php

namespace App\Observers;

use App\Models\DeliveryOrder;
use App\Services\InventoryService;

class DeliveryOrderObserver
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function updated(DeliveryOrder $deliveryOrder): void
    {
        if ($deliveryOrder->wasChanged('status') || $deliveryOrder->wasChanged('warehouse_id')) {
            $this->inventoryService->syncDeliveryOrderStock($deliveryOrder);
        }
    }

    public function deleted(DeliveryOrder $deliveryOrder): void
    {
        // Cegah pengembalian stok ganda jika record yang sudah trashed di-force delete secara permanen
        if ($deliveryOrder->isForceDeleting() && $deliveryOrder->trashed()) {
            return;
        }

        $this->inventoryService->syncDeliveryOrderStock($deliveryOrder, true);
    }

    public function restored(DeliveryOrder $deliveryOrder): void
    {
        $this->inventoryService->syncDeliveryOrderStock($deliveryOrder);
    }

    public function handleStatusChange(DeliveryOrder $do, ?string $oldStatus, string $newStatus): void
    {
        $this->inventoryService->syncDeliveryOrderStock($do);
    }
}
