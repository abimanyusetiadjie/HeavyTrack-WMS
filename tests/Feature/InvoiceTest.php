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

        $response = $this->actingAs($user)->get('/admin/invoices');
        $response->assertSuccessful();
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
}
