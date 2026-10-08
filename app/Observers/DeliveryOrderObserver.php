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

    public function created(DeliveryOrder $deliveryOrder): void
    {
        $this->handleStatusChange($deliveryOrder, null, $deliveryOrder->status);
    }

    public function updated(DeliveryOrder $deliveryOrder): void
    {
        if ($deliveryOrder->wasChanged('status')) {
            $this->handleStatusChange($deliveryOrder, $deliveryOrder->getOriginal('status'), $deliveryOrder->status);
        }
    }

    protected function handleStatusChange(DeliveryOrder $do, ?string $oldStatus, string $newStatus)
    {
        $triggerStatuses = ['CONFIRMED', 'SHIPPED'];
        
        // If it was already confirmed/shipped, we don't deduct again.
        $wasAlreadyTriggered = in_array($oldStatus, $triggerStatuses);
        $isNowTriggered = in_array($newStatus, $triggerStatuses);
        
        if (!$wasAlreadyTriggered && $isNowTriggered) {
            // Needs deduction
            try {
                // Ensure items are loaded
                $do->loadMissing('items');
                
                foreach ($do->items as $item) {
                    $this->inventoryService->deductStock(
                        $do->warehouse_id,
                        $item->part_id,
                        $item->qty,
                        'DELIVERY_ORDER',
                        $do->id,
                        $do->do_number,
                        'Auto deduct on DO ' . $newStatus
                    );
                }
            } catch (Exception $e) {
                // To prevent the save if stock is insufficient, we can throw the exception to abort transaction
                // Filament will catch this if thrown inside a database transaction, but Observer `updated` runs after save
                // Wait! If we throw here in `updated`, the DB transaction from Filament's save will rollback if it's inside one.
                // Filament wraps save in DB::transaction.
                throw $e;
            }
        }
    }
}
