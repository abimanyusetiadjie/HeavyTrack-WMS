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

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $activePartsCount = Part::where('is_active', true)->count();

        $totalStockUnits = StockBalance::whereHas('part', fn ($q) => $q->where('is_active', true))->sum('qty_on_hand');

        $lowStockCount = Part::where('is_active', true)
            ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= parts.min_stock_level')
            ->count();

        $confirmedDO = DeliveryOrder::whereIn('status', ['CONFIRMED', 'ISSUED'])->count();

        $stats = [
            Stat::make('Katalog Produk Aktif', number_format($activePartsCount, 0, ',', '.') . ' Varian')
                ->description('Total fisik: ' . number_format($totalStockUnits, 0, ',', '.') . ' PCS')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->url('/admin/products'),

            Stat::make('Peringatan Stok Menipis', $lowStockCount . ' Produk')
                ->description('Di bawah batas stok minimum')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->url('/admin/products?tableFilters[low_stock][value]=1'),

            Stat::make('Surat Jalan Dikonfirmasi', $confirmedDO . ' Dokumen')
                ->description('Telah dikonfirmasi & memotong stok')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success')
                ->url('/admin/delivery-orders'),
        ];

        $user = auth()->user();
        if ($user && $user->isOwner()) {
            $unpaidAmount = Invoice::whereIn('status', ['UNPAID', 'PARTIALLY_PAID'])->sum('grand_total') 
                - Invoice::whereIn('status', ['UNPAID', 'PARTIALLY_PAID'])->sum('paid_amount');

            $stats[] = Stat::make('Piutang Belum Lunas', 'Rp ' . number_format($unpaidAmount, 0, ',', '.'))
                ->description('Tagihan faktur berjalan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($unpaidAmount > 0 ? 'danger' : 'success')
                ->url('/admin/invoices');
        }

        return $stats;
    }
}
