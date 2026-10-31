<?php

namespace Tests\Feature;

use App\Filament\Resources\DeliveryOrderResource;
use App\Filament\Resources\WarehouseResource;
use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderCustomerAndReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_test_' . uniqid() . '@heavytrack.com',
        ]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'name' => 'GUDANG UTAMA (PUSAT)',
            'code' => 'WH-PST',
            'is_active' => true,
        ]);

        $this->customer = Contact::create([
            'company_name' => 'PT TAMBANG MAKMUR JAYA',
            'type' => 'CUSTOMER',
            'pic_name' => 'BAPAK BUDI',
            'phone' => '08123456789',
            'address' => 'JL. RAYA INDUSTRI NO. 88, SAMARINDA',
        ]);

        $this->brand = \App\Models\Brand::create(['name' => 'FLEETGUARD']);

        $this->part = Part::create([
            'part_number' => 'FS1242',
            'name' => 'Fuel Water Separator Filter',
            'brand_id' => $this->brand->id,
            'is_active' => true,
            'unit' => 'PCS',
        ]);

        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty_on_hand' => 50,
        ]);
    }

    public function test_warehouse_menu_navigation_is_hidden(): void
    {
        $this->assertFalse(WarehouseResource::shouldRegisterNavigation());
    }

    public function test_delivery_order_sales_order_reference_saves_as_uppercase(): void
    {
        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0001',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->customer->id,
            'delivery_date' => now()->toDateString(),
            'sales_order_reference' => 'so-po-client-99/batch-1',
            'status' => 'CONFIRMED',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $do->id,
            'sales_order_reference' => 'SO-PO-CLIENT-99/BATCH-1',
        ]);
    }

    public function test_print_delivery_order_shows_sales_order_reference(): void
    {
        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0002',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->customer->id,
            'delivery_date' => now()->toDateString(),
            'sales_order_reference' => 'REF-TENDER-2026',
            'status' => 'CONFIRMED',
            'created_by' => $this->user->id,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'part_id' => $this->part->id,
            'qty' => 5,
            'unit' => 'PCS',
        ]);

        $response = $this->get(route('print.delivery-order', ['deliveryOrder' => $do->id]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        $viewHtml = view('print.delivery-order', ['do' => $do->fresh()->load(['items.part.brand', 'contact', 'warehouse'])])->render();
        $this->assertStringContainsString('REF. SO / PO', $viewHtml);
        $this->assertStringContainsString('REF-TENDER-2026', $viewHtml);
        $this->assertStringContainsString('PT TAMBANG MAKMUR JAYA', $viewHtml);
        $this->assertStringContainsString('GUDANG UTAMA (PUSAT)', $viewHtml);
    }

    public function test_delivery_order_form_has_preloaded_customer_and_manual_sales_reference(): void
    {
        $response = $this->get(DeliveryOrderResource::getUrl('create'));
        $response->assertSuccessful();
        $response->assertSee('PT TAMBANG MAKMUR JAYA');
        $response->assertSee('Referensi Sales Order');

        \Livewire\Livewire::test(\App\Filament\Resources\DeliveryOrderResource\Pages\CreateDeliveryOrder::class)
            ->assertFormFieldExists('contact_id')
            ->assertFormFieldExists('warehouse_id')
            ->assertFormFieldExists('sales_order_reference')
            ->assertFormSet([
                'warehouse_id' => $this->warehouse->id,
            ]);
    }
}
