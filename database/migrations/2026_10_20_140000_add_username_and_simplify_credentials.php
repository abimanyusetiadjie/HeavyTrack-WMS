<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom username jika belum ada di tabel users
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $easyPassword = Hash::make('12345678');

        // 2. Akun Owner: username 'owner', nama 'owner', password '12345678'
        $owner = User::firstOrNew(['email' => 'owner@heavytrack.com']);
        $owner->name = 'owner';
        $owner->username = 'owner';
        $owner->password = $easyPassword;
        $owner->save();
        $owner->syncRoles([$ownerRole]);

        // 3. Akun Admin: username 'admin', nama 'admin', password '12345678'
        $admin = User::firstOrNew(['email' => 'admin@heavytrack.com']);
        $admin->name = 'admin';
        $admin->username = 'admin';
        $admin->password = $easyPassword;
        $admin->save();
        $admin->syncRoles([$adminRole]);

        // 4. Update seluruh user yang ada agar memiliki username & password mudah
        $allUsers = User::all();
        foreach ($allUsers as $u) {
            $emailLower = strtolower($u->email ?? '');
            $nameLower = strtolower($u->name ?? '');

            if (empty($u->username)) {
                $prefix = strtolower(explode('@', $u->email ?? '')[0]);
                if (empty($prefix)) {
                    $prefix = 'user_' . $u->id;
                }
                // Pastikan tidak duplikat
                if (! User::where('username', $prefix)->where('id', '!=', $u->id)->exists()) {
                    $u->username = $prefix;
                } else {
                    $u->username = $prefix . '_' . $u->id;
                }
            }

            // Jika akun adalah adminheavytruck atau kasir/gudang, set password ke 12345678 dan role Admin
            if (
                str_contains($emailLower, 'adminheavytruck') ||
                str_contains($nameLower, 'adminheavytruck') ||
                str_contains($emailLower, 'kasir') ||
                str_contains($emailLower, 'gudang')
            ) {
                $u->password = $easyPassword;
                $u->syncRoles([$adminRole]);
            }

            $u->save();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
