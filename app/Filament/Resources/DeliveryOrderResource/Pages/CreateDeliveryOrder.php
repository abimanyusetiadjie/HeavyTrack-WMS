<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Observers\DeliveryOrderObserver;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function afterCreate(): void
    {
        if (in_array($this->record->status, ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'], true)) {
            app(DeliveryOrderObserver::class)->handleStatusChange($this->record, null, $this->record->status);
        }
    }
}
