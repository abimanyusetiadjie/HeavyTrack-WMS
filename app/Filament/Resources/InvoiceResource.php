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

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Invoice (Faktur)';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('General Information')->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(function () {
                            $count = Invoice::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
                            return 'INV/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        })
                        ->maxLength(50),
                    
                    Forms\Components\Select::make('delivery_order_id')
                        ->relationship('deliveryOrder', 'do_number')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;
                            $do = DeliveryOrder::with('items.part')->find($state);
                            if ($do) {
                                $set('contact_id', $do->contact_id);
                                
                                $items = [];
                                $subtotal = 0;
                                foreach($do->items as $item) {
                                    $price = $item->part->selling_price ?? 0;
                                    $total = $item->qty * $price;
                                    $subtotal += $total;
                                    
                                    $items[] = [
                                        'part_id' => $item->part_id,
                                        'part_number_snapshot' => $item->part->part_number ?? '-',
                                        'part_name_snapshot' => $item->part->name ?? '-',
                                        'qty' => $item->qty,
                                        'unit' => $item->unit,
                                        'unit_price' => $price,
                                        'discount_percent' => 0,
                                        'total_price' => $total,
                                    ];
                                }
                                $set('items', $items);
                                $set('subtotal', $subtotal);
                                
                                // Recalculate Grand Total
                                $discount = 0;
                                $taxPercent = 11;
                                $taxAmount = ($subtotal - $discount) * ($taxPercent / 100);
                                $set('tax_amount', $taxAmount);
                                $set('grand_total', $subtotal - $discount + $taxAmount);
                            }
                        }),
                        
                    Forms\Components\Select::make('contact_id')
                        ->relationship('contact', 'company_name')->createOptionForm([ \Filament\Forms\Components\TextInput::make('company_name')->required()->maxLength(255), \Filament\Forms\Components\TextInput::make('contact_person')->maxLength(255), \Filament\Forms\Components\TextInput::make('phone')->tel()->maxLength(50), \Filament\Forms\Components\Textarea::make('address')->maxLength(500), ])
                        ->required()
                        ->searchable(),
                        
                    Forms\Components\DatePicker::make('invoice_date')
                        ->required()
                        ->default(now()),
                        
                    Forms\Components\DatePicker::make('due_date')
                        ->required()
                        ->default(now()->addDays(14)),
                        
                    Forms\Components\Select::make('status')
                        ->options([
                            'UNPAID' => 'UNPAID',
                            'PARTIALLY_PAID' => 'PARTIALLY_PAID',
                            'PAID' => 'PAID',
                            'VOID' => 'VOID',
                        ])
                        ->default('UNPAID')
                        ->required(),
                        
                    Forms\Components\Hidden::make('created_by')
                        ->default(auth()->id() ?? 1),
                ])->columns(2)->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('part_id')
                                ->options(Part::query()->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $part = Part::find($state);
                                    if ($part) {
                                        $set('part_number_snapshot', $part->part_number);
                                        $set('part_name_snapshot', $part->name);
                                        $set('unit', $part->unit);
                                        $set('unit_price', $part->selling_price);
                                        $qty = $get('qty') ?? 1;
                                        $set('qty', $qty);
                                        $set('total_price', $qty * $part->selling_price);
                                    }
                                }),
                                
                            Forms\Components\Hidden::make('part_number_snapshot'),
                            Forms\Components\Hidden::make('part_name_snapshot'),
                            
                            Forms\Components\TextInput::make('qty')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('total_price', $state * ($get('unit_price') ?? 0))),
                                
                            Forms\Components\TextInput::make('unit')->required(),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('total_price', $state * ($get('qty') ?? 0))),
                                
                            Forms\Components\TextInput::make('discount_percent')
                                ->default(0)->numeric(),
                                
                            Forms\Components\TextInput::make('total_price')
                                ->required()
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                        ])->columns(5)
                ])->disabled(fn (?Invoice $record) => $record?->status === 'PAID'),
                
                Forms\Components\Section::make('Totals')->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('discount_amount')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('tax_percent')
                        ->numeric()
                        ->default(11),
                    Forms\Components\TextInput::make('tax_amount')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('grand_total')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('paid_amount')
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
                Tables\Columns\TextColumn::make('invoice_number')->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('contact.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('grand_total')->money('IDR'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger' => 'UNPAID',
                        'warning' => 'PARTIALLY_PAID',
                        'success' => 'PAID',
                        'secondary' => 'VOID',
                    ]),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                
                \Filament\Actions\Action::make('printFaktur')
                    ->label('Cetak Faktur')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Invoice $record): string => route('print.invoice', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                    
                \Filament\Actions\Action::make('printKwitansi')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (Invoice $record): string => route('print.receipt', ['invoice' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->paid_amount > 0),
                    
                \Filament\Actions\Action::make('recordPayment')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')->required()->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->options(['CASH' => 'CASH', 'BANK_TRANSFER' => 'BANK_TRANSFER', 'GIRO' => 'GIRO'])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->required()->numeric()
                            ->default(fn (Invoice $record) => $record->grand_total - $record->paid_amount),
                        Forms\Components\TextInput::make('reference_number'),
                        Forms\Components\Textarea::make('notes'),
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
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
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
