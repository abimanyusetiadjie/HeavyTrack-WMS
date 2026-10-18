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
    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Gudang';
    protected static ?string $modelLabel = 'Surat Jalan (DO)';
    protected static ?string $pluralModelLabel = 'Surat Jalan';
    protected static ?int $navigationSort = 3;

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
                Section::make('Informasi Dokumen Surat Jalan')
                    ->description('Detail surat jalan, gudang pengirim, dan tujuan pengiriman.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('do_number')
                            ->label('Nomor Surat Jalan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(function () {
                                $count = DeliveryOrder::withTrashed()->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                                return 'SJ/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                            })
                            ->maxLength(50),
                        
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Tanggal Pengiriman')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status Pengiriman')
                            ->options([
                                'DRAFT' => 'DRAFT (Konsep - Belum Potong Stok)',
                                'ISSUED' => 'DITERBITKAN (Potong Stok)',
                                'CONFIRMED' => 'DIKONFIRMASI',
                                'SHIPPED' => 'DALAM PENGIRIMAN',
                                'RECEIVED' => 'DITERIMA PELANGGAN',
                                'VOID' => 'DIBATALKAN (Kembalikan Stok)',
                            ])
                            ->default('ISSUED')
                            ->required()
                            ->helperText('Status selain DRAFT akan otomatis memotong saldo stok fisik gudang.'),
                            
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
                            ->relationship('contact', 'company_name', fn ($query) => $query->whereIn('type', ['CUSTOMER', 'BOTH']))
                            ->createOptionForm([ 
                                Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan / Pembeli')->required()->maxLength(255), 
                                Forms\Components\Hidden::make('type')->default('CUSTOMER'),
                                Forms\Components\TextInput::make('pic_name')->label('Nama Kontak / PIC')->maxLength(100), 
                                Forms\Components\TextInput::make('phone')->label('Nomor Telepon / WA')->tel()->maxLength(50), 
                                Forms\Components\Textarea::make('address')->label('Alamat Lengkap')->maxLength(500), 
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('sales_order_id')
                            ->label('Referensi Sales Order (Jika Ada)')
                            ->relationship('salesOrder', 'order_number')
                            ->searchable(),

                        Forms\Components\TextInput::make('driver_name')
                            ->label('Nama Supir')
                            ->maxLength(100),
                            
                        Forms\Components\TextInput::make('vehicle_plate_number')
                            ->label('Plat Nomor Kendaraan')
                            ->maxLength(30),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Surat Jalan')
                            ->rows(2)
                            ->placeholder('Catatan pengiriman, rute, instruksi supir, dll.')
                            ->maxLength(500),
                            
                        Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id() ?? 1),
                    ])->columns(['default' => 1, 'md' => 2, 'lg' => 3]),

                Section::make('Daftar Barang (Item)')
                    ->description('Daftar sparepart yang dikeluarkan dari gudang untuk dikirim.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->addActionLabel('Tambah Barang Lainnya')
                            ->relationship()
                            ->minItems(1)
                            ->required()
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Pilih Produk / Sparepart')
                                    ->options(fn () => Part::withTrashed()->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->part_number} - {$p->name}"]))
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if ($state && $warehouseId) {
                                            $stock = StockBalance::where('part_id', $state)->where('warehouse_id', $warehouseId)->first();
                                            $set('available_stock', $stock ? $stock->qty_on_hand : 0);
                                            $part = Part::withTrashed()->find($state);
                                            if ($part) {
                                                $set('unit', $part->unit ?? 'PCS');
                                            }
                                        } else {
                                            $set('available_stock', 0);
                                        }
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 5]),
                                    
                                Forms\Components\TextInput::make('available_stock')
                                    ->label('Sisa Stok')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, callable $get) {
                                        $partId = $get('part_id');
                                        $warehouseId = $get('../../warehouse_id');
                                        if ($partId && $warehouseId) {
                                            $stock = StockBalance::where('part_id', $partId)->where('warehouse_id', $warehouseId)->first();
                                            $component->state($stock ? $stock->qty_on_hand : 0);
                                        }
                                    })
                                    ->columnSpan(['default' => 4, 'md' => 2]),
                                    
                                Forms\Components\TextInput::make('qty')
                                    ->label('Jumlah (Qty)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->columnSpan(['default' => 4, 'md' => 2]),
                                    
                                Forms\Components\TextInput::make('unit')
                                    ->label('Satuan')
                                    ->required()
                                    ->maxLength(20)
                                    ->columnSpan(['default' => 4, 'md' => 3]),
                                    
                                Forms\Components\TextInput::make('notes')
                                    ->label('Catatan Tambahan')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])->columns(['default' => 12, 'md' => 12]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('do_number')->label('Nomor SJ')->searchable()->fontFamily('mono')->weight('bold'),
                Tables\Columns\TextColumn::make('delivery_date')->label('Tgl Kirim')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Gudang Asal'),
                Tables\Columns\TextColumn::make('contact.company_name')->label('Tujuan / Pelanggan'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Faktur Penjualan')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ?: 'Belum Ada Faktur')
                    ->url(fn (DeliveryOrder $record) => $record->invoice ? route('filament.admin.resources.invoices.edit', ['record' => $record->invoice->id]) : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
            ])
            ->actions([
                \Filament\Actions\Action::make('printInvoice')
                    ->label(fn (DeliveryOrder $record) => $record->invoice ? 'Cetak Faktur' : 'Terbitkan & Cetak Faktur')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color(fn (DeliveryOrder $record) => $record->invoice ? 'success' : 'warning')
                    ->url(fn (DeliveryOrder $record): string => route('print.delivery-order.invoice', ['deliveryOrder' => $record->id]))
                    ->openUrlInNewTab(),
                \Filament\Actions\Action::make('print')
                    ->label('Cetak SJ')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (DeliveryOrder $record): string => route('print.delivery-order', ['deliveryOrder' => $record->id]))
                    ->openUrlInNewTab(),
                \Filament\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ])->label('Aksi Masal'),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['warehouse', 'contact', 'invoice']);
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
