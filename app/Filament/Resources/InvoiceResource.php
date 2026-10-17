<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\DeliveryOrder;
use App\Models\Part;
use App\Models\Payment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static \UnitEnum|string|null $navigationGroup = 'Keuangan & Penjualan';
    protected static ?string $modelLabel = 'Faktur (Invoice)';
    protected static ?string $pluralModelLabel = 'Daftar Faktur';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->hasRole('Admin') || $user->hasRole('Kasir') || $user->id === 1;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('Admin') || $user->hasRole('Kasir') || $user->id === 1);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('Admin') || $user->hasRole('Kasir') || $user->id === 1);
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
                Section::make('Informasi Faktur & Pelanggan')
                    ->description('Detail invoice, pelanggan yang ditagih, dan tanggal jatuh tempo.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Faktur')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(function () {
                                $count = Invoice::withTrashed()->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                                return 'INV/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                            })
                            ->maxLength(50),
                            
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Tanggal Terbit')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Batas Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(30)),

                        Forms\Components\Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'DRAFT' => 'DRAFT (Konsep)',
                                'UNPAID' => 'BELUM DIBAYAR',
                                'PARTIALLY_PAID' => 'DIBAYAR SEBAGIAN',
                                'PAID' => 'LUNAS',
                                'VOID' => 'DIBATALKAN',
                            ])
                            ->default('UNPAID')
                            ->required(),

                        Forms\Components\Select::make('delivery_order_id')
                            ->label('Tarik Data dari Surat Jalan (Otomatis)')
                            ->relationship('deliveryOrder', 'do_number')
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $do = DeliveryOrder::with(['items.part', 'contact'])->find($state);
                                    if ($do) {
                                        $set('contact_id', $do->contact_id);
                                        
                                        $items = [];
                                        $subtotal = 0;
                                        foreach ($do->items as $item) {
                                            $price = round((float) ($item->part->selling_price ?? 0));
                                            $total = round($item->qty * $price);
                                            $subtotal += $total;
                                            
                                            $items[] = [
                                                'part_id' => $item->part_id,
                                                'part_number_snapshot' => $item->part->part_number,
                                                'part_name_snapshot' => $item->part->name,
                                                'qty' => $item->qty,
                                                'unit' => $item->unit,
                                                'unit_price' => self::formatMoney($price),
                                                'total_price' => self::formatMoney($total),
                                                'discount_percent' => 0
                                            ];
                                        }
                                        $set('items', $items);
                                        $discount = self::parseMoney($get('discount_amount'));
                                        $taxPercent = (float) ($get('tax_percent') ?? 11);
                                        $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
                                        $set('subtotal', self::formatMoney($subtotal));
                                        $set('tax_amount', self::formatMoney($tax));
                                        $set('grand_total', self::formatMoney(max(0, $subtotal - $discount + $tax)));
                                    }
                                }
                            }),
                            
                        Forms\Components\Select::make('contact_id')
                            ->label('Ditagihkan Kepada (Klien)')
                            ->relationship('contact', 'company_name', fn ($query) => $query->whereIn('type', ['CUSTOMER', 'BOTH']))
                            ->createOptionForm([ 
                                Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan / Pembeli')->required()->maxLength(255), 
                                Forms\Components\Hidden::make('type')->default('CUSTOMER'),
                                Forms\Components\TextInput::make('pic_name')->label('Nama Kontak / PIC')->maxLength(100), 
                                Forms\Components\TextInput::make('phone')->label('Telepon / WA')->tel()->maxLength(50), 
                                Forms\Components\Textarea::make('address')->label('Alamat')->maxLength(500), 
                            ])
                            ->required()
                            ->searchable(),
                    ])->columns(['default' => 1, 'md' => 2, 'lg' => 3]),

                Section::make('Rincian Tagihan (Items)')
                    ->description('Daftar barang dan harga jual yang ditagihkan kepada pelanggan.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->addActionLabel('Tambah Tagihan Lainnya')
                            ->relationship()
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => self::recalculateTotalsFromRoot($set, $get))
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Pilih Produk / Sparepart')
                                    ->options(Part::query()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $part = Part::find($state);
                                            if ($part) {
                                                $price = round((float) ($part->selling_price ?? 0));
                                                $set('part_number_snapshot', $part->part_number);
                                                $set('part_name_snapshot', $part->name);
                                                $set('unit', $part->unit);
                                                $set('unit_price', self::formatMoney($price));
                                                $qty = (float) ($get('qty') ?? 1);
                                                $set('qty', $qty);
                                                $disc = (float) ($get('discount_percent') ?? 0);
                                                $lineTotal = round($qty * $price * (1 - ($disc / 100)));
                                                $set('total_price', self::formatMoney($lineTotal));
                                                self::recalculateTotalsFromRepeaterItem($set, $get);
                                            }
                                        }
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                    
                                Forms\Components\Hidden::make('part_number_snapshot'),
                                Forms\Components\Hidden::make('part_name_snapshot'),
                                
                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = (float) ($state ?? 0);
                                        $price = self::parseMoney($get('unit_price'));
                                        $disc = (float) ($get('discount_percent') ?? 0);
                                        $set('total_price', self::formatMoney(round($qty * $price * (1 - ($disc / 100)))));
                                        self::recalculateTotalsFromRepeaterItem($set, $get);
                                    })
                                    ->columnSpan(['default' => 6, 'md' => 1]),
                                    
                                Forms\Components\TextInput::make('unit')
                                    ->label('Satuan')
                                    ->required()
                                    ->columnSpan(['default' => 6, 'md' => 1]),
                                
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->prefix('Rp')
                                    ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->required()
                                    ->numeric()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = (float) ($get('qty') ?? 0);
                                        $price = self::parseMoney($state);
                                        $disc = (float) ($get('discount_percent') ?? 0);
                                        $set('total_price', self::formatMoney(round($qty * $price * (1 - ($disc / 100)))));
                                        self::recalculateTotalsFromRepeaterItem($set, $get);
                                    })
                                    ->columnSpan(['default' => 6, 'md' => 2]),
                                    
                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Diskon')
                                    ->suffix('%')
                                    ->default(0)
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = (float) ($get('qty') ?? 0);
                                        $price = self::parseMoney($get('unit_price'));
                                        $disc = (float) ($state ?? 0);
                                        $set('total_price', self::formatMoney(round($qty * $price * (1 - ($disc / 100)))));
                                        self::recalculateTotalsFromRepeaterItem($set, $get);
                                    })
                                    ->columnSpan(['default' => 6, 'md' => 1]),
                                    
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total Harga')
                                    ->prefix('Rp')
                                    ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->required()
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(['default' => 12, 'md' => 3]),
                            ])->columns(['default' => 12, 'md' => 12])
                    ])->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),

                Section::make('Total & Kalkulasi Akhir')
                    ->description('Ringkasan nilai transaksi, potongan, pajak PPN, dan grand total.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 400)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = self::parseMoney($state);
                                $taxPercent = (float) ($get('tax_percent') ?? 11);
                                $discount = self::parseMoney($get('discount_amount'));
                                $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
                                $set('tax_amount', self::formatMoney($tax));
                                $set('grand_total', self::formatMoney(max(0, $subtotal - $discount + $tax)));
                            })
                            ->columnSpan(['default' => 12, 'sm' => 6, 'md' => 3]),
                            
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Potongan / Diskon')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 400)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = self::parseMoney($get('subtotal'));
                                $taxPercent = (float) ($get('tax_percent') ?? 11);
                                $discount = self::parseMoney($state);
                                $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
                                $set('tax_amount', self::formatMoney($tax));
                                $set('grand_total', self::formatMoney(max(0, $subtotal - $discount + $tax)));
                            })
                            ->columnSpan(['default' => 12, 'sm' => 6, 'md' => 3]),

                        Forms\Components\TextInput::make('tax_percent')
                            ->label('PPN')
                            ->suffix('%')
                            ->numeric()
                            ->default(11)
                            ->live(debounce: 400)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = self::parseMoney($get('subtotal'));
                                $discount = self::parseMoney($get('discount_amount'));
                                $tax = round(max(0, $subtotal - $discount) * ((float) ($state ?? 0) / 100));
                                $set('tax_amount', self::formatMoney($tax));
                                $set('grand_total', self::formatMoney(max(0, $subtotal - $discount + $tax)));
                            })
                            ->columnSpan(['default' => 6, 'sm' => 3, 'md' => 2]),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Nilai PPN (Pajak)')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(['default' => 6, 'sm' => 3, 'md' => 4]),
                            
                        Forms\Components\TextInput::make('grand_total')
                            ->label('TOTAL TAGIHAN (GRAND TOTAL)')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->extraAttributes(['class' => 'text-xl font-extrabold text-amber-600'])
                            ->columnSpan(['default' => 12, 'md' => 8]),
                            
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Sisa / Terbayar')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->disabled()
                            ->dehydrated(false)
                            ->numeric()
                            ->default(0)
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id() ?? 1),
                    ])
                    ->columns(['default' => 12, 'md' => 12])
                    ->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Nomor Faktur')->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('Tgl Terbit')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('contact.company_name')->label('Pelanggan'),
                Tables\Columns\TextColumn::make('grand_total')->label('Total Tagihan')->money('IDR'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'info' => 'DRAFT',
                        'danger' => 'UNPAID',
                        'warning' => 'PARTIALLY_PAID',
                        'success' => 'PAID',
                        'secondary' => 'VOID',
                    ]),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Ubah'),
                
                \Filament\Actions\Action::make('printFaktur')
                    ->label('Cetak Faktur')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Invoice $record): string => route('print.invoice', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                    
                \Filament\Actions\Action::make('printKwitansi')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->url(fn (Invoice $record): string => route('print.receipt', ['invoice' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->paid_amount > 0),
                    
                \Filament\Actions\Action::make('recordPayment')
                    ->label('Input Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')->label('Tanggal Bayar')->required()->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options(['CASH' => 'Tunai (Cash)', 'BANK_TRANSFER' => 'Transfer Bank', 'GIRO' => 'Cek / Giro'])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Pembayaran')
                            ->prefix('Rp')
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->required()
                            ->numeric()
                            ->default(fn (Invoice $record) => self::formatMoney(max(0, $record->grand_total - $record->paid_amount))),
                        Forms\Components\TextInput::make('reference_number')->label('No Referensi/Bukti'),
                        Forms\Components\Textarea::make('notes')->label('Catatan'),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $nextPaymentSeq = Payment::whereMonth('created_at', date('m'))
                            ->whereYear('created_at', date('Y'))
                            ->count() + 1;

                        Payment::create([
                            'receipt_number' => 'BKM/' . date('Ym') . '/' . str_pad($nextPaymentSeq, 4, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -3),
                            'invoice_id' => $record->id,
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'amount' => self::parseMoney($data['amount']),
                            'reference_number' => $data['reference_number'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id() ?? 1,
                        ]);
                        
                        $totalPaid = $record->payments()->sum('amount');
                        $record->paid_amount = $totalPaid;
                        
                        if ($totalPaid >= $record->grand_total) {
                            $record->status = 'PAID';
                        } elseif ($totalPaid > 0) {
                            $record->status = 'PARTIALLY_PAID';
                        }
                        
                        $record->save();
                    })
                    ->visible(fn (Invoice $record): bool => $record->status !== 'PAID' && $record->status !== 'VOID'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ])->label('Aksi Masal'),
            ]);
    }

    protected static function parseMoney(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $cleaned = str_replace('.', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);
        return (float) $cleaned;
    }

    protected static function formatMoney(float | int $value): string
    {
        return number_format(round($value), 0, ',', '.');
    }

    protected static function recalculateTotalsFromRoot(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = self::parseMoney($item['unit_price'] ?? 0);
            $disc = (float) ($item['discount_percent'] ?? 0);
            $subtotal += round($qty * $price * (1 - ($disc / 100)));
        }

        $discount = self::parseMoney($get('discount_amount'));
        $taxPercent = (float) ($get('tax_percent') ?? 11);
        $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
        $grandTotal = round(max(0, $subtotal - $discount + $tax));

        $set('subtotal', self::formatMoney($subtotal));
        $set('tax_amount', self::formatMoney($tax));
        $set('grand_total', self::formatMoney($grandTotal));
    }

    protected static function recalculateTotalsFromRepeaterItem(callable $set, callable $get): void
    {
        $items = $get('../../items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = self::parseMoney($item['unit_price'] ?? 0);
            $disc = (float) ($item['discount_percent'] ?? 0);
            $subtotal += round($qty * $price * (1 - ($disc / 100)));
        }

        $discount = self::parseMoney($get('../../discount_amount'));
        $taxPercent = (float) ($get('../../tax_percent') ?? 11);
        $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
        $grandTotal = round(max(0, $subtotal - $discount + $tax));

        $set('../../subtotal', self::formatMoney($subtotal));
        $set('../../tax_amount', self::formatMoney($tax));
        $set('../../grand_total', self::formatMoney($grandTotal));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
