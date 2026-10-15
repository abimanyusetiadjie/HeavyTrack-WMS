<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printFaktur')->label('Cetak Faktur (PDF)')->icon('heroicon-o-printer')->url(fn (): string => route('print.invoice', $this->record))->openUrlInNewTab(),
            Actions\Action::make('printKwitansi')->label('Cetak Kwitansi')->icon('heroicon-o-currency-dollar')->url(fn (): string => route('print.receipt', $this->record))->openUrlInNewTab()->visible(fn (): bool => $this->record->paid_amount > 0),
            Actions\DeleteAction::make(),
        ];
    }
}
