<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use App\Models\Part;
use App\Models\StockBalance;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function beforeSave(): void
    {
        $oldStatus = $this->record->status;
        $newStatus = $this->data['status'] ?? $oldStatus;

        // Jika mengubah status dari RECEIVED ke DRAFT atau VOID, stok akan ditarik kembali
        if ($oldStatus === 'RECEIVED' && $newStatus !== 'RECEIVED') {
            $this->record->loadMissing('items');
            foreach ($this->record->items as $item) {
                $currentStock = StockBalance::where('warehouse_id', $this->record->warehouse_id)
                    ->where('part_id', $item->part_id)
                    ->value('qty_on_hand') ?? 0;

                if ($currentStock < $item->qty_received) {
                    $part = Part::withTrashed()->find($item->part_id);
                    $partName = $part ? "{$part->part_number} ({$part->name})" : "Part #{$item->part_id}";

                    Notification::make()
                        ->title('Gagal Mengubah Status LPB')
                        ->body("Stok fisik untuk {$partName} di gudang tersisa {$currentStock}, sedangkan dokumen LPB ini memasukkan {$item->qty_received}. Sebagian atau seluruh stok telah terpakai dalam transaksi lain (misal Surat Jalan).")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak Bukti LPB')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('print.goods-receipt', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->status === 'RECEIVED') {
                        $this->record->loadMissing('items');
                        foreach ($this->record->items as $item) {
                            $currentStock = StockBalance::where('warehouse_id', $this->record->warehouse_id)
                                ->where('part_id', $item->part_id)
                                ->value('qty_on_hand') ?? 0;

                            if ($currentStock < $item->qty_received) {
                                $part = Part::withTrashed()->find($item->part_id);
                                $partName = $part ? "{$part->part_number} ({$part->name})" : "Part #{$item->part_id}";

                                Notification::make()
                                    ->title('Tidak Dapat Menghapus Dokumen LPB')
                                    ->body("Stok fisik {$partName} di gudang tersisa {$currentStock}, sedangkan LPB ini memasukkan {$item->qty_received}. Stok sudah terpakai dalam transaksi lain sehingga dokumen tidak dapat dihapus.")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }
                    }
                }),
        ];
    }
}
