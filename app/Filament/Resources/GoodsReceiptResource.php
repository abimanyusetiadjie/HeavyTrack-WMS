<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Models\Part;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Gudang';
    protected static ?string $modelLabel = 'Barang Masuk (Penerimaan)';
    protected static ?string $pluralModelLabel = 'Barang Masuk (Inbound)';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->hasRole('Admin') || $user->hasRole('Gudang') || $user->id === 1;
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
            ->schema([
                Grid::make(['default' => 1, 'lg' => 3, 'xl' => 4])->schema([
                    // Kolom Kiri (Main)
                    Group::make()->schema([
                        Section::make('Informasi Dokumen Penerimaan')
                            ->description('Detail surat jalan supplier dan gudang tujuan.')
                            ->schema([
                                Forms\Components\TextInput::make('receipt_number')
                                    ->label('Nomor Bukti Masuk (LPB)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(function () {
                                        $count = GoodsReceipt::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                                        return 'LPB/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->maxLength(50),

                                Forms\Components\DatePicker::make('receipt_date')
                                    ->label('Tanggal Penerimaan')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Gudang Tujuan')
                                    ->relationship('warehouse', 'name')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Nama Gudang')->required()->maxLength(100),
                                        Forms\Components\TextInput::make('code')->label('Kode')->maxLength(50),
                                        Forms\Components\Textarea::make('address')->label('Alamat')->maxLength(500),
                                    ])
                                    ->required()
                                    ->searchable(),

                                Forms\Components\Select::make('contact_id')
                                    ->label('Pemasok / Supplier')
                                    ->relationship('contact', 'company_name', fn ($query) => $query->whereIn('type', ['SUPPLIER', 'BOTH']))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan Supplier')->required()->maxLength(255),
                                        Forms\Components\Select::make('type')->label('Jenis Kontak')->options([
                                            'SUPPLIER' => 'Supplier / Vendor',
                                            'BOTH' => 'Keduanya',
                                        ])->default('SUPPLIER')->required(),
                                        Forms\Components\TextInput::make('pic_name')->label('Nama Kontak/Sales')->maxLength(100),
                                        Forms\Components\TextInput::make('phone')->label('Telepon/WA')->tel()->maxLength(50),
                                        Forms\Components\Textarea::make('address')->label('Alamat')->maxLength(500),
                                    ])
                                    ->searchable(),

                                Forms\Components\TextInput::make('supplier_do_number')
                                    ->label('No. Surat Jalan / Invoice Supplier')
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('po_reference_number')
                                    ->label('No. Purchase Order (PO)')
                                    ->maxLength(100),
                            ])->columns(2),

                        Section::make('Daftar Barang Masuk')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->label('')
                                    ->addActionLabel('Tambah Barang Masuk Lainnya')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('part_id')
                                            ->label('Pilih Produk/Part')
                                            ->options(Part::query()->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $part = Part::find($state);
                                                    if ($part) {
                                                        $set('unit', $part->unit ?? 'PCS');
                                                        $set('unit_cost', $part->cost_price ?? 0);
                                                        $set('bin_location_snapshot', $part->bin_location);
                                                    }
                                                }
                                            })
                                            ->columnSpan(4),

                                        Forms\Components\TextInput::make('qty_received')
                                            ->label('Jumlah Masuk')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('unit')
                                            ->label('Satuan')
                                            ->required()
                                            ->maxLength(20)
                                            ->default('PCS')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Harga Modal / Beli (Rp)')
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('bin_location_snapshot')
                                            ->label('Rak Simpan')
                                            ->maxLength(50)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('notes')
                                            ->label('Keterangan / Kondisi')
                                            ->placeholder('Kondisi fisik, segel kemasan, dll.')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ])->columns(['default' => 12, 'md' => 12]),
                            ]),
                    ])->columnSpan(['default' => 1, 'lg' => 2, 'xl' => 3]),

                    // Kolom Kanan (Sidebar)
                    Group::make()->schema([
                        Section::make('Status Dokumen')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Penerimaan')
                                    ->options([
                                        'DRAFT' => 'DRAFT (Belum Masuk Stok)',
                                        'RECEIVED' => 'DITERIMA (Masuk Saldo Stok)',
                                        'VOID' => 'DIBATALKAN',
                                    ])
                                    ->default('RECEIVED')
                                    ->required(),

                                Forms\Components\Placeholder::make('info_stok')
                                    ->label('Informasi Stok')
                                    ->content('Status "DITERIMA" secara otomatis menambah kuantitas barang ke saldo stok fisik gudang dan mencatat riwayat ke Buku Stok (Stock Ledger).'),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan Penerimaan')
                                    ->placeholder('Misal: Diterima dalam kondisi baik, segel utuh.')
                                    ->maxLength(500),

                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id() ?? 1),
                            ]),
                    ])->columnSpan(['default' => 1, 'lg' => 1, 'xl' => 1]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No. Dokumen LPB')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact.company_name')
                    ->label('Supplier / Pemasok')
                    ->placeholder('Tanpa Supplier')
                    ->searchable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang Tujuan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Item')
                    ->counts('items')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'DRAFT',
                        'success' => 'RECEIVED',
                        'danger' => 'VOID',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT' => 'Draft',
                        'RECEIVED' => 'Diterima (Stok Masuk)',
                        'VOID' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Filter Gudang')
                    ->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'RECEIVED' => 'Diterima',
                        'VOID' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('printLPB')
                    ->label('Cetak LPB')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (GoodsReceipt $record): string => route('print.goods-receipt', $record))
                    ->openUrlInNewTab(),
                \Filament\Actions\EditAction::make()->label('Detail / Ubah'),
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
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
