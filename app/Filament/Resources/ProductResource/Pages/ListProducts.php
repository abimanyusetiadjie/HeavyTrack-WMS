<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Part;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Produk'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Produk'),
            'warning_stock' => Tab::make('⚠️ Stok < 100')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw(
                    '(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) < 100'
                ))
                ->badge(fn () => Part::whereRaw(
                    '(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) < 100'
                )->count())
                ->badgeColor('danger'),
            'out_of_stock' => Tab::make('❌ Habis (0)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw(
                    '(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= 0'
                ))
                ->badge(fn () => Part::whereRaw(
                    '(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= 0'
                )->count())
                ->badgeColor('gray'),
        ];
    }
}
