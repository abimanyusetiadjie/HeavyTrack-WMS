<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';
    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Kontak (Relasi)';
    protected static ?string $pluralModelLabel = 'Pelanggan & Pemasok';
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
            ->schema([
                Section::make('Informasi Relasi Bisnis')
                    ->description('Data profil pelanggan (customer) maupun pemasok (supplier).')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Nama Perusahaan / Toko / Perorangan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Jenis Kontak')
                            ->options([
                                'CUSTOMER' => 'Pelanggan (Customer)',
                                'SUPPLIER' => 'Pemasok (Supplier / Vendor)',
                                'BOTH' => 'Keduanya (Pelanggan & Pemasok)',
                            ])
                            ->default('CUSTOMER')
                            ->required(),

                        Forms\Components\TextInput::make('pic_name')
                            ->label('Nama PIC / Kontak Person')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('tax_number_npwp')
                            ->label('Nomor NPWP')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('term_of_payment_days')
                            ->label('Termin Pembayaran (Hari)')
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = Tunai / Cash saat transaksi.'),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Nama Relasi / Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'success' => 'CUSTOMER',
                        'info' => 'SUPPLIER',
                        'warning' => 'BOTH',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'CUSTOMER' => 'Pelanggan',
                        'SUPPLIER' => 'Pemasok',
                        'BOTH' => 'Keduanya',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('pic_name')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon / WA')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('term_of_payment_days')
                    ->label('Termin')
                    ->suffix(' Hari')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Data Dihapus'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter Jenis Kontak')
                    ->options([
                        'CUSTOMER' => 'Pelanggan',
                        'SUPPLIER' => 'Pemasok',
                        'BOTH' => 'Keduanya',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Ubah'),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
