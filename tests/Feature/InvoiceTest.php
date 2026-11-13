<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_invoice_create_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test@heavytrack.com']);

        $response = $this->actingAs($user)->get(InvoiceResource::getUrl('create'));
        $response->assertSuccessful();
    }

    public function test_can_render_delivery_orders_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test2@heavytrack.com']);

        $response = $this->actingAs($user)->get('/admin/delivery-orders');
        $response->assertSuccessful();
    }

    public function test_can_render_invoices_list_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test3@heavytrack.com']);
        $user->syncRoles([\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner'])]);

        $contact = Contact::create([
            'company_name' => 'PT Customer Test',
            'type' => 'CUSTOMER',
        ]);

        \App\Models\Invoice::create([
            'invoice_number' => 'INV/TEST/001',
            'contact_id' => $contact->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'UNPAID',
            'grand_total' => 1000000,
            'created_by' => $user->id,
        ]);

        $page = new \App\Filament\Resources\InvoiceResource\Pages\ListInvoices();
        $tabs = $page->getTabs();
        $this->assertArrayHasKey('all', $tabs);
        $this->assertArrayHasKey('unpaid', $tabs);
        $this->assertArrayHasKey('paid', $tabs);
        $this->assertArrayHasKey('void', $tabs);

        $response = $this->actingAs($user)->get('/admin/invoices');
        $response->assertSuccessful();
        $response->assertSee('Semua Faktur');
        $response->assertSee('Belum Lunas');
        $response->assertSee('Lunas');
        $response->assertSee('Batal');
    }

    public function test_invoice_and_payment_fields_are_automatically_capslock(): void
    {
        $user = User::factory()->create(['email' => 'admin_caps_inv@heavytrack.com']);

        $contact = Contact::create([
            'company_name' => 'pt sinar abadi teknik',
            'type' => 'CUSTOMER',
            'address' => 'kawasan industri mm2100 cikarang',
            'phone' => '08123456789',
        ]);

        $brand = Brand::firstOrCreate(['name' => 'Donaldson'], ['code' => 'DON']);
        $cat = Category::firstOrCreate(['name' => 'Air Filter'], ['code' => 'AIR']);

        $part = Part::create([
            'part_number' => 'p55-0010',
            'name' => 'air filter primary',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 350000,
            'cost_price' => 250000,
        ]);

        // Simulasikan pembuatan invoice dengan input huruf kecil
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'inv/202610/7788',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'UNPAID',
            'subtotal' => 700000,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => 700000,
            'paid_amount' => 0,
            'created_by' => $user->id,
        ]);

        $item = \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'part_id' => $part->id,
            'part_number_snapshot' => 'p55-0010',
            'part_name_snapshot' => 'air filter primary',
            'qty' => 2,
            'unit' => 'pcs',
            'unit_price' => 350000,
            'discount_percent' => 0,
            'total_price' => 700000,
        ]);

        $payment = \App\Models\Payment::create([
            'receipt_number' => 'bkm/202610/9999',
            'invoice_id' => $invoice->id,
            'payment_date' => now(),
            'payment_method' => 'BANK_TRANSFER',
            'bank_name' => 'bca cabang thamrin',
            'reference_number' => 'trf-bca-889900',
            'amount' => 700000,
            'notes' => 'pembayaran lunas tagihan filter alat berat',
            'created_by' => $user->id,
        ]);

        // 1. Verifikasi model hooks mengonversi ke UPPERCASE (CAPSLOCK)
        $this->assertEquals('INV/202610/7788', $invoice->fresh()->invoice_number);
        $this->assertEquals('P55-0010', $item->fresh()->part_number_snapshot);
        $this->assertEquals('AIR FILTER PRIMARY', $item->fresh()->part_name_snapshot);
        $this->assertEquals('PCS', $item->fresh()->unit);

        $this->assertEquals('BKM/202610/9999', $payment->fresh()->receipt_number);
        $this->assertEquals('BCA CABANG THAMRIN', $payment->fresh()->bank_name);
        $this->assertEquals('TRF-BCA-889900', $payment->fresh()->reference_number);
        $this->assertEquals('PEMBAYARAN LUNAS TAGIHAN FILTER ALAT BERAT', $payment->fresh()->notes);

        // 2. Verifikasi print view Faktur
        $invoiceView = view('print.invoice', ['invoice' => $invoice->fresh()->load(['items.part', 'contact', 'creator', 'deliveryOrder'])])->render();
        $this->assertStringContainsString('FAKTUR PENJUALAN', $invoiceView);
        $this->assertStringContainsString('INV/202610/7788', $invoiceView);
        $this->assertStringContainsString('PT SINAR ABADI TEKNIK', $invoiceView);
        $this->assertStringContainsString('KAWASAN INDUSTRI MM2100 CIKARANG', $invoiceView);
        $this->assertStringContainsString('P55-0010', $invoiceView);
        $this->assertStringContainsString('AIR FILTER PRIMARY', $invoiceView);
        $this->assertStringContainsString('PCS', $invoiceView);

        // 3. Verifikasi print view Kwitansi
        $receiptView = view('print.receipt', ['invoice' => $invoice->fresh()->load(['payments', 'contact'])])->render();
        $this->assertStringContainsString('KWITANSI PEMBAYARAN', $receiptView);
        $this->assertStringContainsString('BKM/202610/9999', $receiptView);
        $this->assertStringContainsString('PT SINAR ABADI TEKNIK', $receiptView);
        $this->assertStringContainsString('TRF-BCA-889900', $receiptView);
        $this->assertStringContainsString('PEMBAYARAN LUNAS TAGIHAN FILTER ALAT BERAT', $receiptView);
    }

    public function test_standalone_invoice_without_delivery_order_does_not_deduct_stock_until_do_is_issued(): void
    {
        $user = User::factory()->create(['email' => 'owner_inv_stock@heavytrack.com']);
        $user->syncRoles([\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner'])]);
        $this->actingAs($user);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI', 'is_active' => true]);
        $contact = Contact::create(['company_name' => 'ANUGRAH JAYA TRACTOR', 'type' => 'CUSTOMER']);
        $brand = Brand::firstOrCreate(['name' => 'Fleetguard'], ['code' => 'FLT']);
        $cat = Category::firstOrCreate(['name' => 'Fuel Filter'], ['code' => 'FF']);

        $part = Part::create([
            'part_number' => 'FS36231-TEST',
            'name' => 'Filter FS36231 Test',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 100000,
        ]);

        app(\App\Services\InventoryService::class)->addStock($gma->id, $part->id, 100, 'INITIAL_STOCK', $part->id, 'INIT/FS', 'Saldo Awal');

        // 1. Buat Faktur langsung tanpa Surat Jalan
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'INV/202611/0006',
            'contact_id' => $contact->id,
            'invoice_date' => '2026-11-13',
            'due_date' => '2026-12-13',
            'status' => 'UNPAID',
            'subtotal' => 2000000,
            'grand_total' => 2000000,
            'created_by' => $user->id,
        ]);

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'part_id' => $part->id,
            'part_number_snapshot' => 'FS36231-TEST',
            'part_name_snapshot' => 'Filter FS36231 Test',
            'qty' => 20,
            'unit' => 'PCS',
            'unit_price' => 100000,
            'total_price' => 2000000,
        ]);

        // Konfirmasi: stok TIDAK berkurang otomatis jika faktur dibuat tanpa Surat Jalan
        $this->assertEquals(100, \App\Models\StockBalance::where('warehouse_id', $gma->id)->where('part_id', $part->id)->value('qty_on_hand'));
        $this->assertDatabaseMissing('stock_ledgers', [
            'reference_type' => 'DELIVERY_ORDER',
            'part_id' => $part->id,
        ]);

        // Verifikasi Daftar Faktur menampilkan status 'Tanpa SJ (Stok Belum Dipotong)'
        $this->get('/admin/invoices')
            ->assertSuccessful()
            ->assertSee('INV/202611/0006')
            ->assertSee('Tanpa SJ (Stok Belum Dipotong)');

        // 2. Terbitkan Surat Jalan & Potong Stok dari Faktur tersebut
        $do = InvoiceResource::issueDeliveryOrderFromInvoice($invoice->fresh(), [
            'warehouse_id' => $gma->id,
            'delivery_date' => '2026-11-13',
        ]);

        $this->assertEquals('CONFIRMED', $do->status);
        $this->assertEquals($do->id, $invoice->fresh()->delivery_order_id);

        // Sekarang stok fisik berkurang (100 - 20 = 80) dan tercatat di StockLedger
        $this->assertEquals(80, \App\Models\StockBalance::where('warehouse_id', $gma->id)->where('part_id', $part->id)->value('qty_on_hand'));
        $this->assertDatabaseHas('stock_ledgers', [
            'warehouse_id' => $gma->id,
            'part_id' => $part->id,
            'reference_type' => 'DELIVERY_ORDER',
            'reference_id' => $do->id,
            'qty_change' => -20,
            'balance_before' => 100,
            'balance_after' => 80,
        ]);

        // Daftar Faktur sekarang menampilkan badge 'Stok Dipotong'
        $this->get('/admin/invoices')
            ->assertSuccessful()
            ->assertSee($do->do_number . ' (Stok Dipotong)');
    }

    public function test_invoice_linked_to_empty_or_unmatched_delivery_order_shows_red_unposted_badge_not_false_deducted(): void
    {
        $user = User::factory()->create(['email' => 'owner_false_do@heavytrack.com']);
        $user->syncRoles([\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner'])]);
        $this->actingAs($user);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI', 'is_active' => true]);
        $contact = Contact::create(['company_name' => 'ANUGRAH JAYA TRACTOR', 'type' => 'CUSTOMER']);
        $brand = Brand::firstOrCreate(['name' => 'Fleetguard'], ['code' => 'FLT']);
        $cat = Category::firstOrCreate(['name' => 'Fuel Filter'], ['code' => 'FF']);

        $part = Part::create([
            'part_number' => 'P55-493M',
            'name' => 'Heavy Duty Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 49354000,
        ]);

        app(\App\Services\InventoryService::class)->addStock($gma->id, $part->id, 50, 'INITIAL_STOCK', $part->id, 'INIT/493', 'Saldo Awal');

        // Simulasikan Surat Jalan lama SJ/202610/0002 berstatus CONFIRMED tetapi 0 mutasi StockLedger untuk barang faktur ini
        $emptyDo = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0002',
            'warehouse_id' => $gma->id,
            'contact_id' => $contact->id,
            'delivery_date' => '2026-10-15',
            'status' => 'CONFIRMED',
            'created_by' => $user->id,
        ]);

        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'INV/202611/0006',
            'delivery_order_id' => $emptyDo->id,
            'contact_id' => $contact->id,
            'invoice_date' => '2026-11-13',
            'due_date' => '2026-12-13',
            'status' => 'UNPAID',
            'subtotal' => 493540000,
            'grand_total' => 493540000,
            'created_by' => $user->id,
        ]);

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'part_id' => $part->id,
            'part_number_snapshot' => 'P55-493M',
            'part_name_snapshot' => 'Heavy Duty Filter',
            'qty' => 10,
            'unit' => 'PCS',
            'unit_price' => 49354000,
            'total_price' => 493540000,
        ]);

        // TIDAK BOLEH menampilkan "(Stok Dipotong)"! Harus jujur menampilkan bahwa mutasi masih 0 / belum dipotong!
        $this->get('/admin/invoices')
            ->assertSuccessful()
            ->assertSee('INV/202611/0006')
            ->assertSee('SJ/202610/0002 (Mutasi 0 - Belum Dipotong!)')
            ->assertDontSee('SJ/202610/0002 (Stok Dipotong)');

        // Pastikan tombol perbaikan 'Buat SJ & Potong Stok' tetap tersedia dan berfungsi memotong 10 pcs
        $newDo = InvoiceResource::issueDeliveryOrderFromInvoice($invoice->fresh(), [
            'warehouse_id' => $gma->id,
            'delivery_date' => '2026-11-13',
        ]);

        $this->assertEquals(40, \App\Models\StockBalance::where('warehouse_id', $gma->id)->where('part_id', $part->id)->value('qty_on_hand'));
        $this->assertDatabaseHas('stock_ledgers', [
            'reference_type' => 'DELIVERY_ORDER',
            'reference_id' => $newDo->id,
            'part_id' => $part->id,
            'qty_change' => -10,
            'balance_before' => 50,
            'balance_after' => 40,
        ]);

        // Sekarang barulah status sah menjadi "(Stok Dipotong)"
        $this->get('/admin/invoices')
            ->assertSuccessful()
            ->assertSee($newDo->do_number . ' (Stok Dipotong)');
    }
}
