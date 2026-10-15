<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;
}
