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
        // 1. Roles (Hanya 2 role: Owner dan Admin)
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // 2. Users (Kredensial mudah untuk operasional)
        $ownerPassword = Hash::make(env('DEFAULT_OWNER_PASSWORD', '12345678'));
        $owner = User::firstOrNew(['email' => 'owner@heavytrack.com']);
        $owner->name = 'owner';
        $owner->username = 'owner';
        $owner->password = $ownerPassword;
        $owner->save();
        $owner->syncRoles([$ownerRole]);

        $adminPassword = Hash::make(env('DEFAULT_ADMIN_PASSWORD', '12345678'));
        $admin = User::firstOrNew(['email' => 'admin@heavytrack.com']);
        $admin->name = 'admin';
        $admin->username = 'admin';
        $admin->password = $adminPassword;
        $admin->save();
        $admin->syncRoles([$adminRole]);

        // Hapus role lama yang tidak terpakai
        Role::whereNotIn('name', ['Owner', 'Admin'])->delete();

        // 3. Warehouses (Gudang Fisik Terkunci Sistem)
        $wMitraAbadi = Warehouse::firstOrCreate(
            ['code' => 'GMA'],
            [
                'name' => 'GUDANG MITRA ABADI',
                'address' => 'KAWASAN INDUSTRI MITRA ABADI NUSANTARA, BLOK A NO. 12',
                'is_active' => true,
            ]
        );

        $wAnekarasa = Warehouse::firstOrCreate(
            ['code' => 'GAR'],
            [
                'name' => 'GUDANG ANEKARASA',
                'address' => 'GUDANG CABANG ANEKARASA',
                'is_active' => true,
            ]
        );

        // Pastikan hanya 2 gudang ini yang aktif
        Warehouse::whereNotIn('id', [$wMitraAbadi->id, $wAnekarasa->id])->forceDelete();

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

        $categories = ['Fuel Filter', 'Oil Filter', 'Water Separator', 'Hydraulic Filter', 'Air Filter'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat],
                ['code' => strtoupper(substr(str_replace(' ', '', $cat), 0, 5))]
            );
        }

        // 6. Jalankan Migrasi Data 19 Part Klien Asli
        $this->call(ClientDataSeeder::class);

        // 7. Berikan Saldo Stok Awal (Initial Stock) HANYA saat inisialisasi sistem pertama kali
        if (StockLedger::where('reference_type', 'INITIAL_STOCK')->count() === 0) {
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
}
