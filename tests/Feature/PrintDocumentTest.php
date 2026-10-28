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
}
