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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
    protected static ?string $modelLabel = 'Surat Jalan (DO)';
    protected static ?string $pluralModelLabel = 'Surat Jalan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)->schema([
                    // Kolom Kiri (Main)
                    Group::make()->schema([
                        Section::make('Informasi Utama')
                            ->description('Detail dasar surat jalan dan tujuan pengiriman.')
                            ->schema([
                                Forms\Components\TextInput::make('do_number')
                                    ->label('Nomor Surat Jalan')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(function () {
                                        $count = DeliveryOrder::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                                        return 'SJ/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->maxLength(50),
                                
                                Forms\Components\DatePicker::make('delivery_date')
                                    ->label('Tanggal Pengiriman')
                                    ->required()
                                    ->default(now()),
                                    
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Gudang Asal')
                                    ->relationship('warehouse', 'name')->createOptionForm([ 
                                        \Filament\Forms\Components\TextInput::make('name')->label('Nama Gudang')->required()->maxLength(100), 
                                        \Filament\Forms\Components\TextInput::make('code')->label('Kode')->maxLength(50), 
                                        \Filament\Forms\Components\Textarea::make('address')->label('Alamat Lengkap')->maxLength(500), 
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->searchable(),
                                    
                                Forms\Components\Select::make('contact_id')
                                    ->label('Pelanggan / Tujuan')
                                    ->relationship('contact', 'company_name')->createOptionForm([ 
                                        \Filament\Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan/Orang')->required()->maxLength(255), 
                                        \Filament\Forms\Components\TextInput::make('contact_person')->label('Nama Kontak')->maxLength(255), 
                                        \Filament\Forms\Components\TextInput::make('phone')->label('Nomor Telepon')->tel()->maxLength(50), 
                                        \Filament\Forms\Components\Textarea::make('address')->label('Alamat Lengkap')->maxLength(500), 
                                    ])
                                    ->required()
                                    ->searchable(),
                            ])->columns(2),

                        Section::make('Daftar Barang (Item)')
                            ->schema([
                            Forms\Components\Repeater::make('items')
                                ->label('')
                                ->addActionLabel('Tambah Barang Lainnya')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('part_id')
                                        ->label('Pilih Produk/Part')
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
                                        })
                                        ->columnSpan(4),
                                        
                                    Forms\Components\TextInput::make('available_stock')
                                        ->label('Sisa Stok')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->numeric()
                                        ->columnSpan(2),
                                        
                                    Forms\Components\TextInput::make('qty')
                                        ->label('Jumlah (Qty)')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)->lte('available_stock')->validationMessages(['lte' => 'Kuantitas melebihi stok yang tersedia.'])
                                        ->columnSpan(2),
                                        
                                    Forms\Components\TextInput::make('unit')
                                        ->label('Satuan')
                                        ->required()
                                        ->maxLength(20)
                                        ->columnSpan(2),
                                        
                                    Forms\Components\TextInput::make('notes')
                                        ->label('Catatan Tambahan')
                                        ->maxLength(255)
                                        ->columnSpan(10),
                                ])->columns(10),
                        ]),
                    ])->columnSpan(2),

                    // Kolom Kanan (Sidebar)
                    Group::make()->schema([
                        Section::make('Pengaturan')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Pengiriman')
                                    ->options([
                                        'DRAFT' => 'DRAFT',
                                        'ISSUED' => 'DITERBITKAN',
                                        'CONFIRMED' => 'DIKONFIRMASI',
                                        'SHIPPED' => 'DALAM PENGIRIMAN',
                                        'RECEIVED' => 'DITERIMA',
                                        'VOID' => 'DIBATALKAN',
                                    ])
                                    ->default('ISSUED')
                                    ->required(),
                                    
                                Forms\Components\Select::make('sales_order_id')
                                    ->label('Referensi Sales Order (Jika Ada)')
                                    ->relationship('salesOrder', 'order_number')
                                    ->searchable(),
                            ]),

                        Section::make('Informasi Logistik')
                            ->description('Detail pengirim.')
                            ->schema([
                                Forms\Components\TextInput::make('driver_name')
                                    ->label('Nama Supir')
                                    ->maxLength(100),
                                    
                                Forms\Components\TextInput::make('vehicle_plate_number')
                                    ->label('Plat Nomor Kendaraan')
                                    ->maxLength(30),
                            ]),

                        Section::make('Catatan Surat Jalan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Keterangan Lainnya')
                                    ->rows(4),
                                    
                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id() ?? 1),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('do_number')->label('Nomor SJ')->searchable(),
                Tables\Columns\TextColumn::make('delivery_date')->label('Tgl Kirim')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Gudang Asal'),
                Tables\Columns\TextColumn::make('contact.company_name')->label('Tujuan / Pelanggan'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Ubah'),
                \Filament\Actions\Action::make('print')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn (DeliveryOrder $record): string => route('print.delivery-order', ['deliveryOrder' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ])->label('Aksi Masal'),
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
