<?php

namespace App\Observers;

use App\Models\DeliveryOrder;
use App\Services\InventoryService;
use Exception;
use Filament\Notifications\Notification;

class DeliveryOrderObserver
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function updated(DeliveryOrder $deliveryOrder): void
    {
        if ($deliveryOrder->wasChanged('status')) {
            $this->handleStatusChange($deliveryOrder, $deliveryOrder->getOriginal('status'), $deliveryOrder->status);
        }
    }

    public function deleted(DeliveryOrder $deliveryOrder): void
    {
        $triggerStatuses = ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'];
        if (in_array($deliveryOrder->status, $triggerStatuses, true)) {
            $deliveryOrder->loadMissing('items');
            foreach ($deliveryOrder->items as $item) {
                $this->inventoryService->addStock(
                    $deliveryOrder->warehouse_id,
                    $item->part_id,
                    $item->qty,
                    'DELIVERY_ORDER_VOID',
                    $deliveryOrder->id,
                    $deliveryOrder->do_number,
                    'Penghapusan Dokumen Surat Jalan: ' . $deliveryOrder->do_number . ' (Stok Dikembalikan)'
                );
            }
        }
    }

    public function restored(DeliveryOrder $deliveryOrder): void
    {
        $triggerStatuses = ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'];
        if (in_array($deliveryOrder->status, $triggerStatuses, true)) {
            $deliveryOrder->loadMissing('items');
            foreach ($deliveryOrder->items as $item) {
                $this->inventoryService->deductStock(
                    $deliveryOrder->warehouse_id,
                    $item->part_id,
                    $item->qty,
                    'DELIVERY_ORDER',
                    $deliveryOrder->id,
                    $deliveryOrder->do_number,
                    'Pemulihan Surat Jalan: ' . $deliveryOrder->do_number . ' (Stok Dipotong Kembali)'
                );
            }
        }
    }

    public function handleStatusChange(DeliveryOrder $do, ?string $oldStatus, string $newStatus): void
    {
        $triggerStatuses = ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'];
        
        $wasAlreadyTriggered = in_array($oldStatus, $triggerStatuses, true);
        $isNowTriggered = in_array($newStatus, $triggerStatuses, true);
        
        // Transisi dari DRAFT/null ke status aktif -> Potong stok gudang
        if (!$wasAlreadyTriggered && $isNowTriggered) {
            $do->load('items');
            
            foreach ($do->items as $item) {
                $this->inventoryService->deductStock(
                    $do->warehouse_id,
                    $item->part_id,
                    $item->qty,
                    'DELIVERY_ORDER',
                    $do->id,
                    $do->do_number,
                    'Pengeluaran Surat Jalan: ' . $do->do_number . ' (' . $newStatus . ')'
                );
            }
        }

        // Transisi dari status aktif ke VOID (Dibatalkan) -> Kembalikan stok ke gudang
        if ($wasAlreadyTriggered && $newStatus === 'VOID') {
            $do->load('items');
            
            foreach ($do->items as $item) {
                $this->inventoryService->addStock(
                    $do->warehouse_id,
                    $item->part_id,
                    $item->qty,
                    'DELIVERY_ORDER_VOID',
                    $do->id,
                    $do->do_number,
                    'Pembatalan Surat Jalan: ' . $do->do_number
                );
            }
        }
    }
}
