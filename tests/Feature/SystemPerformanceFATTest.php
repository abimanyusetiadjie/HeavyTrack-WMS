<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemPerformanceFATTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_system_acceptance_and_performance_benchmark(): void
    {
        // 1. Seed minimal realistic dataset
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_fat@heavytrack.com',
            'name' => 'Owner FAT',
        ]);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI']);
        $gar = Warehouse::firstOrCreate(['code' => 'GAR'], ['name' => 'GUDANG ANEKARASA']);

        $customer = Contact::create([
            'company_name' => 'PT Mitra Sejahtera Tambang',
            'type' => 'CUSTOMER',
            'address' => 'Jl. Narogong Raya KM 15',
            'phone' => '081299887766',
        ]);

        $supplier = Contact::create([
            'company_name' => 'PT Supplier Utama Sparepart',
            'type' => 'SUPPLIER',
            'address' => 'Jl. Industri Jababeka Blok C',
            'phone' => '081122334455',
        ]);

        $brandHitachi = \App\Models\Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $brandFleet = \App\Models\Brand::firstOrCreate(['name' => 'Fleetguard'], ['code' => 'FLG']);
        $catFilter = \App\Models\Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $parts = [];
        for ($i = 1; $i <= 10; $i++) {
            $brand = ($i % 2 === 0) ? $brandHitachi : $brandFleet;
            $parts[] = Part::create([
                'part_number' => 'PART-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => 'Industrial Filter Sparepart ' . $i,
                'brand_id' => $brand->id,
                'category_id' => $catFilter->id,
                'selling_price' => 150000 + ($i * 10000),
                'cost_price' => 100000 + ($i * 5000),
            ]);
        }

        // Delivery Order Compact
        $doCompact = DeliveryOrder::create([
            'do_number' => 'SJ/202611/0101',
            'delivery_date' => now(),
            'contact_id' => $customer->id,
            'warehouse_id' => $gma->id,
            'driver_name' => 'Supir Joko',
            'vehicle_plate_number' => 'B 1010 KZA',
            'status' => 'CONFIRMED',
            'created_by' => $owner->id,
        ]);
        for ($i = 0; $i < 5; $i++) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $doCompact->id,
                'part_id' => $parts[$i]->id,
                'qty' => 2,
                'unit' => 'PCS',
            ]);
        }

        // Delivery Order Full (> 12 items)
        $doFull = DeliveryOrder::create([
            'do_number' => 'SJ/202611/0102',
            'delivery_date' => now(),
            'contact_id' => $customer->id,
            'warehouse_id' => $gma->id,
            'driver_name' => 'Supir Joko',
            'vehicle_plate_number' => 'B 1010 KZA',
            'status' => 'CONFIRMED',
            'created_by' => $owner->id,
        ]);
        for ($i = 1; $i <= 15; $i++) {
            $p = Part::create([
                'part_number' => 'HEAVY-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => 'Heavy Part ' . $i,
                'brand_id' => $brandHitachi->id,
                'category_id' => $catFilter->id,
                'selling_price' => 200000,
                'cost_price' => 150000,
            ]);
            DeliveryOrderItem::create([
                'delivery_order_id' => $doFull->id,
                'part_id' => $p->id,
                'qty' => 1,
                'unit' => 'PCS',
            ]);
        }

        // Invoice & Payment
        $invoice = Invoice::create([
            'invoice_number' => 'INV/202611/0101',
            'delivery_order_id' => $doCompact->id,
            'contact_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'PAID',
            'subtotal' => 1000000,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => 1000000,
            'paid_amount' => 1000000,
            'created_by' => $owner->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'part_id' => $parts[0]->id,
            'part_number_snapshot' => $parts[0]->part_number,
            'part_name_snapshot' => $parts[0]->name,
            'qty' => 2,
            'unit' => 'PCS',
            'unit_price' => 500000,
            'total_price' => 1000000,
        ]);
        Payment::create([
            'receipt_number' => 'BKM/202611/0101',
            'invoice_id' => $invoice->id,
            'payment_date' => now(),
            'payment_method' => 'BANK_TRANSFER',
            'amount' => 1000000,
            'reference_number' => 'TRF-BCA-9988',
            'created_by' => $owner->id,
        ]);

        // Endpoints to benchmark
        $endpoints = [
            ['GET', '/health', 'System Health Check', false],
            ['GET', '/admin/login', 'Authentication Page', false],
            ['GET', '/admin', 'Owner Dashboard', true],
            ['GET', '/admin/products', 'Katalog Produk (Brand Grouping)', true],
            ['GET', '/admin/products/create', 'Form Tambah Produk (Price Parser)', true],
            ['GET', '/admin/stock-ledgers', 'Buku Mutasi Stok (Tab Filters)', true],
            ['GET', '/admin/delivery-orders', 'Daftar Surat Jalan (DO)', true],
            ['GET', '/admin/delivery-orders/create', 'Form Buat Surat Jalan (DO)', true],
            ['GET', '/admin/invoices', 'Daftar Faktur Penjualan', true],
            ['GET', '/admin/invoices/create', 'Form Buat Faktur Penjualan', true],
            ['GET', '/admin/goods-receipts', 'Daftar Restock Masuk (LPB)', true],
            ['GET', '/admin/goods-receipts/create', 'Form Restock Masuk (LPB)', true],
            ['GET', '/admin/warehouses', 'Daftar Gudang (2 Locked)', true],
            ['GET', '/admin/contacts', 'Daftar Kontak (Customer & Supplier)', true],
            ['GET', '/print/delivery-order/' . $doCompact->id, 'Cetak SJ (Halaman Penuh A4)', true],
            ['GET', '/print/delivery-order/' . $doFull->id, 'Cetak SJ Banyak Item (A4)', true],
            ['GET', '/print/invoice/' . $invoice->id, 'Cetak Faktur Penjualan (A4 Portrait)', true],
            ['GET', '/print/receipt/' . $invoice->id, 'Cetak Kwitansi Pembayaran (A5 Landscape)', true],
        ];

        $benchmarkResults = [];

        foreach ($endpoints as $ep) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $startTime = microtime(true);
            $startMem = memory_get_usage();

            $client = $ep[3] ? $this->actingAs($owner) : $this;
            $response = $client->get($ep[1]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memDelta = round((memory_get_usage() - $startMem) / (1024 * 1024), 2);
            $queries = count(DB::getQueryLog());

            $this->assertEquals(200, $response->status(), "Endpoint {$ep[1]} failed with status {$response->status()}");

            $benchmarkResults[] = [
                'name' => $ep[2],
                'uri' => $ep[1],
                'status' => $response->status(),
                'duration_ms' => $duration,
                'queries' => $queries,
                'memory_mb' => max(0.1, $memDelta),
            ];
        }

        // Simpan hasil ke scratch JSON agar bisa dianalisis
        $outputPath = 'c:/Users/abima/.gemini/antigravity/brain/24d013e3-de83-4290-aec5-85ca017a7377/scratch/fat_benchmark_results.json';
        file_put_contents($outputPath, json_encode($benchmarkResults, JSON_PRETTY_PRINT));

        $this->assertFileExists($outputPath);
        $this->assertCount(count($endpoints), $benchmarkResults);
    }
}
