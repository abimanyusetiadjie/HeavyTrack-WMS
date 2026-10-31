<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        // Validasi RBAC Backend: Jika bukan Owner, modal HPP otomatis 0
        if (! $user || ! $user->isOwner()) {
            $data['cost_price'] = 0;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $initialQty = (int) ($this->data['initial_stock'] ?? 0);
        if ($initialQty > 0) {
            $primaryWarehouse = \App\Models\Warehouse::where('is_active', true)->first();
            if ($primaryWarehouse) {
                \App\Models\StockBalance::updateOrCreate(
                    ['warehouse_id' => $primaryWarehouse->id, 'part_id' => $this->record->id],
                    ['qty_on_hand' => $initialQty, 'qty_reserved' => 0]
                );

                \App\Models\StockLedger::create([
                    'part_id' => $this->record->id,
                    'warehouse_id' => $primaryWarehouse->id,
                    'reference_type' => 'STOCK_ADJUSTMENT',
                    'reference_id' => $this->record->id,
                    'reference_number' => 'OPNAME/' . $this->record->part_number,
                    'qty_change' => $initialQty,
                    'balance_before' => 0,
                    'balance_after' => $initialQty,
                    'notes' => 'Saldo awal saat input produk baru',
                    'created_by' => auth()->id() ?? 1,
                ]);
            }
        }
    }
}
