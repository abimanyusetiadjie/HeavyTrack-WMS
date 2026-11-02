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
        $part = $this->record;
        $loc = $part->bin_location;
        if (! empty($loc)) {
            $isAnekarasa = str_contains(strtoupper($loc), 'ANEKARASA');
            $targetWarehouse = \App\Models\Warehouse::where('code', $isAnekarasa ? 'GAR' : 'GMA')->first()
                ?? \App\Models\Warehouse::where('name', 'like', $isAnekarasa ? '%ANEKARASA%' : '%MITRA ABADI%')->first();

            if ($targetWarehouse) {
                \App\Models\StockBalance::firstOrCreate(
                    ['part_id' => $part->id, 'warehouse_id' => $targetWarehouse->id],
                    ['qty_on_hand' => 0, 'qty_reserved' => 0]
                );
            }
        }
    }
}
