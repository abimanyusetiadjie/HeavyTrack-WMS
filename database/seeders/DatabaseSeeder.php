<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\Contact;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $gudangRole = Role::firstOrCreate(['name' => 'Gudang']);
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        // 2. Users (Gunakan password aman)
        $defaultPassword = Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'HeavyTrack@2026!'));

        $admin = User::firstOrCreate(
            ['email' => 'admin@heavytrack.com'],
            ['name' => 'Super Admin', 'password' => $defaultPassword]
        );
        $admin->syncRoles([$adminRole]);

        $gudang = User::firstOrCreate(
            ['email' => 'gudang@heavytrack.com'],
            ['name' => 'Kepala Gudang', 'password' => $defaultPassword]
        );
        $gudang->syncRoles([$gudangRole]);

        $kasir = User::firstOrCreate(
            ['email' => 'kasir@heavytrack.com'],
            ['name' => 'Kasir Utama', 'password' => $defaultPassword]
        );
        $kasir->syncRoles([$kasirRole]);

        // 3. Warehouses (Gudang Fisik)
        $wPusat = Warehouse::firstOrCreate(
            ['code' => 'GDG-PUSAT'],
            ['name' => 'Gudang Utama (Pusat)', 'address' => 'Kawasan Industri Mitra Abadi Nusantara, Blok A No. 12', 'is_active' => true]
        );

        $wTransit = Warehouse::firstOrCreate(
            ['code' => 'GDG-TRN'],
            ['name' => 'Gudang Transit Logistik', 'address' => 'Kawasan Pergudangan Logistik Cikarang', 'is_active' => true]
        );

        // 4. Contacts (Supplier & Pelanggan)
        Contact::firstOrCreate(
            ['company_name' => 'PT United Tractors Tbk'],
            ['type' => 'SUPPLIER', 'pic_name' => 'Bambang Sudiro', 'phone' => '081288889999', 'address' => 'Jl. Raya Bekasi Km 22, Cakung, Jakarta Timur', 'term_of_payment_days' => 30]
        );

        Contact::firstOrCreate(
            ['company_name' => 'PT Trakindo Utama'],
            ['type' => 'SUPPLIER', 'pic_name' => 'Hendra Setiawan', 'phone' => '081377778888', 'address' => 'Jl. Cilandak KKO Raya No. 1, Jakarta Selatan', 'term_of_payment_days' => 30]
        );

        Contact::firstOrCreate(
            ['company_name' => 'PT Bumi Karsa'],
            ['type' => 'CUSTOMER', 'pic_name' => 'Budi Santoso', 'phone' => '081234567890', 'address' => 'Jl. AP Pettarani No. 100, Makassar', 'term_of_payment_days' => 14]
        );

        Contact::firstOrCreate(
            ['company_name' => 'PT Adhi Karya (Persero) Tbk'],
            ['type' => 'CUSTOMER', 'pic_name' => 'Joko Anwar', 'phone' => '081298765432', 'address' => 'Jl. Raya Pasar Minggu KM 18, Jakarta', 'term_of_payment_days' => 30]
        );

        // 5. Brands & Categories bawaan
        $brands = ['Hitachi', 'Komatsu', 'CAT', 'LiuGong', 'Parker Racor', 'Fleetguard', 'Kobelco', 'XCMG', 'SANY'];
        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand],
                ['code' => strtoupper(substr(str_replace(' ', '', $brand), 0, 5))]
            );
        }

        $categories = ['Fuel Filter', 'Oil Filter', 'Water Separator', 'Hydraulic Filter', 'Air Filter', 'Element Assy'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat],
                ['code' => strtoupper(substr(str_replace(' ', '', $cat), 0, 5))]
            );
        }

        // 6. Jalankan Migrasi Data 19 Part Klien Asli
        $this->call(ClientDataSeeder::class);

        // 7. Berikan Saldo Stok Awal (Initial Stock) jika belum ada saldo
        $parts = Part::all();
        foreach ($parts as $part) {
            $existing = StockBalance::where('warehouse_id', $wPusat->id)->where('part_id', $part->id)->first();
            if (!$existing) {
                StockBalance::create([
                    'warehouse_id' => $wPusat->id,
                    'part_id' => $part->id,
                    'qty_on_hand' => 50, // Saldo stok awal siap pakai
                    'qty_reserved' => 0,
                ]);

                StockLedger::create([
                    'warehouse_id' => $wPusat->id,
                    'part_id' => $part->id,
                    'reference_type' => 'INITIAL_STOCK',
                    'reference_id' => $part->id,
                    'reference_number' => 'INIT/' . $part->part_number,
                    'qty_change' => 50,
                    'balance_before' => 0,
                    'balance_after' => 50,
                    'notes' => 'Saldo Stok Awal Sistem WMS Mitra Abadi Nusantara',
                    'created_by' => $admin->id,
                ]);
            }
        }
    }
}
