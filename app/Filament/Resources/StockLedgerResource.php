<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockLedgerResource\Pages;
use App\Models\StockLedger;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockLedgerResource extends Resource
{
    protected static ?string $model = StockLedger::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Gudang';
    protected static ?string $modelLabel = 'Buku Mutasi Stok (Kartu Stok)';
    protected static ?string $pluralModelLabel = 'Buku Mutasi Stok';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->isOwner() || $user->isAdmin();
    }

    public static function canCreate(): bool
    {
        return false; // Buku mutasi dicatat otomatis oleh sistem, tidak bisa diinput manual sembarangan
    }

    public static function canEdit($record): bool
    {
        return false; // Jejak audit tidak boleh diubah
    }

    public static function canDelete($record): bool
    {
        return false; // Jejak audit tidak boleh dihapus
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Mutasi')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('part.part_number')
                    ->label('Nomor Part')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('part.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Jenis Transaksi')
                    ->badge()
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['GOODS_RECEIPT', 'INITIAL_STOCK', 'DELIVERY_ORDER_VOID']),
                        'danger' => fn ($state): bool => in_array($state, ['DELIVERY_ORDER', 'GOODS_RECEIPT_VOID']),
                        'warning' => fn ($state): bool => in_array($state, ['STOCK_ADJUSTMENT', 'MANUAL_ADJUSTMENT']),
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'GOODS_RECEIPT' => 'Barang Masuk (LPB)',
                        'GOODS_RECEIPT_VOID' => 'Batal Masuk (Stok Ditarik)',
                        'DELIVERY_ORDER' => 'Surat Jalan (DO)',
                        'DELIVERY_ORDER_VOID' => 'Batal Kirim (Stok Kembali)',
                        'STOCK_ADJUSTMENT', 'MANUAL_ADJUSTMENT' => 'Penyesuaian Opname',
                        'INITIAL_STOCK' => 'Saldo Awal',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No. Dokumen')
                    ->fontFamily('mono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('qty_change')
                    ->label('Mutasi')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Saldo Awal')
                    ->numeric(),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Sisa Saldo')
                    ->weight('bold')
                    ->numeric(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(35)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Operator')
                    ->placeholder('Sistem'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Filter Gudang')
                    ->relationship('warehouse', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->label('Filter Transaksi')
                    ->options([
                        'GOODS_RECEIPT' => 'Barang Masuk (LPB)',
                        'DELIVERY_ORDER' => 'Surat Jalan (DO)',
                        'STOCK_ADJUSTMENT' => 'Penyesuaian Opname',
                        'INITIAL_STOCK' => 'Saldo Awal',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLedgers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['part', 'warehouse', 'creator']);
    }
}
