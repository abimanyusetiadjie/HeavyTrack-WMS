<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGoodsReceipts extends ListRecords
{
    protected static string $resource = GoodsReceiptResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Penerimaan'),
            'received' => Tab::make('Diterima')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'RECEIVED'))
                ->badge(fn () => GoodsReceipt::where('status', 'RECEIVED')->count())
                ->badgeColor('success'),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'DRAFT'))
                ->badge(fn () => GoodsReceipt::where('status', 'DRAFT')->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Catat Barang Masuk'),
        ];
    }
}
