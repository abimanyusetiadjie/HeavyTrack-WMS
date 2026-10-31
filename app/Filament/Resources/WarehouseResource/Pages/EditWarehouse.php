<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->stockBalances()->where('qty_on_hand', '>', 0)->exists()) {
                        Notification::make()
                            ->title('Gudang Tidak Dapat Dihapus')
                            ->body("Gudang '{$this->record->name}' masih memiliki saldo stok fisik barang. Pindahkan atau kosongkan stok terlebih dahulu sebelum menghapus gudang.")
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
