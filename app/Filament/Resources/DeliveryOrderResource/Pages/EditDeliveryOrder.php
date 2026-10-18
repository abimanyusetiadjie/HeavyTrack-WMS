<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printInvoice')
                ->label(fn (): string => $this->record->invoice ? 'Cetak Faktur' : 'Terbitkan & Cetak Faktur')
                ->icon('heroicon-o-document-currency-dollar')
                ->color(fn (): string => $this->record->invoice ? 'success' : 'warning')
                ->url(fn (): string => route('print.delivery-order.invoice', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('print')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('print.delivery-order', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
