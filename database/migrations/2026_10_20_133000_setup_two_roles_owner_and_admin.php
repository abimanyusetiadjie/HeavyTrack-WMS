<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Pastikan hanya ada 2 Role di sistem: Owner dan Admin
        $ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // 2. Buat atau perbarui akun Owner utama
        $defaultPassword = Hash::make(env('DEFAULT_OWNER_PASSWORD', 'OwnerHeavyTrack@2026!'));
        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@heavytrack.com'],
            ['name' => 'Owner', 'password' => $defaultPassword]
        );
        $ownerUser->syncRoles([$ownerRole]);

        // 3. Cari semua user di database dan petakan role-nya ke Owner atau Admin
        $users = User::all();
        foreach ($users as $user) {
            $emailLower = strtolower($user->email ?? '');
            $nameLower = strtolower($user->name ?? '');

            // Jika user adalah adminheavytruck atau akun staf kasir/gudang/admin -> role Admin
            if (
                str_contains($emailLower, 'adminheavytruck') ||
                str_contains($nameLower, 'adminheavytruck') ||
                str_contains($emailLower, 'kasir') ||
                str_contains($emailLower, 'gudang')
            ) {
                $user->syncRoles([$adminRole]);
            } elseif (
                str_contains($emailLower, 'owner') ||
                str_contains($nameLower, 'owner') ||
                $user->id === $ownerUser->id
            ) {
                $user->syncRoles([$ownerRole]);
            } else {
                // Default akun operasional lainnya adalah Admin
                $user->syncRoles([$adminRole]);
            }
        }

        // 4. Hapus role-role lama yang sudah tidak dipakai (Gudang, Kasir, dsb)
        Role::whereNotIn('name', ['Owner', 'Admin'])->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
