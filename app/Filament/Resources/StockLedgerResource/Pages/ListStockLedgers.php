<?php

namespace App\Filament\Resources\StockLedgerResource\Pages;

use App\Filament\Resources\StockLedgerResource;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStockLedgers extends ListRecords
{
    protected static string $resource = StockLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reconcile')
                ->label('Audit & Rekonsiliasi Stok')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Audit & Sinkronkan Saldo Stok')
                ->modalDescription('Sistem akan mencocokkan total saldo fisik (StockBalance) dengan seluruh akumulasi riwayat mutasi (StockLedger). Jika ditemukan selisih, sistem akan otomatis merekonsiliasi saldo fisik agar 100% akurat.')
                ->modalSubmitActionLabel('Jalankan Audit & Sinkronisasi')
                ->action(function () {
                    $result = app(InventoryService::class)->reconcileStock();

                    if ($result['discrepancies'] === 0) {
                        Notification::make()
                            ->title('Integritas Stok Sempurna (100% Sinkron)')
                            ->body("Seluruh {$result['audited']} varian produk di semua gudang telah diverifikasi cocok dengan mutasi kartu stok.")
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Rekonsiliasi Berhasil')
                            ->body("Ditemukan dan berhasil diperbaiki {$result['discrepancies']} varian stok yang berselisih.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
