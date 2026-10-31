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
        $targetQty = isset($this->data['initial_stock']) ? (int) $this->data['initial_stock'] : null;
        if ($targetQty !== null && $targetQty >= 0) {
            $primaryWarehouse = \App\Models\Warehouse::where('is_active', true)->first();
            if ($primaryWarehouse) {
                $balance = \App\Models\StockBalance::firstOrCreate(
                    ['warehouse_id' => $primaryWarehouse->id, 'part_id' => $this->record->id],
                    ['qty_on_hand' => 0, 'qty_reserved' => 0]
                );
                $oldQty = (int) $balance->qty_on_hand;
                if ($targetQty !== $oldQty) {
                    $diff = $targetQty - $oldQty;
                    $balance->qty_on_hand = $targetQty;
                    $balance->save();

                    \App\Models\StockLedger::create([
                        'part_id' => $this->record->id,
                        'warehouse_id' => $primaryWarehouse->id,
                        'reference_type' => 'STOCK_ADJUSTMENT',
                        'reference_id' => $this->record->id,
                        'reference_number' => 'OPNAME/' . $this->record->part_number . '/' . date('YmdHi'),
                        'qty_change' => $diff,
                        'balance_before' => $oldQty,
                        'balance_after' => $targetQty,
                        'notes' => 'Penyesuaian stok langsung dari form produk',
                        'created_by' => auth()->id() ?? 1,
                    ]);
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
