<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        // Logika Mengubah Data (Write / Update Protection)
        // Jika bukan Owner, modal/HPP selalu mengabaikan input form dan mengunci nilai modal lama dari database
        if (! $user || ! $user->isOwner()) {
            $data['cost_price'] = $this->record->getRawOriginal('cost_price') ?? 0;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
