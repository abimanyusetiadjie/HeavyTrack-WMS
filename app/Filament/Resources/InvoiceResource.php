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
use Illuminate\Database\Eloquent\Builder;

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
                Section::make('Informasi Faktur & Pelanggan')
                    ->description('Detail invoice, pelanggan yang ditagih, dan tanggal jatuh tempo.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Faktur')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state)
                            ->default(function () {
                                $prefix = 'INV/' . date('Ym') . '/';
                                $latest = Invoice::withTrashed()
                                    ->where('invoice_number', 'like', $prefix . '%')
                                    ->orderByDesc('invoice_number')
                                    ->value('invoice_number');

                                $nextSeq = 1;
                                if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                                    $nextSeq = (int) $matches[1] + 1;
                                } else {
                                    $nextSeq = Invoice::withTrashed()
                                        ->whereMonth('created_at', date('m'))
                                        ->whereYear('created_at', date('Y'))
                                        ->count() + 1;
                                }

                                return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
                            })
                            ->maxLength(50),
                            
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Tanggal Terbit')
                            ->required()
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $contactId = $get('contact_id');
                                if ($contactId && $state) {
                                    $contact = \App\Models\Contact::find($contactId);
                                    $days = $contact?->term_of_payment_days ?: 30;
                                    $set('due_date', \Carbon\Carbon::parse($state)->addDays((int) $days)->format('Y-m-d'));
                                }
                            }),
                            
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
                            ->relationship('deliveryOrder', 'do_number', function (Builder $query, ?Invoice $record) {
                                $query->whereIn('status', ['CONFIRMED', 'ISSUED']);
                                if ($record && $record->delivery_order_id) {
                                    $query->where(function ($q) use ($record) {
                                        $q->doesntHave('invoices')
                                          ->orWhere('id', $record->delivery_order_id);
                                    });
                                } else {
                                    $query->doesntHave('invoices');
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $do = DeliveryOrder::with(['items.part' => fn ($q) => $q->withTrashed(), 'contact'])->find($state);
                                    if ($do) {
                                        $set('contact_id', $do->contact_id);

                                        if ($do->contact?->term_of_payment_days) {
                                            $invDate = $get('invoice_date') ? \Carbon\Carbon::parse($get('invoice_date')) : now();
                                            $set('due_date', $invDate->copy()->addDays((int) $do->contact->term_of_payment_days)->format('Y-m-d'));
                                        }
                                        
                                        $items = [];
                                        $subtotal = 0;
                                        foreach ($do->items as $item) {
                                            $price = round((float) ($item->part?->selling_price ?? 0));
                                            $total = round($item->qty * $price);
                                            $subtotal += $total;
                                            
                                            $items[] = [
                                                'part_id' => $item->part_id,
                                                'part_number_snapshot' => mb_strtoupper($item->part?->part_number ?? '-', 'UTF-8'),
                                                'part_name_snapshot' => mb_strtoupper($item->part?->name ?? 'Sparepart', 'UTF-8'),
                                                'qty' => $item->qty,
                                                'unit' => mb_strtoupper($item->unit ?: ($item->part?->unit ?? 'PCS'), 'UTF-8'),
                                                'unit_price' => self::formatMoney($price),
                                                'total_price' => self::formatMoney($total),
                                                'discount_percent' => 0
                                            ];
                                        }
                                        $set('items', $items);
                                        $discount = self::parseMoney($get('discount_amount'));
                                        $taxPercent = (float) ($get('tax_percent') ?? 0);
                                        $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
                                        $set('subtotal', self::formatMoney($subtotal));
                                        $set('tax_amount', self::formatMoney($tax));
                                        $set('grand_total', self::formatMoney(max(0, $subtotal - $discount + $tax)));

                                        if (empty($items)) {
                                            \Filament\Notifications\Notification::make()
                                                ->warning()
                                                ->title('Surat Jalan Belum Memiliki Barang')
                                                ->body("Surat Jalan {$do->do_number} belum memiliki rincian barang. Silakan isi barang pada Surat Jalan atau tambahkan rincian secara manual di bawah.")
                                                ->send();
                                        }
                                    }
                                }
                            }),
                            
                        Forms\Components\Select::make('contact_id')
                            ->label('Ditagihkan Kepada (Klien)')
                            ->relationship('contact', 'company_name', fn ($query) => $query->whereIn('type', ['CUSTOMER', 'BOTH'])->orderBy('company_name'))
                            ->createOptionForm([ 
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Nama Perusahaan / Pembeli')
                                    ->required()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state), 
                                Forms\Components\Hidden::make('type')->default('CUSTOMER'),
                                Forms\Components\TextInput::make('pic_name')
                                    ->label('Nama Kontak / PIC')
                                    ->maxLength(100)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state), 
                                Forms\Components\TextInput::make('phone')->label('Telepon / WA')->tel()->maxLength(50), 
                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat')
                                    ->maxLength(500)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state), 
                            ])
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $contact = \App\Models\Contact::find($state);
                                    if ($contact && $contact->term_of_payment_days) {
                                        $invDate = $get('invoice_date') ? \Carbon\Carbon::parse($get('invoice_date')) : now();
                                        $set('due_date', $invDate->copy()->addDays((int) $contact->term_of_payment_days)->format('Y-m-d'));
                                    }
                                }
                            }),
                    ])->columns(['default' => 1, 'md' => 2, 'lg' => 3]),

                Section::make('Rincian Tagihan (Items)')
                    ->description('Daftar barang dan harga jual yang ditagihkan kepada pelanggan.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->addActionLabel('Tambah Tagihan Lainnya')
                            ->relationship()
                            ->minItems(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => self::recalculateTotalsFromRoot($set, $get))
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Pilih Produk / Sparepart')
                                    ->placeholder('Ketik nomor part, nama, atau merek...')
                                    ->options(function (callable $get) {
                                        $currentPartId = $get('part_id');

                                        $parts = Part::with(['brand:id,name', 'stockBalances'])
                                            ->where(function (Builder $query) use ($currentPartId) {
                                                $query->where('is_active', true);
                                                if ($currentPartId) {
                                                    $query->orWhere('id', $currentPartId);
                                                }
                                            })
                                            ->orderBy(\App\Models\Brand::select('name')->whereColumn('brands.id', 'parts.brand_id'), 'asc')
                                            ->orderBy('part_number', 'asc')
                                            ->get();

                                        $grouped = [];
                                        foreach ($parts as $p) {
                                            $brandName = mb_strtoupper($p->brand?->name ?? 'LAIN-LAIN', 'UTF-8');
                                            $stockQty = (int) $p->stockBalances->sum('qty_on_hand');
                                            $unit = $p->unit ?? 'PCS';
                                            $stockText = $stockQty > 0 ? "Stok: {$stockQty} {$unit}" : "Stok: 0 (KOSONG)";
                                            $priceText = (float) $p->selling_price > 0 ? \App\Filament\Resources\ProductResource::formatPriceDisplay($p->selling_price) : '-';
                                            $brandLabel = $p->brand?->name ? " [{$p->brand->name}]" : '';
                                            $label = "{$p->part_number} — {$p->name}{$brandLabel} [{$priceText}] [{$stockText}]";
                                            if (! $p->is_active) {
                                                $label .= ' (Non-Aktif)';
                                            }
                                            $grouped[$brandName][$p->id] = $label;
                                        }

                                        return $grouped;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $part = Part::withTrashed()->find($state);
                                            if ($part) {
                                                $price = round((float) ($part->selling_price ?? 0));
                                                $set('part_number_snapshot', mb_strtoupper($part->part_number ?? '-', 'UTF-8'));
                                                $set('part_name_snapshot', mb_strtoupper($part->name ?? 'Sparepart', 'UTF-8'));
                                                $set('unit', mb_strtoupper($part->unit ?? 'PCS', 'UTF-8'));
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
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state)
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
                    ]),

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
                                $taxPercent = (float) ($get('tax_percent') ?? 0);
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
                                $taxPercent = (float) ($get('tax_percent') ?? 0);
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
                            ->default(0)
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
                    ->columns(['default' => 12, 'md' => 12]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Nomor Faktur')->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('Tgl Terbit')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('contact.company_name')->label('Pelanggan'),
                Tables\Columns\TextColumn::make('stock_sync_status')
                    ->label('Surat Jalan & Stok')
                    ->badge()
                    ->state(function (Invoice $record): string {
                        if ($record->status === 'VOID') {
                            return 'Faktur Batal';
                        }
                        if ($record->deliveryOrder) {
                            $doStatus = in_array($record->deliveryOrder->status, ['CONFIRMED', 'ISSUED', 'SHIPPED', 'RECEIVED'], true);
                            return $doStatus
                                ? "{$record->deliveryOrder->do_number} (Stok Dipotong)"
                                : "{$record->deliveryOrder->do_number} ({$record->deliveryOrder->status})";
                        }

                        return 'Tanpa SJ (Stok Belum Dipotong)';
                    })
                    ->color(function (Invoice $record): string {
                        if ($record->status === 'VOID') {
                            return 'gray';
                        }
                        if ($record->deliveryOrder && in_array($record->deliveryOrder->status, ['CONFIRMED', 'ISSUED', 'SHIPPED', 'RECEIVED'], true)) {
                            return 'success';
                        }

                        return 'warning';
                    })
                    ->url(fn (Invoice $record): ?string => $record->deliveryOrder ? route('filament.admin.resources.delivery-orders.edit', ['record' => $record->deliveryOrder->id]) : null),
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
                self::makeIssueDeliveryOrderAction(\Filament\Actions\Action::make('issueDeliveryOrder')),
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
                            ->minValue(1)
                            ->default(fn (Invoice $record) => self::formatMoney(max(0, $record->grand_total - $record->paid_amount))),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('No Referensi/Bukti')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper($state, 'UTF-8') : $state),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $amount = self::parseMoney($data['amount']);
                        if ($amount <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Nominal Tidak Valid')
                                ->body('Nominal pembayaran harus lebih besar dari Rp 0.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $outstanding = max(0, (float) $record->grand_total - (float) $record->paid_amount);
                        if ($amount > $outstanding) {
                            \Filament\Notifications\Notification::make()
                                ->title('Nominal Melebihi Sisa Tagihan')
                                ->body('Nominal pembayaran (Rp ' . number_format($amount, 0, ',', '.') . ') tidak boleh melebihi sisa tagihan (Rp ' . number_format($outstanding, 0, ',', '.') . ').')
                                ->danger()
                                ->send();
                            return;
                        }

                        $prefix = 'BKM/' . date('Ym') . '/';
                        $latest = Payment::withTrashed()
                            ->where('receipt_number', 'like', $prefix . '%')
                            ->orderByDesc('receipt_number')
                            ->value('receipt_number');

                        $nextPaymentSeq = 1;
                        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                            $nextPaymentSeq = (int) $matches[1] + 1;
                        } else {
                            $nextPaymentSeq = Payment::withTrashed()
                                ->whereMonth('created_at', date('m'))
                                ->whereYear('created_at', date('Y'))
                                ->count() + 1;
                        }

                        Payment::create([
                            'receipt_number' => $prefix . str_pad($nextPaymentSeq, 4, '0', STR_PAD_LEFT),
                            'invoice_id' => $record->id,
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'amount' => $amount,
                            'reference_number' => !empty($data['reference_number']) ? mb_strtoupper($data['reference_number'], 'UTF-8') : null,
                            'notes' => !empty($data['notes']) ? mb_strtoupper($data['notes'], 'UTF-8') : null,
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

                        \Filament\Notifications\Notification::make()
                            ->title('Pembayaran Berhasil Dicatat')
                            ->body('Pembayaran sebesar Rp ' . number_format($amount, 0, ',', '.') . ' telah berhasil dibukukan.')
                            ->success()
                            ->send();
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

    protected static function formatMoney(float | int $value): int
    {
        return (int) round($value);
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
        $taxPercent = (float) ($get('tax_percent') ?? 0);
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
        $taxPercent = (float) ($get('../../tax_percent') ?? 0);
        $tax = round(max(0, $subtotal - $discount) * ($taxPercent / 100));
        $grandTotal = round(max(0, $subtotal - $discount + $tax));

        $set('../../subtotal', self::formatMoney($subtotal));
        $set('../../tax_amount', self::formatMoney($tax));
        $set('../../grand_total', self::formatMoney($grandTotal));
    }

    public static function makeIssueDeliveryOrderAction(\Filament\Actions\Action $action): \Filament\Actions\Action
    {
        return $action
            ->label('Buat SJ & Potong Stok')
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->modalHeading('Terbitkan Surat Jalan & Potong Stok dari Faktur')
            ->modalDescription('Faktur ini dibuat tanpa Surat Jalan sehingga belum memotong stok gudang. Pilih gudang asal barang untuk membuat Surat Jalan resmi dan memotong stok secara otomatis.')
            ->form([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Gudang Asal Pengiriman')
                    ->options(fn () => \App\Models\Warehouse::where('is_active', true)->orderBy('id')->pluck('name', 'id'))
                    ->default(function (Invoice $record) {
                        $partIds = $record->items()->pluck('part_id')->filter()->toArray();
                        if (! empty($partIds)) {
                            $bestWhId = \App\Models\StockBalance::whereIn('part_id', $partIds)
                                ->where('qty_on_hand', '>', 0)
                                ->groupBy('warehouse_id')
                                ->orderByRaw('SUM(qty_on_hand) DESC')
                                ->value('warehouse_id');
                            if ($bestWhId) {
                                return $bestWhId;
                            }
                        }

                        return \App\Models\Warehouse::where('code', 'GMA')->value('id')
                            ?? \App\Models\Warehouse::where('is_active', true)->value('id');
                    })
                    ->selectablePlaceholder(false)
                    ->required(),
                Forms\Components\DatePicker::make('delivery_date')
                    ->label('Tanggal Pengiriman')
                    ->default(fn (Invoice $record) => $record->invoice_date ?? now())
                    ->required(),
                Forms\Components\TextInput::make('driver_name')
                    ->label('Nama Supir (Opsional)')
                    ->maxLength(100)
                    ->extraInputAttributes(['style' => 'text-transform: uppercase']),
                Forms\Components\TextInput::make('vehicle_plate_number')
                    ->label('Plat Nomor Kendaraan (Opsional)')
                    ->maxLength(30)
                    ->extraInputAttributes(['style' => 'text-transform: uppercase']),
            ])
            ->visible(fn (Invoice $record): bool => ! $record->deliveryOrder && $record->status !== 'VOID')
            ->action(function (Invoice $record, array $data): void {
                try {
                    $do = self::issueDeliveryOrderFromInvoice($record, $data);

                    \Filament\Notifications\Notification::make()
                        ->title('Surat Jalan Diterbitkan & Stok Dipotong')
                        ->body("Surat Jalan {$do->do_number} berhasil dibuat untuk Faktur {$record->invoice_number}. Saldo stok dan buku mutasi telah terpotong otomatis.")
                        ->success()
                        ->persistent()
                        ->send();
                } catch (\Throwable $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal Menerbitkan Surat Jalan')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    public static function issueDeliveryOrderFromInvoice(Invoice $invoice, array $data): DeliveryOrder
    {
        if ($invoice->deliveryOrder) {
            throw new \Exception("Faktur {$invoice->invoice_number} sudah terhubung dengan Surat Jalan.");
        }

        $invoice->loadMissing('items');
        if ($invoice->items->isEmpty()) {
            throw new \Exception("Faktur {$invoice->invoice_number} tidak memiliki rincian barang.");
        }

        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            throw new \Exception('Gudang asal pengiriman wajib dipilih.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $data, $warehouseId) {
            // 1. Validasi stok per part terlebih dahulu sebelum membuat DO
            $qtyByPart = [];
            foreach ($invoice->items as $item) {
                if (! $item->part_id || (int) $item->qty <= 0) {
                    throw new \Exception("Ada baris barang pada faktur yang tidak valid atau belum terhubung ke Master Produk.");
                }
                $qtyByPart[$item->part_id] = ($qtyByPart[$item->part_id] ?? 0) + (int) $item->qty;
            }

            foreach ($qtyByPart as $partId => $needed) {
                $available = (int) (\App\Models\StockBalance::where('warehouse_id', $warehouseId)
                    ->where('part_id', $partId)
                    ->value('qty_on_hand') ?? 0);

                if ($available < $needed) {
                    $part = Part::withTrashed()->find($partId);
                    $partName = $part ? "{$part->part_number} ({$part->name})" : "Part ID #{$partId}";
                    throw new \Exception("Stok fisik tidak mencukupi untuk {$partName} di gudang terpilih. Tersedia: {$available}, dibutuhkan sesuai faktur: {$needed}.");
                }
            }

            // 2. Generate nomor Surat Jalan berikutnya
            $prefix = 'SJ/' . date('Ym', strtotime((string) ($data['delivery_date'] ?? now()))) . '/';
            $latest = DeliveryOrder::withTrashed()
                ->where('do_number', 'like', $prefix . '%')
                ->orderByDesc('do_number')
                ->value('do_number');

            $nextSeq = 1;
            if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                $nextSeq = (int) $matches[1] + 1;
            }

            $doNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $do = DeliveryOrder::create([
                'do_number' => $doNumber,
                'warehouse_id' => $warehouseId,
                'contact_id' => $invoice->contact_id,
                'delivery_date' => $data['delivery_date'] ?? $invoice->invoice_date ?? now()->toDateString(),
                'driver_name' => $data['driver_name'] ?? null,
                'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
                'notes' => 'DITERBITKAN DARI FAKTUR ' . $invoice->invoice_number,
                'status' => 'CONFIRMED',
                'created_by' => auth()->id() ?? $invoice->created_by ?? 1,
            ]);

            foreach ($invoice->items as $item) {
                \App\Models\DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'part_id' => $item->part_id,
                    'qty' => (int) $item->qty,
                    'unit' => mb_strtoupper($item->unit ?: 'PCS', 'UTF-8'),
                ]);
            }

            $invoice->delivery_order_id = $do->id;
            $invoice->saveQuietly();

            app(\App\Services\InventoryService::class)->syncDeliveryOrderStock($do);

            return $do;
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contact', 'deliveryOrder']);
    }
}
