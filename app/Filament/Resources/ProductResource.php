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
    protected static ?string $modelLabel = 'Produk (Barang)';
    protected static ?string $pluralModelLabel = 'Daftar Produk';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Informasi Produk')
                    ->description('Detail nama dan kategori barang/part.')
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
                    ])->columns(2),
                    
                \Filament\Schemas\Components\Section::make('Stok & Lokasi')
                    ->description('Pengaturan lokasi penyimpanan dan batas stok minimum.')
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
                    ])->columns(3),
                    
                \Filament\Schemas\Components\Section::make('Harga & Status')
                    ->description('Harga pembelian dan harga jual standar produk.')
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Harga Modal / Beli (Rp)')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                            
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual / Ecer (Rp)')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(3),
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
                \Filament\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                    \Filament\Actions\ForceDeleteBulkAction::make()->label('Hapus Permanen'),
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
