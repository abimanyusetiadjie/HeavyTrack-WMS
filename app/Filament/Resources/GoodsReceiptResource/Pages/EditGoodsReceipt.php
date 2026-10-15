<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak Bukti LPB')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('print.goods-receipt', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
