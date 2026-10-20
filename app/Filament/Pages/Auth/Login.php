<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Schema;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Username atau Nama / Email')
            ->placeholder('Contoh: owner atau admin')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * Cari kredensial user berdasarkan Username, Nama, atau Email
     * agar user awam cukup mengetik nama pengguna saja (misal: owner / admin).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim($data['email']);

        // Cari user yang cocok dengan username, name, atau email (case-insensitive)
        $user = User::where(function ($query) use ($login) {
            $query->where('email', $login)
                  ->orWhere('name', $login);

            if (Schema::hasColumn('users', 'username')) {
                $query->orWhere('username', $login);
            }
        })->first();

        return [
            'email' => $user?->email ?? $login,
            'password' => $data['password'],
        ];
    }
}
