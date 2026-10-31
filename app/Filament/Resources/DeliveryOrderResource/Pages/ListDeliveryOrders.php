<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\DeliveryOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDeliveryOrders extends ListRecords
{
    protected static string $resource = DeliveryOrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Surat Jalan'),
            'confirmed' => Tab::make('Dikonfirmasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['CONFIRMED', 'ISSUED']))
                ->badge(fn () => DeliveryOrder::whereIn('status', ['CONFIRMED', 'ISSUED'])->count())
                ->badgeColor('success'),
            'void' => Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'VOID'))
                ->badge(fn () => DeliveryOrder::where('status', 'VOID')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Surat Jalan'),
            Actions\Action::make('deleteDeliveryOrder')
                ->label('Hapus Surat Jalan')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Select::make('delivery_order_id')
                        ->label('Pilih Surat Jalan yang Ingin Dihapus')
                        ->options(fn () => DeliveryOrder::pluck('do_number', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Hapus Surat Jalan')
                ->modalDescription('Apakah Anda yakin ingin menghapus dokumen Surat Jalan ini?')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->action(function (array $data) {
                    $do = DeliveryOrder::find($data['delivery_order_id']);
                    if ($do) {
                        $do->delete();
                        \Filament\Notifications\Notification::make()
                            ->title('Surat Jalan Berhasil Dihapus')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
