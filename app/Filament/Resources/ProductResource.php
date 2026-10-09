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
    protected static ?string $modelLabel = 'Product (Part)';
    protected static ?string $pluralModelLabel = 'Products (Parts)';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('part_number')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('clean_part_number', preg_replace('/[^A-Za-z0-9]/', '', $state))),
                        
                        Forms\Components\Hidden::make('clean_part_number'),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(100),
                                \Filament\Forms\Components\TextInput::make('code')->maxLength(50),
                            ])
                            ->required()
                            ->searchable(),
                            
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(100),
                                \Filament\Forms\Components\TextInput::make('code')->maxLength(50),
                            ])
                            ->searchable(),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                \Filament\Schemas\Components\Section::make('Stok & Lokasi')
                    ->schema([
                        Forms\Components\TextInput::make('bin_location')
                            ->label('Rak/Lokasi Gudang')
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('unit')
                            ->default('PCS')
                            ->maxLength(20),
                            
                        Forms\Components\TextInput::make('min_stock_level')
                            ->required()
                            ->numeric()
                            ->default(5),
                    ])->columns(3),
                    
                \Filament\Schemas\Components\Section::make('Harga & Status')
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                            
                        Forms\Components\TextInput::make('selling_price')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('part_number')
                    ->label('Part Number')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stock_balances_sum_qty_on_hand')
                    ->label('Total Stock')
                    ->sum('stockBalances', 'qty_on_hand')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Brand'),
                    
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Minimum Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->whereHas('stockBalances', function ($q) {
                        // Assuming total stock across warehouses or just simple sum
                        // For exact minimum check, we compare the sum
                        // But in Eloquent, whereHas doesn't aggregate easily. We can use withSum and having.
                    })
                    // Better approach for filtering based on aggregate:
                    ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM stock_balances WHERE stock_balances.part_id = parts.id) <= parts.min_stock_level')
                    ),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
