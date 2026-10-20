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
use Illuminate\Database\Eloquent\Builder;

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Dokumen Penerimaan')
                    ->description('Detail surat jalan supplier, gudang tujuan, dan status penerimaan.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Nomor Bukti Masuk (LPB)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(function () {
                                $prefix = 'LPB/' . date('Ym') . '/';
                                $latest = GoodsReceipt::withTrashed()
                                    ->where('receipt_number', 'like', $prefix . '%')
                                    ->orderByDesc('receipt_number')
                                    ->value('receipt_number');

                                $nextSeq = 1;
                                if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                                    $nextSeq = (int) $matches[1] + 1;
                                } else {
                                    $nextSeq = GoodsReceipt::withTrashed()
                                        ->whereMonth('created_at', date('m'))
                                        ->whereYear('created_at', date('Y'))
                                        ->count() + 1;
                                }

                                return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
                            })
                            ->maxLength(50),

                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('Tanggal Penerimaan')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status Penerimaan')
                            ->options([
                                'DRAFT' => 'DRAFT (Belum Masuk Stok)',
                                'RECEIVED' => 'DITERIMA (Masuk Saldo Stok)',
                                'VOID' => 'DIBATALKAN',
                            ])
                            ->default('RECEIVED')
                            ->required()
                            ->helperText('Status DITERIMA otomatis menambah saldo stok fisik gudang.'),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang Tujuan')
                            ->relationship('warehouse', 'name')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama Gudang')->required()->maxLength(100),
                                Forms\Components\TextInput::make('code')->label('Kode')->maxLength(50),
                                Forms\Components\Textarea::make('address')->label('Alamat')->maxLength(500),
                            ])
                            ->required()
                            ->searchable()
                            ->disabled(fn (?GoodsReceipt $record) => $record?->status === 'RECEIVED'),

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

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Penerimaan')
                            ->placeholder('Misal: Diterima dalam kondisi baik, segel utuh.')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id() ?? 1),
                    ])->columns(['default' => 1, 'md' => 2, 'lg' => 3]),

                Section::make('Daftar Barang Masuk')
                    ->description(fn (?GoodsReceipt $record) => $record?->status === 'RECEIVED'
                        ? 'Rincian sparepart telah diterima dan menambah saldo stok gudang (Terkunci untuk menjaga integritas stok).'
                        : 'Rincian sparepart yang diterima masuk ke gudang.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->addActionLabel('Tambah Barang Masuk Lainnya')
                            ->relationship()
                            ->minItems(1)
                            ->required()
                            ->disabled(fn (?GoodsReceipt $record) => $record?->status === 'RECEIVED')
                            ->dehydrated(fn (?GoodsReceipt $record) => $record?->status !== 'RECEIVED')
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Pilih Produk / Sparepart')
                                    ->options(fn () => Part::withTrashed()->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->part_number} - {$p->name}"]))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $part = Part::withTrashed()->find($state);
                                            if ($part) {
                                                $set('unit', $part->unit ?? 'PCS');
                                                $isOwner = auth()->user()?->isOwner() ?? false;
                                                $set('unit_cost', $isOwner ? (int) round((float) ($part->cost_price ?? 0)) : 0);
                                                $set('bin_location_snapshot', $part->bin_location);
                                            }
                                        }
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                Forms\Components\TextInput::make('qty_received')
                                    ->label('Jumlah Masuk')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->columnSpan(['default' => 6, 'md' => 2]),

                                Forms\Components\TextInput::make('unit')
                                    ->label('Satuan')
                                    ->required()
                                    ->maxLength(20)
                                    ->default('PCS')
                                    ->columnSpan(['default' => 6, 'md' => 2]),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Harga Modal / Beli')
                                    ->prefix('Rp')
                                    ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn (): bool => auth()->user()?->isOwner() ?? false)
                                    ->dehydrated(fn (): bool => auth()->user()?->isOwner() ?? false)
                                    ->columnSpan(['default' => 6, 'md' => 2]),

                                Forms\Components\TextInput::make('bin_location_snapshot')
                                    ->label('Rak Simpan')
                                    ->maxLength(50)
                                    ->columnSpan(['default' => 6, 'md' => 2]),

                                Forms\Components\TextInput::make('notes')
                                    ->label('Keterangan / Kondisi')
                                    ->placeholder('Kondisi fisik, segel kemasan, dll.')
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
                    ->size('sm')
                    ->url(fn (GoodsReceipt $record): string => route('print.goods-receipt', $record))
                    ->openUrlInNewTab(),
                \Filament\Actions\EditAction::make()->label('Detail / Ubah')->size('sm'),
                \Filament\Actions\DeleteAction::make()->label('Hapus')->size('sm'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contact', 'warehouse']);
    }
}
