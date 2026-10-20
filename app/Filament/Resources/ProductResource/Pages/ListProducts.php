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

    public ?string $filterCategory = '';
    public bool $filterLowStock = false;

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function toggleLowStock(): void
    {
        $this->filterLowStock = ! $this->filterLowStock;
        $this->resetPage();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Produk'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->when($this->filterCategory, fn ($query) => $query->where('category_id', $this->filterCategory))
            ->when($this->filterLowStock, fn ($query) => $query->whereRaw(
                '(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) < 100'
            ));
    }
}
