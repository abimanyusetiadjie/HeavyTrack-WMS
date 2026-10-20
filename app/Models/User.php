<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Izinkan semua user yang ada di DB untuk masuk ke panel admin
        return true; 
    }

    /**
     * Memeriksa apakah user adalah Owner (Pemilik Toko).
     */
    public function isOwner(): bool
    {
        $emailLower = strtolower($this->email ?? '');
        $nameLower = strtolower($this->name ?? '');
        $usernameLower = strtolower($this->username ?? '');

        // Akun adminheavytruck secara eksplisit BUKAN Owner (role Admin)
        if (str_contains($emailLower, 'adminheavytruck') || str_contains($nameLower, 'adminheavytruck') || str_contains($usernameLower, 'adminheavytruck')) {
            return false;
        }

        // Cek apakah memiliki role Owner dari Spatie
        if ($this->hasRole('Owner')) {
            return true;
        }

        // Cek jika email, nama, atau username mengandung kata owner
        if (str_contains($emailLower, 'owner') || str_contains($nameLower, 'owner') || str_contains($usernameLower, 'owner')) {
            return true;
        }

        return false;
    }

    /**
     * Memeriksa apakah user adalah Admin (Kasir / Operasional).
     */
    public function isAdmin(): bool
    {
        return ! $this->isOwner();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
