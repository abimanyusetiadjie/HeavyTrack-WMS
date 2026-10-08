<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Models\DeliveryOrder;
use App\Models\Part;
use App\Models\StockBalance;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
    protected static ?string $modelLabel = 'Delivery Order';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('General Information')->schema([
                    Forms\Components\TextInput::make('do_number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(function () {
                            $count = DeliveryOrder::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                            return 'SJ/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        })
                        ->maxLength(50),
                    
                    Forms\Components\Select::make('sales_order_id')
                        ->relationship('salesOrder', 'order_number')
                        ->searchable(),
                        
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')
                        ->required()
                        ->reactive()
                        ->searchable(),
                        
                    Forms\Components\Select::make('contact_id')
                        ->relationship('contact', 'company_name')
                        ->required()
                        ->searchable(),
                        
                    Forms\Components\DatePicker::make('delivery_date')
                        ->required()
                        ->default(now()),
                        
                    Forms\Components\TextInput::make('driver_name')
                        ->maxLength(100),
                        
                    Forms\Components\TextInput::make('vehicle_plate_number')
                        ->maxLength(30),
                        
                    Forms\Components\Select::make('status')
                        ->options([
                            'DRAFT' => 'DRAFT',
                            'ISSUED' => 'ISSUED',
                            'CONFIRMED' => 'CONFIRMED',
                            'SHIPPED' => 'SHIPPED',
                            'RECEIVED' => 'RECEIVED',
                            'VOID' => 'VOID',
                        ])
                        ->default('ISSUED')
                        ->required(),
                        
                    Forms\Components\Hidden::make('created_by')
                        ->default(auth()->id() ?? 1),
                ])->columns(2),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('part_id')
                                ->label('Product (Part)')
                                ->options(Part::query()->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $warehouseId = $get('../../warehouse_id');
                                    if ($state && $warehouseId) {
                                        $stock = StockBalance::where('part_id', $state)->where('warehouse_id', $warehouseId)->first();
                                        $set('available_stock', $stock ? $stock->qty_on_hand : 0);
                                        $part = Part::find($state);
                                        if ($part) {
                                            $set('unit', $part->unit);
                                        }
                                    } else {
                                        $set('available_stock', 0);
                                    }
                                }),
                                
                            Forms\Components\TextInput::make('available_stock')
                                ->disabled()
                                ->dehydrated(false)
                                ->numeric(),
                                
                            Forms\Components\TextInput::make('qty')
                                ->required()
                                ->numeric()
                                ->minValue(1)->lte('available_stock')->validationMessages(['lte' => 'Kuantitas tidak boleh melebihi stok yang tersedia.']),
                                
                            Forms\Components\TextInput::make('unit')
                                ->required()
                                ->maxLength(20),
                                
                            Forms\Components\TextInput::make('notes')
                                ->maxLength(255),
                        ])->columns(5),
                ]),
                
                Forms\Components\Section::make('Notes')->schema([
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('do_number')->searchable(),
                Tables\Columns\TextColumn::make('delivery_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name'),
                Tables\Columns\TextColumn::make('contact.company_name'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-printer')
                    ->url(fn (DeliveryOrder $record): string => route('print.delivery-order', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }
}
