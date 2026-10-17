<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Part;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Part::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';
    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Gudang';
    protected static ?string $modelLabel = 'Produk (Barang)';
    protected static ?string $pluralModelLabel = 'Daftar Produk';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->hasRole('Admin') || $user->hasRole('Gudang') || $user->hasRole('Kasir') || $user->id === 1;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('Admin') || $user->hasRole('Gudang') || $user->id === 1);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('Admin') || $user->hasRole('Gudang') || $user->id === 1);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('Admin') || $user->id === 1);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                \Filament\Schemas\Components\Section::make('Informasi Produk')
                    ->description('Detail nama dan kategori barang/part.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('part_number')
                            ->label('Nomor Part (Part Number)')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('clean_part_number', preg_replace('/[^A-Za-z0-9]/', '', $state))),
                        
                        Forms\Components\Hidden::make('clean_part_number'),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Barang')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('brand_id')
                            ->label('Merek (Brand)')
                            ->relationship('brand', 'name')
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')->label('Nama Merek')->required()->maxLength(100),
                                \Filament\Forms\Components\TextInput::make('code')->label('Kode Merek')->maxLength(50),
                            ])
                            ->required()
                            ->searchable(),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')->label('Nama Kategori')->required()->maxLength(100),
                                \Filament\Forms\Components\TextInput::make('code')->label('Kode Kategori')->maxLength(50),
                            ])
                            ->searchable(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Lengkap')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(['default' => 1, 'md' => 2]),
                    
                \Filament\Schemas\Components\Section::make('Stok & Lokasi')
                    ->description('Pengaturan lokasi penyimpanan dan batas stok minimum.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('bin_location')
                            ->label('Nomor Rak/Lokasi Gudang')
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('unit')
                            ->label('Satuan (UoM)')
                            ->default('PCS')
                            ->maxLength(20),
                            
                        Forms\Components\TextInput::make('min_stock_level')
                            ->label('Batas Minimal Stok')
                            ->required()
                            ->numeric()
                            ->default(5),
                    ])->columns(['default' => 1, 'md' => 3]),
                    
                \Filament\Schemas\Components\Section::make('Harga & Status')
                    ->description('Harga pembelian, harga jual standar, dan status aktif produk.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Harga Modal / Beli')
                            ->prefix('Rp')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->visible(fn (): bool => auth()->user()?->hasRole('Admin') || auth()->user()?->hasRole('Kasir') || auth()->id() === 1),
                            
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual / Ecer')
                            ->prefix('Rp')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->visible(fn (): bool => auth()->user()?->hasRole('Admin') || auth()->user()?->hasRole('Kasir') || auth()->id() === 1),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(['default' => 1, 'md' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('part_number')
                    ->label('Nomor Part')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Merek')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stock_balances_sum_qty_on_hand')
                    ->label('Total Stok')
                    ->sum('stockBalances', 'qty_on_hand')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
                
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Saring Merek'),
                    
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Saring Kategori'),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Peringatan Stok Menipis')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= parts.min_stock_level')),
            ])
            ->actions([
                \Filament\Actions\Action::make('adjustStock')
                    ->label('Sesuaikan Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Admin') || auth()->user()?->hasRole('Gudang') || auth()->id() === 1)
                    ->form([
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn () => \App\Models\Warehouse::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => \App\Models\Warehouse::first()?->id),
                        Forms\Components\Select::make('type')
                            ->label('Tipe Penyesuaian')
                            ->options([
                                'SET' => 'Tetapkan Stok Fisik / Opname (=)',
                                'ADD' => 'Tambah Stok (+)',
                                'SUB' => 'Kurang Stok (-)',
                            ])
                            ->default('SET')
                            ->required(),
                        Forms\Components\TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('notes')
                            ->label('Catatan / Alasan')
                            ->default('Stok Awal / Hasil Opname')
                            ->required(),
                    ])
                    ->action(function (Part $record, array $data): void {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $warehouseId = (int) $data['warehouse_id'];
                        $qty = (int) $data['qty'];
                        $notes = $data['notes'];

                        if ($data['type'] === 'SET') {
                            $inventoryService->adjustStock($warehouseId, $record->id, $qty, $notes);
                        } elseif ($data['type'] === 'ADD') {
                            if ($qty > 0) {
                                $inventoryService->addStock($warehouseId, $record->id, $qty, 'MANUAL_ADJUSTMENT', $record->id, 'ADJ/' . $record->id, $notes);
                            }
                        } elseif ($data['type'] === 'SUB') {
                            if ($qty > 0) {
                                $inventoryService->deductStock($warehouseId, $record->id, $qty, 'MANUAL_ADJUSTMENT', $record->id, 'ADJ/' . $record->id, $notes);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Stok Produk Berhasil Diperbarui')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                    \Filament\Actions\RestoreBulkAction::make()->label('Pulihkan'),
                ])->label('Aksi Masal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['brand', 'category', 'stockBalances'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
