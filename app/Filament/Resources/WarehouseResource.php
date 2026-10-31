<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Gudang';
    protected static ?string $pluralModelLabel = 'Daftar Gudang';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->isOwner() || $user->isAdmin();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isOwner() || $user->isAdmin());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && ($user->isOwner() || $user->isAdmin());
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && ($user->isOwner() || $user->isAdmin());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Lokasi Gudang')
                    ->description('Detail kode dan lokasi fasilitas gudang fisik.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Gudang')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Contoh: GDG-01, PUSAT'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Gudang')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Gudang Utama Jakarta'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Fisik Gudang')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Gudang')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('stock_balances_count')
                    ->label('Jumlah Jenis Part')
                    ->counts('stockBalances')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Ubah')->size('sm'),
                \Filament\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->size('sm')
                    ->before(function (Warehouse $record, \Filament\Actions\DeleteAction $action) {
                        if ($record->stockBalances()->where('qty_on_hand', '>', 0)->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gudang Tidak Dapat Dihapus')
                                ->body("Gudang '{$record->name}' masih memiliki saldo stok fisik barang. Pindahkan atau kosongkan stok terlebih dahulu sebelum menghapus gudang.")
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, \Filament\Actions\DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->stockBalances()->where('qty_on_hand', '>', 0)->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Gudang Tidak Dapat Dihapus')
                                        ->body("Salah satu gudang terpilih ('{$record->name}') masih memiliki saldo stok fisik barang. Operasi dibatalkan.")
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    $action->halt();
                                }
                            }
                        }),
                ])->label('Aksi Masal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
