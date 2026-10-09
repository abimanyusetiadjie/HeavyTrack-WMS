<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Contact;
use App\Models\Part;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\StockBalance;
use App\Models\User;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        // Warehouses
        $w1 = Warehouse::firstOrCreate(['name' => 'Gudang Pusat Jakarta', 'code' => 'JKT-01'], ['address' => 'Jl. Industri Raya No. 1, Jakarta']);
        $w2 = Warehouse::firstOrCreate(['name' => 'Gudang Cabang Surabaya', 'code' => 'SUB-01'], ['address' => 'Jl. Rungkut Industri No. 5, Surabaya']);

        // Contacts
        $c1 = Contact::firstOrCreate(['company_name' => 'PT Bumi Karsa'], ['contact_person' => 'Budi Santoso', 'phone' => '081234567890']);
        $c2 = Contact::firstOrCreate(['company_name' => 'PT Adhi Karya'], ['contact_person' => 'Joko Anwar', 'phone' => '081298765432']);

        // Parts
        $parts = [];
        for ($i=1; $i<=15; $i++) {
            $part = Part::firstOrCreate([
                'part_number' => 'PN-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            ], [
                'name' => 'Sparepart Heavy Duty ' . $i,
                'description' => 'Genuine replacement part for excavator models.',
                'brand_id' => rand(1, 4),
                'category_id' => rand(1, 4),
                'uom' => 'PCS',
                'min_stock_level' => 10,
                'purchase_price' => rand(500, 2000) * 1000,
                'sale_price' => rand(2500, 5000) * 1000,
            ]);
            $parts[] = $part;

            // Seed Stock
            StockBalance::firstOrCreate(
                ['warehouse_id' => $w1->id, 'part_id' => $part->id],
                ['qty_on_hand' => rand(50, 200)]
            );
            StockBalance::firstOrCreate(
                ['warehouse_id' => $w2->id, 'part_id' => $part->id],
                ['qty_on_hand' => rand(20, 100)]
            );
        }

        // Delivery Order
        $do = DeliveryOrder::firstOrCreate(['do_number' => 'DO/2026/10/001'], [
            'delivery_date' => now()->format('Y-m-d'),
            'warehouse_id' => $w1->id,
            'contact_id' => $c1->id,
            'status' => 'ISSUED',
            'created_by' => $admin->id ?? 1
        ]);

        if ($do->items()->count() == 0) {
            $do->items()->create(['part_id' => $parts[0]->id, 'qty' => 5]);
            $do->items()->create(['part_id' => $parts[1]->id, 'qty' => 10]);
        }
        
        // Invoice
        $inv = Invoice::firstOrCreate(['invoice_number' => 'INV/2026/10/001'], [
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'delivery_order_id' => $do->id,
            'contact_id' => $c1->id,
            'subtotal' => 5000000,
            'tax_amount' => 550000,
            'total_amount' => 5550000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
            'created_by' => $admin->id ?? 1
        ]);
    }
}
