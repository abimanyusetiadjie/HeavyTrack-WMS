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
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $modelLabel = 'Faktur (Invoice)';
    protected static ?string $pluralModelLabel = 'Daftar Faktur';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Informasi Faktur')
                    ->description('Rincian tagihan kepada pelanggan.')
                    ->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Nomor Faktur')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(function () {
                            $count = Invoice::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                            return 'INV/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        })
                        ->maxLength(50),
                        
                    Forms\Components\Select::make('delivery_order_id')
                        ->label('Berdasarkan Surat Jalan')
                        ->relationship('deliveryOrder', 'do_number')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $do = DeliveryOrder::with(['items.part', 'contact'])->find($state);
                                if ($do) {
                                    $set('contact_id', $do->contact_id);
                                    
                                    $items = [];
                                    $subtotal = 0;
                                    foreach ($do->items as $item) {
                                        $price = $item->part->sale_price ?? 0;
                                        $total = $item->qty * $price;
                                        $subtotal += $total;
                                        
                                        $items[] = [
                                            'part_id' => $item->part_id,
                                            'part_number_snapshot' => $item->part->part_number,
                                            'part_name_snapshot' => $item->part->name,
                                            'qty' => $item->qty,
                                            'unit' => $item->unit,
                                            'unit_price' => $price,
                                            'total_price' => $total,
                                            'discount_percent' => 0
                                        ];
                                    }
                                    $set('items', $items);
                                    $set('subtotal', $subtotal);
                                    $set('tax_amount', $subtotal * 0.11);
                                    $set('grand_total', $subtotal + ($subtotal * 0.11));
                                }
                            }
                        }),
                        
                    Forms\Components\Select::make('contact_id')
                        ->label('Ditagihkan Kepada (Klien)')
                        ->relationship('contact', 'company_name')->createOptionForm([ 
                            \Filament\Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan/Orang')->required()->maxLength(255), 
                            \Filament\Forms\Components\TextInput::make('contact_person')->label('Nama Kontak')->maxLength(255), 
                            \Filament\Forms\Components\TextInput::make('phone')->label('Telepon')->tel()->maxLength(50), 
                            \Filament\Forms\Components\Textarea::make('address')->label('Alamat')->maxLength(500), 
                        ])
                        ->required()
                        ->searchable(),
                        
                    Forms\Components\DatePicker::make('invoice_date')
                        ->label('Tanggal Faktur Terbit')
                        ->required()
                        ->default(now()),
                        
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Jatuh Tempo (Due Date)')
                        ->required()
                        ->default(now()->addDays(30)),
                        
                    Forms\Components\Select::make('status')
                        ->label('Status Pembayaran')
                        ->options([
                            'DRAFT' => 'DRAFT',
                            'UNPAID' => 'BELUM DIBAYAR',
                            'PARTIALLY_PAID' => 'DIBAYAR SEBAGIAN',
                            'PAID' => 'LUNAS',
                            'VOID' => 'DIBATALKAN',
                        ])
                        ->default('UNPAID')
                        ->required(),
                        
                    Forms\Components\Hidden::make('created_by')
                        ->default(auth()->id() ?? 1),
                ])->columns(2),

                \Filament\Schemas\Components\Section::make('Rincian Tagihan (Items)')
                    ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Barang yang Ditagihkan')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('part_id')
                                ->label('Pilih Produk/Part')
                                ->options(Part::query()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state) {
                                        $part = Part::find($state);
                                        $set('part_number_snapshot', $part->part_number);
                                        $set('part_name_snapshot', $part->name);
                                        $set('unit', $part->unit);
                                        $set('unit_price', $part->sale_price ?? 0);
                                        $qty = $get('qty') ?? 1;
                                        $set('qty', $qty);
                                        $set('total_price', $qty * ($part->sale_price ?? 0));
                                    }
                                })
                                ->columnSpan(3),
                                
                            Forms\Components\Hidden::make('part_number_snapshot'),
                            Forms\Components\Hidden::make('part_name_snapshot'),
                            
                            Forms\Components\TextInput::make('qty')
                                ->label('Kuantitas (Qty)')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('total_price', $state * ($get('unit_price') ?? 0)))
                                ->columnSpan(1),
                                
                            Forms\Components\TextInput::make('unit')
                                ->label('Satuan')
                                ->required()
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('total_price', $state * ($get('qty') ?? 0)))
                                ->columnSpan(2),
                                
                            Forms\Components\TextInput::make('discount_percent')
                                ->label('Diskon (%)')
                                ->default(0)->numeric()
                                ->columnSpan(1),
                                
                            Forms\Components\TextInput::make('total_price')
                                ->label('Total Harga')
                                ->required()
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),
                        ])->columns(10)
                ])->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),
                
                \Filament\Schemas\Components\Section::make('Total & Kalkulasi')
                    ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->label('Subtotal (Sebelum Pajak)')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Potongan Harga (Rp)')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('tax_percent')
                        ->label('PPN (%)')
                        ->numeric()
                        ->default(11),
                    Forms\Components\TextInput::make('tax_amount')
                        ->label('Total Pajak (Rp)')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('grand_total')
                        ->label('GRAND TOTAL TAGIHAN')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('paid_amount')
                        ->label('Sudah Dibayar (Rp)')
                        ->disabled()
                        ->dehydrated(false)
                        ->numeric()
                        ->default(0),
                ])->columns(3)->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),
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
                    ->label('Cetak Faktur (Invoice)')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Invoice $record): string => route('print.invoice', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                    
                \Filament\Actions\Action::make('printKwitansi')
                    ->label('Cetak Kwitansi (Receipt)')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (Invoice $record): string => route('print.receipt', ['invoice' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->paid_amount > 0),
                    
                \Filament\Actions\Action::make('recordPayment')
                    ->label('Input Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')->label('Tanggal Bayar')->required()->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options(['CASH' => 'Tunai (Cash)', 'BANK_TRANSFER' => 'Transfer Bank', 'GIRO' => 'Cek / Giro'])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal yang Dibayarkan')
                            ->required()->numeric()
                            ->default(fn (Invoice $record) => $record->grand_total - $record->paid_amount),
                        Forms\Components\TextInput::make('reference_number')->label('Nomor Referensi/Bukti'),
                        Forms\Components\Textarea::make('notes')->label('Catatan'),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        Payment::create([
                            'receipt_number' => 'BKM/' . date('Ym') . '/' . rand(1000, 9999),
                            'invoice_id' => $record->id,
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'amount' => $data['amount'],
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
