<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\Part;
use App\Models\StockBalance;
use App\Observers\DeliveryOrderObserver;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $warehouseId = $this->data['warehouse_id'] ?? null;
        $items = $this->data['items'] ?? [];
        $status = $this->data['status'] ?? 'CONFIRMED';

        // 1. Validasi minimal 1 barang
        if (empty($items)) {
            Notification::make()
                ->title('Barang Belum Diisi')
                ->body('Surat Jalan wajib memiliki minimal 1 rincian sparepart yang dikirim.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // 2. Jika status aktif (memotong stok), validasi kecukupan stok fisik di gudang
        if (in_array($status, ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'], true) && $warehouseId) {
            $qtyByPart = [];
            foreach ($items as $item) {
                $partId = $item['part_id'] ?? null;
                $qty = (int) ($item['qty'] ?? 0);
                if ($partId && $qty > 0) {
                    $qtyByPart[$partId] = ($qtyByPart[$partId] ?? 0) + $qty;
                }
            }

            foreach ($qtyByPart as $partId => $totalNeeded) {
                $stock = StockBalance::where('warehouse_id', $warehouseId)
                    ->where('part_id', $partId)
                    ->first();

                $available = $stock ? $stock->qty_on_hand : 0;
                if ($available < $totalNeeded) {
                    $part = Part::find($partId);
                    $partName = $part ? "{$part->part_number} ({$part->name})" : "Part ID #{$partId}";

                    Notification::make()
                        ->title('Stok Gudang Tidak Mencukupi')
                        ->body("Stok untuk {$partName} di gudang ini hanya tersisa {$available} {$part?->unit}, tidak mencukupi untuk pengiriman sebanyak {$totalNeeded} {$part?->unit}.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        }
    }

    protected function afterCreate(): void
    {
        if (in_array($this->record->status, ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'], true)) {
            app(DeliveryOrderObserver::class)->handleStatusChange($this->record, null, $this->record->status);
        }
    }
}
