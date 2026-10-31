<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Faktur'),
            'unpaid' => Tab::make('Belum Lunas')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('payment_status', ['UNPAID', 'PARTIAL']))
                ->badge(fn () => Invoice::whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count())
                ->badgeColor('warning'),
            'paid' => Tab::make('Lunas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'PAID'))
                ->badge(fn () => Invoice::where('payment_status', 'PAID')->count())
                ->badgeColor('success'),
            'void' => Tab::make('Batal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'VOID'))
                ->badge(fn () => Invoice::where('status', 'VOID')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
