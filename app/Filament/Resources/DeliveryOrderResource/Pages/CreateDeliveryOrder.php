<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;
}
