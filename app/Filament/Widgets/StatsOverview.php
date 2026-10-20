<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\StockBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStockUnits = StockBalance::sum('qty_on_hand');

        $lowStockCount = Part::whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= parts.min_stock_level')->count();

        $confirmedDO = DeliveryOrder::whereIn('status', ['CONFIRMED', 'ISSUED'])->count();

        $stats = [
            Stat::make('Katalog Produk Aktif', Part::count() . ' Varian')
                ->description('Total stok: ' . number_format($totalStockUnits, 0, ',', '.') . ' PCS')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Peringatan Stok Menipis', $lowStockCount . ' Produk')
                ->description('Di bawah batas stok minimum')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Surat Jalan Dikonfirmasi', $confirmedDO . ' Dokumen')
                ->description('Telah dikonfirmasi & memotong stok')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),
        ];

        $user = auth()->user();
        if ($user && $user->isOwner()) {
            $unpaidAmount = Invoice::whereIn('status', ['UNPAID', 'PARTIALLY_PAID'])->sum('grand_total') 
                - Invoice::whereIn('status', ['UNPAID', 'PARTIALLY_PAID'])->sum('paid_amount');

            $stats[] = Stat::make('Piutang Belum Lunas', 'Rp ' . number_format($unpaidAmount, 0, ',', '.'))
                ->description('Tagihan faktur berjalan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($unpaidAmount > 0 ? 'danger' : 'success');
        }

        return $stats;
    }
}
