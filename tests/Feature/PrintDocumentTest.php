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

        // Cetak Kwitansi (mengecek template kwitansi baru dengan terbilang)
        $resReceipt = $this->actingAs($user)->get(route('print.receipt', ['invoice' => $invoice->id]));
        $this->assertEquals(200, $resReceipt->status());
    }
}
