<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use App\Observers\GoodsReceiptObserver;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function afterCreate(): void
    {
        if ($this->record->status === 'RECEIVED') {
            app(GoodsReceiptObserver::class)->handleStatusChange($this->record, null, 'RECEIVED');
        }
    }
}
