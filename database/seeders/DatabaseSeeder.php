<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $gudangRole = Role::firstOrCreate(['name' => 'Gudang']);
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        // 2. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@heavytrack.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole($adminRole);

        $gudang = User::firstOrCreate(
            ['email' => 'gudang@heavytrack.com'],
            ['name' => 'Kepala Gudang', 'password' => Hash::make('password')]
        );
        $gudang->assignRole($gudangRole);

        $kasir = User::firstOrCreate(
            ['email' => 'kasir@heavytrack.com'],
            ['name' => 'Kasir Utama', 'password' => Hash::make('password')]
        );
        $kasir->assignRole($kasirRole);

        // 3. Brands
        $brands = ['Hitachi', 'Komatsu', 'CAT', 'LiuGong', 'Parker', 'Fleetguard'];
        foreach ($brands as $brand) {
            Brand::firstOrCreate([
                'name' => $brand,
                'code' => strtoupper(substr($brand, 0, 3)),
            ]);
        }

        // 4. Categories
        $categories = ['Fuel Filter', 'Oil Filter', 'Water Separator', 'Element Assy'];
        foreach ($categories as $cat) {
            Category::firstOrCreate([
                'name' => $cat,
                'code' => strtoupper(str_replace(' ', '_', $cat)),
            ]);
        }
    }
}
