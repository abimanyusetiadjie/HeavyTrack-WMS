<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        // Logika Mengubah Data (Write / Update Protection)
        // Jika bukan Owner, modal/HPP selalu mengabaikan input form dan mengunci nilai modal lama dari database
        if (! $user || ! $user->isOwner()) {
            $data['cost_price'] = $this->record->getRawOriginal('cost_price') ?? 0;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $part = $this->record;
        $loc = $part->bin_location;
        if (! empty($loc)) {
            $isAnekarasa = str_contains(strtoupper($loc), 'ANEKARASA');
            $targetWarehouse = \App\Models\Warehouse::where('code', $isAnekarasa ? 'GAR' : 'GMA')->first()
                ?? \App\Models\Warehouse::where('name', 'like', $isAnekarasa ? '%ANEKARASA%' : '%MITRA ABADI%')->first();

            if ($targetWarehouse) {
                $otherBalances = $part->stockBalances()->where('warehouse_id', '!=', $targetWarehouse->id)->get();
                $totalOtherStock = $otherBalances->sum('qty_on_hand');

                if ($totalOtherStock > 0 && $part->stockBalances()->where('warehouse_id', $targetWarehouse->id)->count() === 0) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($part, $targetWarehouse, $otherBalances, $totalOtherStock) {
                        foreach ($otherBalances as $ob) {
                            $ob->update(['qty_on_hand' => 0]);
                        }
                        $targetBalance = \App\Models\StockBalance::firstOrCreate(
                            ['part_id' => $part->id, 'warehouse_id' => $targetWarehouse->id],
                            ['qty_on_hand' => 0, 'qty_reserved' => 0]
                        );
                        $targetBalance->update(['qty_on_hand' => $totalOtherStock]);
                    });
                } elseif ($part->stockBalances()->count() === 0) {
                    \App\Models\StockBalance::firstOrCreate(
                        ['part_id' => $part->id, 'warehouse_id' => $targetWarehouse->id],
                        ['qty_on_hand' => 0, 'qty_reserved' => 0]
                    );
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
