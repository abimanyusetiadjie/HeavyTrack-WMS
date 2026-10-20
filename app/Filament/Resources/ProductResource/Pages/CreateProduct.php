<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        // Validasi RBAC Backend: Jika bukan Owner, modal HPP otomatis 0
        if (! $user || ! $user->isOwner()) {
            $data['cost_price'] = 0;
        }

        return $data;
    }
}
