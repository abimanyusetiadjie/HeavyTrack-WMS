<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('print.delivery-order', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
