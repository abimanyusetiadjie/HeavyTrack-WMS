<?php

namespace App\Filament\Resources\StockLedgerResource\Pages;

use App\Filament\Resources\StockLedgerResource;
use Filament\Resources\Pages\ListRecords;

class ListStockLedgers extends ListRecords
{
    protected static string $resource = StockLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Read-only audit log
    }
}
