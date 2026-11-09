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
        return $user->isOwner() || $user->isAdmin();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) $user;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return (bool) $user;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && ($user->isOwner() || $user->isAdmin());
    }

    public static function formatPriceDisplay($state): string
    {
        if ($state === null || $state === '' || (float) $state <= 0) {
            return '-';
        }

        $val = (float) $state;
        // Jika bilangan bulat murni (contoh: 150000, 16)
        if (floor($val) == $val) {
            return 'Rp ' . number_format($val, 0, ',', '.');
        }

        // Jika memiliki nilai desimal (contoh: 8.5, 8.25, 2.3)
        // Bersihkan trailing zero agar tampil nilai real bersih tanpa pembulatan
        $str = rtrim(rtrim(sprintf('%.4f', $val), '0'), '.');
        $parts = explode('.', $str);
        $intPart = number_format((int) $parts[0], 0, ',', '.');
        $decPart = $parts[1] ?? '';

        if ($val < 1000) {
            return 'Rp ' . $parts[0] . ($decPart !== '' ? '.' . $decPart : '');
        }

        return 'Rp ' . $intPart . ($decPart !== '' ? ',' . $decPart : '');
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('clean_part_number', preg_replace('/[^A-Za-z0-9]/', '', $state))),
                        
                        Forms\Components\Hidden::make('clean_part_number'),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Barang')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('brand_id')
                            ->label('Merek (Brand)')
                            ->relationship('brand', 'name', fn (Builder $query) => $query->orderBy('name'))
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Nama Merek')
                                    ->required()
                                    ->maxLength(100)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                                \Filament\Forms\Components\TextInput::make('code')
                                    ->label('Kode Merek')
                                    ->maxLength(50)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                            ])
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('name'))
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->maxLength(100)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                                \Filament\Forms\Components\TextInput::make('code')
                                    ->label('Kode Kategori')
                                    ->maxLength(50)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                            ])
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Lengkap')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(['default' => 1, 'md' => 2]),
                    
                \Filament\Schemas\Components\Section::make('Stok & Lokasi')
                    ->description('Pengaturan lokasi penyimpanan dan batas stok minimum.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('bin_location')
                            ->label('Lokasi Gudang')
                            ->placeholder('Pilih Lokasi Gudang')
                            ->options(fn () => \App\Models\Warehouse::where('is_active', true)->orderBy('id')->get()->mapWithKeys(function ($wh) {
                                $label = str_contains(strtoupper($wh->name), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi';
                                return [$label => $label];
                            })->toArray())
                            ->formatStateUsing(function ($state, ?Part $record) {
                                if (! empty($state)) {
                                    return str_contains(strtoupper($state), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi';
                                }
                                if ($record && $record->stockBalances()->where('qty_on_hand', '>', 0)->exists()) {
                                    $first = $record->stockBalances()->where('qty_on_hand', '>', 0)->with('warehouse')->first();
                                    if ($first && $first->warehouse) {
                                        return str_contains(strtoupper($first->warehouse->name), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi';
                                    }
                                }
                                return 'Gudang Mitra Abadi';
                            })
                            ->default('Gudang Mitra Abadi')
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\TextInput::make('unit')
                            ->label('Satuan (UoM)')
                            ->default('PCS')
                            ->maxLength(20),
                            
                        Forms\Components\TextInput::make('min_stock_level')
                            ->label('Batas Minimal Stok (Alert)')
                            ->helperText('Batas peringatan stok menipis.')
                            ->required()
                            ->numeric()
                            ->default(5),
                    ])->columns(['default' => 1, 'md' => 3]),
                    
                \Filament\Schemas\Components\Section::make('Harga & Status')
                    ->description('Harga pembelian, harga jual standar, dan status aktif produk.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Harga Pokok (HPP / Modal)')
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->helperText('Bebas mengetik koma (,) atau titik (.) di mana pun.')
                            ->formatStateUsing(function ($state) {
                                if (! filled($state) || (float) $state <= 0) {
                                    return null;
                                }
                                $f = (float) $state;
                                if (floor($f) == $f) {
                                    return (int) $f;
                                }
                                return rtrim(rtrim(sprintf('%.4f', $f), '0'), '.');
                            })
                            ->dehydrateStateUsing(fn ($state) => \App\Models\Part::parsePrice($state))
                            ->nullable()
                            ->visible(fn (): bool => auth()->user()?->isOwner() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->isOwner() ?? false),
                            
                        Forms\Components\TextInput::make('selling_price')
                            ->label(fn (): string => (auth()->user()?->isOwner() ?? false) ? 'Harga Jual Standar' : 'Harga Jual Kasir')
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->helperText('Bebas mengetik koma (,) atau titik (.) di mana pun.')
                            ->formatStateUsing(function ($state) {
                                if (! filled($state) || (float) $state <= 0) {
                                    return null;
                                }
                                $f = (float) $state;
                                if (floor($f) == $f) {
                                    return (int) $f;
                                }
                                return rtrim(rtrim(sprintf('%.4f', $f), '0'), '.');
                            })
                            ->dehydrateStateUsing(fn ($state) => \App\Models\Part::parsePrice($state))
                            ->nullable(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(['default' => 1, 'md' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->header(view('filament.components.product-search-bar'))
            ->searchPlaceholder('Cari nama barang, barcode, atau SKU...')
            ->columns([
                Tables\Columns\TextColumn::make('part_number')
                    ->label('Nomor Part')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Merek')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('HPP (Modal)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => static::formatPriceDisplay($state))
                    ->visible(fn (): bool => auth()->user()?->isOwner() ?? false),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label(fn (): string => (auth()->user()?->isOwner() ?? false) ? 'Harga Jual' : 'Harga')
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state) => static::formatPriceDisplay($state)),
                    
                Tables\Columns\TextColumn::make('stock_balances_sum_qty_on_hand')
                    ->label('Total Stok')
                    ->sum('stockBalances', 'qty_on_hand')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, Part $record) => ($state === null || (float) $state <= ($record->min_stock_level ?? 5)) ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state, Part $record) => number_format((float) ($state ?? 0), 0, ',', '.') . ' ' . ($record->unit ?? 'PCS')),
                    
                Tables\Columns\TextColumn::make('warehouse_locations')
                    ->label('Gudang')
                    ->badge()
                    ->state(function (Part $record) {
                        $balances = $record->stockBalances->filter(fn ($sb) => (int) ($sb->qty_on_hand ?? 0) > 0);
                        
                        if ($balances->count() > 1) {
                            return $balances->map(function ($sb) use ($record) {
                                $whName = $sb->warehouse?->name ?? '';
                                $label = str_contains(strtoupper($whName), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi';
                                return "{$label} ({$sb->qty_on_hand} " . ($record->unit ?? 'PCS') . ")";
                            })->values()->toArray();
                        }

                        if ($balances->count() === 1) {
                            $whName = $balances->first()->warehouse?->name ?? '';
                            return [str_contains(strtoupper($whName), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi'];
                        }

                        $anyBalance = $record->stockBalances->first();
                        if ($anyBalance && $anyBalance->warehouse) {
                            $whName = $anyBalance->warehouse->name;
                            return [str_contains(strtoupper($whName), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi'];
                        }

                        return ['Gudang Mitra Abadi'];
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Mitra Abadi') => 'info',
                        str_contains($state, 'Anekarasa') => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
                Tables\Filters\SelectFilter::make('warehouse')
                    ->label('Filter Gudang')
                    ->options([
                        'GMA' => 'Gudang Mitra Abadi',
                        'GAR' => 'Gudang Anekarasa',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $code = $data['value'];
                            $query->whereHas('stockBalances', function ($q) use ($code) {
                                $q->whereHas('warehouse', fn ($wh) => $wh->where('code', $code));
                            });
                        }
                    }),
                Tables\Filters\SelectFilter::make('brand_id')->relationship('brand', 'name', fn (Builder $query) => $query->orderBy('name'))->label('Filter Merk')->preload(),
                Tables\Filters\SelectFilter::make('category_id')->relationship('category', 'name', fn (Builder $query) => $query->orderBy('name'))->label('Filter Kategori')->preload(),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Menipis (<= Batas Minimum)')
                    ->query(fn (Builder $query) => $query->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= parts.min_stock_level')),
            ])
            ->actions([
                \Filament\Actions\Action::make('adjustStock')
                    ->label('Sesuaikan Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->size('sm')
                    ->visible(fn (): bool => auth()->user() !== null)
                    ->form([
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn () => \App\Models\Warehouse::where('is_active', true)->orderBy('id')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => \App\Models\Warehouse::where('code', 'GMA')->value('id') ?? \App\Models\Warehouse::where('name', 'like', '%MITRA ABADI%')->value('id') ?? \App\Models\Warehouse::first()?->id),
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

                        try {
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

                            $wh = \App\Models\Warehouse::find($warehouseId);
                            if ($wh && $record->stockBalances()->where('warehouse_id', '!=', $warehouseId)->sum('qty_on_hand') == 0) {
                                $record->update([
                                    'bin_location' => str_contains(strtoupper($wh->name), 'ANEKARASA') ? 'Gudang Anekarasa' : 'Gudang Mitra Abadi',
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Stok Produk Berhasil Diperbarui')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Menyesuaikan Stok')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                \Filament\Actions\EditAction::make()->label('Ubah')->size('sm'),
                \Filament\Actions\DeleteAction::make()->label('Hapus')->size('sm'),
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
        return parent::getEloquentQuery()
            ->with([
                'brand:id,name',
                'category:id,name',
                'stockBalances.warehouse:id,name,code',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->orderBy(
                \App\Models\Brand::select('name')->whereColumn('brands.id', 'parts.brand_id'),
                'asc'
            )
            ->orderBy('parts.part_number', 'asc');
    }
}
