<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_invoice_from_delivery_order(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'testowner@heavytrack.com',
                'name' => 'Owner System',
            ]);
        }

        $contact = Contact::create([
            'company_name' => 'PT Pelanggan Maju',
            'type' => 'CUSTOMER',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'GDG-01',
        ]);

        $brand = \App\Models\Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = \App\Models\Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $part = Part::create([
            'part_number' => 'FL-001',
            'name' => 'Fuel Filter 1',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 150000,
            'cost_price' => 100000,
        ]);

        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0001',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'CONFIRMED',
            'created_by' => $user->id,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'part_id' => $part->id,
            'qty' => 5,
            'unit' => 'PCS',
        ]);

        // 1. Test Print Delivery Order
        $resDo = $this->actingAs($user)->get(route('print.delivery-order', ['deliveryOrder' => $do->id]));
        $this->assertEquals(200, $resDo->status());

        // 2. Test Terbitkan & Cetak Faktur from Delivery Order
        $resInvFromDo = $this->actingAs($user)->get(route('print.delivery-order.invoice', ['deliveryOrder' => $do->id]));
        $this->assertEquals(200, $resInvFromDo->status());

        // Pastikan invoice terbuat di database
        $this->assertDatabaseHas('invoices', [
            'delivery_order_id' => $do->id,
            'contact_id' => $contact->id,
            'subtotal' => 750000,
        ]);

        $invoice = Invoice::where('delivery_order_id', $do->id)->first();
        $this->assertNotNull($invoice);

        // 3. Test Cetak Faktur directly
        $resInv = $this->actingAs($user)->get(route('print.invoice', ['invoice' => $invoice->id]));
        $this->assertEquals(200, $resInv->status());

        // 4. Test Cetak Kwitansi
        $resReceipt = $this->actingAs($user)->get(route('print.receipt', ['invoice' => $invoice->id]));
        $this->assertEquals(200, $resReceipt->status());
    }

    public function test_empty_delivery_order_renders_friendly_view(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'testowner2@heavytrack.com',
                'name' => 'Owner System 2',
            ]);
        }

        $contact = Contact::create([
            'company_name' => 'PT Pelanggan Baru',
            'type' => 'CUSTOMER',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Dua',
            'code' => 'GDG-02',
        ]);

        // Surat Jalan TANPA ITEM (kosong)
        $emptyDo = DeliveryOrder::create([
            'do_number' => 'SJ/202610/9999',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'CONFIRMED',
            'created_by' => $user->id,
        ]);

        $res = $this->actingAs($user)->get(route('print.delivery-order.invoice', ['deliveryOrder' => $emptyDo->id]));
        $this->assertEquals(200, $res->status());
        $res->assertSee('Surat Jalan Belum Memiliki Rincian Barang');
    }

    public function test_void_delivery_order_cannot_issue_invoice(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'testowner3@heavytrack.com',
                'name' => 'Owner System 3',
            ]);
        }

        $contact = Contact::create([
            'company_name' => 'PT Pelanggan Void',
            'type' => 'CUSTOMER',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Void',
            'code' => 'GDG-VD',
        ]);

        $voidDo = DeliveryOrder::create([
            'do_number' => 'SJ/202610/8888',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'VOID',
            'created_by' => $user->id,
        ]);

        $res = $this->actingAs($user)->get(route('print.delivery-order.invoice', ['deliveryOrder' => $voidDo->id]));
        $this->assertEquals(400, $res->status());
    }

    public function test_invoice_print_with_ppn_and_payment_receipt(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'testowner4@heavytrack.com',
                'name' => 'Owner System 4',
            ]);
        }

        $contact = Contact::create([
            'company_name' => 'PT Mitra Sejahtera',
            'type' => 'CUSTOMER',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV/202610/0999',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'PARTIALLY_PAID',
            'subtotal' => 1000000,
            'discount_amount' => 0,
            'tax_percent' => 11,
            'tax_amount' => 110000,
            'grand_total' => 1110000,
            'paid_amount' => 500000,
            'created_by' => $user->id,
        ]);

        \App\Models\Payment::create([
            'receipt_number' => 'BKM/202610/0001',
            'invoice_id' => $invoice->id,
            'payment_date' => now(),
            'payment_method' => 'BANK_TRANSFER',
            'amount' => 500000,
            'reference_number' => 'TRF-123456',
            'created_by' => $user->id,
        ]);

        // Cetak Faktur (mengecek template dengan PPN)
        $resInvoice = $this->actingAs($user)->get(route('print.invoice', ['invoice' => $invoice->id]));
        $this->assertEquals(200, $resInvoice->status());
        $invView = view('print.invoice', ['invoice' => $invoice->fresh()->load(['items.part', 'contact', 'deliveryOrder'])])->render();
        $this->assertStringContainsString('revaldojulian07@gmail.com', $invView);

        // Cetak Kwitansi (mengecek template kwitansi baru dengan terbilang)
        $resReceipt = $this->actingAs($user)->get(route('print.receipt', ['invoice' => $invoice->id]));
        $this->assertEquals(200, $resReceipt->status());
        $receiptView = view('print.receipt', ['invoice' => $invoice->fresh()->load(['contact', 'payments', 'items'])])->render();
        $this->assertStringContainsString('revaldojulian07@gmail.com', $receiptView);
    }

    public function test_delivery_order_fields_and_print_are_automatically_capslock(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'testowner_caps@heavytrack.com',
            'name' => 'Owner Caps',
        ]);

        $contact = Contact::create([
            'company_name' => 'pt mitra sentosa abadi',
            'type' => 'CUSTOMER',
            'address' => 'jl. raya narogong km 14',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'gudang pusat bekasi',
            'code' => 'gdg-pust',
        ]);

        $brand = \App\Models\Brand::firstOrCreate(['name' => 'Caterpillar'], ['code' => 'CAT']);
        $cat = \App\Models\Category::firstOrCreate(['name' => 'O-Ring'], ['code' => 'ORG']);

        $part = Part::create([
            'part_number' => 'or-999',
            'name' => 'seal o-ring kit',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 75000,
            'cost_price' => 50000,
        ]);

        // Simulasikan penginputan huruf kecil (lowercase)
        $do = DeliveryOrder::create([
            'do_number' => 'sj/202610/9001',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'CONFIRMED',
            'driver_name' => 'supir bambang pamungkas',
            'vehicle_plate_number' => 'b 9988 kza',
            'notes' => 'barang titipan proyek tambang, tolong hati-hati di jalan',
            'created_by' => $user->id,
        ]);

        $item = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'part_id' => $part->id,
            'qty' => 10,
            'unit' => 'pcs',
            'notes' => 'kondisi kardus masih segel pabrik',
        ]);

        // 1. Verifikasi model event mengubah teks menjadi UPPERCASE (CAPSLOCK) di database
        $this->assertEquals('SJ/202610/9001', $do->fresh()->do_number);
        $this->assertEquals('SUPIR BAMBANG PAMUNGKAS', $do->fresh()->driver_name);
        $this->assertEquals('B 9988 KZA', $do->fresh()->vehicle_plate_number);
        $this->assertEquals('BARANG TITIPAN PROYEK TAMBANG, TOLONG HATI-HATI DI JALAN', $do->fresh()->notes);
        $this->assertEquals('PCS', $item->fresh()->unit);
        $this->assertEquals('KONDISI KARDUS MASIH SEGEL PABRIK', $item->fresh()->notes);

        // 2. Verifikasi print view delivery order
        $viewContent = view('print.delivery-order', ['do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse'])])->render();
        $this->assertStringContainsString('SURAT JALAN', $viewContent);
        $this->assertStringContainsString('SUPIR BAMBANG PAMUNGKAS', $viewContent);
        $this->assertStringContainsString('B 9988 KZA', $viewContent);
        $this->assertStringContainsString('PT MITRA SENTOSA ABADI', $viewContent);
        $this->assertStringContainsString('JL. RAYA NAROGONG KM 14', $viewContent);
        $this->assertStringContainsString('GUDANG PUSAT BEKASI', $viewContent);
        $this->assertStringContainsString('SEAL O-RING KIT', $viewContent);
        $this->assertStringContainsString('KONDISI KARDUS MASIH SEGEL PABRIK', $viewContent);
        // Pengecualian lowercase untuk email
        $this->assertStringContainsString('revaldojulian07@gmail.com', $viewContent);
    }

    public function test_delivery_order_prints_single_a4_page(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'testowner_single@heavytrack.com',
            'name' => 'Owner Single',
        ]);

        $contact = Contact::create([
            'company_name' => 'PT Mitra Sejahtera Sentosa',
            'type' => 'CUSTOMER',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'GDG-01',
        ]);

        $brand = \App\Models\Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = \App\Models\Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202611/0002',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'CONFIRMED',
            'driver_name' => 'Supir Joko Widodo',
            'vehicle_plate_number' => 'B 1234 CD',
            'created_by' => $user->id,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $part = Part::create([
                'part_number' => 'HIT-00' . $i,
                'name' => 'Oil Filter Hitachi ' . $i,
                'brand_id' => $brand->id,
                'category_id' => $cat->id,
                'selling_price' => 100000,
                'cost_price' => 70000,
            ]);

            DeliveryOrderItem::create([
                'delivery_order_id' => $do->id,
                'part_id' => $part->id,
                'qty' => $i,
                'unit' => 'PCS',
            ]);
        }

        $response = $this->actingAs($user)->get(route('print.delivery-order', ['deliveryOrder' => $do->id]));
        $this->assertEquals(200, $response->status());

        $viewContent = view('print.delivery-order', ['do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse'])])->render();
        
        // Memastikan format sekarang 1 halaman penuh tanpa potongan bagi dua
        $this->assertStringContainsString('SURAT JALAN', $viewContent);
        $this->assertStringNotContainsString('POTONG / GUNTING DI SINI', $viewContent);
        $this->assertStringNotContainsString('LEMBAR 2: COPY', $viewContent);
        $this->assertStringContainsString('HORMAT KAMI,', $viewContent);
        $this->assertStringContainsString('REVALDO JULIAN', $viewContent);
        $this->assertStringContainsString('data:image/png;base64,', $viewContent);

        // Verifikasi PDF DomPDF render menghasilkan tepat 1 halaman A4
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.delivery-order', [
            'do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse']),
        ])->setPaper('a4', 'portrait');
        $pdf->render();
        $this->assertEquals(1, $pdf->getCanvas()->get_page_count());
    }

    public function test_delivery_order_prints_cleanly_with_many_items(): void
    {
        $user = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'testowner_full@heavytrack.com',
            'name' => 'Owner Full',
        ]);

        $contact = Contact::create([
            'company_name' => 'PT Proyek Tambang Besar',
            'type' => 'CUSTOMER',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'GDG-01',
        ]);

        $brand = \App\Models\Brand::firstOrCreate(['name' => 'Komatsu'], ['code' => 'KOM']);
        $cat = \App\Models\Category::firstOrCreate(['name' => 'Sparepart'], ['code' => 'SPR']);

        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202611/0003',
            'delivery_date' => now(),
            'contact_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'CONFIRMED',
            'driver_name' => 'Supir Wahyu',
            'vehicle_plate_number' => 'B 5678 EF',
            'created_by' => $user->id,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $part = Part::create([
                'part_number' => 'KOM-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => 'Komatsu Hydraulic Part ' . $i,
                'brand_id' => $brand->id,
                'category_id' => $cat->id,
                'selling_price' => 200000,
                'cost_price' => 150000,
            ]);

            DeliveryOrderItem::create([
                'delivery_order_id' => $do->id,
                'part_id' => $part->id,
                'qty' => $i,
                'unit' => 'PCS',
            ]);
        }

        $response = $this->actingAs($user)->get(route('print.delivery-order', ['deliveryOrder' => $do->id]));
        $this->assertEquals(200, $response->status());

        $viewContent = view('print.delivery-order', [
            'do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse']),
        ])->render();

        $this->assertStringContainsString('SURAT JALAN', $viewContent);
        $this->assertStringContainsString('HORMAT KAMI,', $viewContent);
        $this->assertStringContainsString('REVALDO JULIAN', $viewContent);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.delivery-order', [
            'do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse']),
        ])->setPaper('a4', 'portrait');
        $pdf->render();
        $this->assertEquals(1, $pdf->getCanvas()->get_page_count());
    }
}
