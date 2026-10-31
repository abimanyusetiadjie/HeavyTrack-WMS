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

    protected static bool $shouldRegisterNavigation = true;

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
        // Sistem dikunci permanen pada 2 gudang resmi: GUDANG MITRA ABADI & GUDANG ANEKARASA
        return false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && ($user->isOwner() || $user->isAdmin());
    }

    public static function canDelete($record): bool
    {
        // 2 Gudang resmi terkunci permanen dan tidak dapat dihapus
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Lokasi Gudang (Terkunci Sistem)')
                    ->description('Daftar gudang operasional resmi sistem HeavyTrack WMS.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Gudang')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Kode gudang dikunci permanen oleh sistem.'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Gudang')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Nama gudang dikunci permanen oleh sistem.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Gudang operasional selalu aktif.'),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Fisik Gudang')
                            ->maxLength(500)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state)
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
                    ->limit(60)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('stock_balances_count')
                    ->label('Jumlah Jenis Part')
                    ->counts('stockBalances')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label('Ubah Alamat')
                    ->icon('heroicon-o-pencil-square')
                    ->size('sm'),
            ])
            ->bulkActions([]);
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
