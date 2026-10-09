<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Part;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Parts in Catalog', Part::count())
                ->description('Active parts')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
            Stat::make('Pending Delivery Orders', DeliveryOrder::where('status', 'ISSUED')->count())
                ->description('Awaiting shipment')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
            Stat::make('Unpaid Invoices', Invoice::where('status', 'UNPAID')->count())
                ->description('Requires collection')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
        ];
    }
}
