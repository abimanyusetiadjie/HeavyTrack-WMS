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
                \Illuminate\Support\Facades\DB::transaction(function () use ($part, $targetWarehouse) {
                    $otherBalances = $part->stockBalances()->where('warehouse_id', '!=', $targetWarehouse->id)->get();
                    $totalOtherStock = $otherBalances->sum('qty_on_hand');

                    $targetBalance = \App\Models\StockBalance::firstOrCreate(
                        ['part_id' => $part->id, 'warehouse_id' => $targetWarehouse->id],
                        ['qty_on_hand' => 0, 'qty_reserved' => 0]
                    );

                    if ($totalOtherStock > 0) {
                        $targetBefore = $targetBalance->qty_on_hand;
                        $targetAfter = $targetBefore + $totalOtherStock;

                        foreach ($otherBalances as $ob) {
                            if ($ob->qty_on_hand > 0) {
                                \App\Models\StockLedger::create([
                                    'warehouse_id' => $ob->warehouse_id,
                                    'part_id' => $part->id,
                                    'reference_type' => 'MANUAL_ADJUSTMENT',
                                    'reference_id' => $part->id,
                                    'reference_number' => 'TRF/' . date('Ymd') . '/' . $part->id,
                                    'qty_change' => -$ob->qty_on_hand,
                                    'balance_before' => $ob->qty_on_hand,
                                    'balance_after' => 0,
                                    'notes' => 'Pemindahan fisik barang ke ' . $targetWarehouse->name,
                                    'created_by' => auth()->id() ?? 1,
                                ]);
                                $ob->update(['qty_on_hand' => 0]);
                            }
                        }

                        $targetBalance->update([
                            'qty_on_hand' => $targetAfter,
                        ]);

                        \App\Models\StockLedger::create([
                            'warehouse_id' => $targetWarehouse->id,
                            'part_id' => $part->id,
                            'reference_type' => 'MANUAL_ADJUSTMENT',
                            'reference_id' => $part->id,
                            'reference_number' => 'TRF/' . date('Ymd') . '/' . $part->id,
                            'qty_change' => $totalOtherStock,
                            'balance_before' => $targetBefore,
                            'balance_after' => $targetAfter,
                            'notes' => 'Penerimaan fisik barang dari pemindahan lokasi gudang',
                            'created_by' => auth()->id() ?? 1,
                        ]);
                    }
                });
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
