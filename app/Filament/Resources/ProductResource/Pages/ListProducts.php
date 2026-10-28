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

    public function getCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Category::orderBy('name')->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Produk'),
            Actions\Action::make('deleteProduct')
                ->label('Hapus Produk')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Select::make('part_id')
                        ->label('Pilih Produk yang Ingin Dihapus')
                        ->options(fn () => Part::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Hapus Produk')
                ->modalDescription('Apakah Anda yakin ingin menghapus data produk ini?')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->action(function (array $data) {
                    $part = Part::find($data['part_id']);
                    if ($part) {
                        if ($part->stockBalances()->where('qty_on_hand', '>', 0)->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Produk Tidak Dapat Dihapus')
                                ->body("Produk '{$part->name}' masih memiliki saldo stok fisik di gudang. Harap kosongkan stok terlebih dahulu sebelum menghapus.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $part->delete();
                        \Filament\Notifications\Notification::make()
                            ->title('Produk Berhasil Dihapus')
                            ->success()
                            ->send();
                    }
                }),
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
