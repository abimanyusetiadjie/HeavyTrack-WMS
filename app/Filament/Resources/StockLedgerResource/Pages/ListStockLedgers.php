<?php

namespace App\Filament\Resources\StockLedgerResource\Pages;

use App\Filament\Resources\StockLedgerResource;
use App\Models\StockLedger;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStockLedgers extends ListRecords
{
    protected static string $resource = StockLedgerResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Mutasi'),
            'delivery_order' => Tab::make('Surat Jalan (DO)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('reference_type', 'DELIVERY_ORDER'))
                ->badge(fn () => StockLedger::where('reference_type', 'DELIVERY_ORDER')->count()),
            'goods_receipt' => Tab::make('Restock Masuk')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('reference_type', 'GOODS_RECEIPT'))
                ->badge(fn () => StockLedger::where('reference_type', 'GOODS_RECEIPT')->count()),
            'adjustment' => Tab::make('Koreksi Stok')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('reference_type', ['STOCK_ADJUSTMENT', 'MANUAL_ADJUSTMENT']))
                ->badge(fn () => StockLedger::whereIn('reference_type', ['STOCK_ADJUSTMENT', 'MANUAL_ADJUSTMENT'])->count()),
            'void' => Tab::make('Void / Batal')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('reference_type', ['DELIVERY_ORDER_VOID', 'GOODS_RECEIPT_VOID']))
                ->badge(fn () => StockLedger::whereIn('reference_type', ['DELIVERY_ORDER_VOID', 'GOODS_RECEIPT_VOID'])->count())
                ->badgeColor('danger'),
            'initial' => Tab::make('Saldo Awal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('reference_type', 'INITIAL_STOCK'))
                ->badge(fn () => StockLedger::where('reference_type', 'INITIAL_STOCK')->count()),
        ];
    }

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
