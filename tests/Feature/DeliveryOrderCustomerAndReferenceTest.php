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

        $this->warehouse = Warehouse::where('code', 'GMA')->first() ?: Warehouse::firstOrCreate(
            ['code' => 'GMA'],
            [
                'name' => 'GUDANG MITRA ABADI',
                'is_active' => true,
            ]
        );

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

    public function test_warehouse_menu_navigation_is_visible_and_locked_to_two_warehouses(): void
    {
        $this->assertTrue(WarehouseResource::shouldRegisterNavigation());
        $this->assertFalse(WarehouseResource::canCreate());
        $this->assertFalse(WarehouseResource::canDelete($this->warehouse));
        $this->assertFalse(WarehouseResource::canDeleteAny());

        // Pastikan 2 gudang resmi terdaftar
        $this->assertDatabaseHas('warehouses', ['code' => 'GMA', 'name' => 'GUDANG MITRA ABADI']);
        $this->assertDatabaseHas('warehouses', ['code' => 'GAR', 'name' => 'GUDANG ANEKARASA']);
        $this->assertEquals(2, Warehouse::count());
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
        $this->assertStringContainsString('GUDANG MITRA ABADI', $viewHtml);
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

    public function test_delivery_order_item_select_has_grouped_brands_and_stock_info(): void
    {
        $response = $this->get(DeliveryOrderResource::getUrl('create'));
        $response->assertSuccessful();

        // Verifikasi bahwa brand dan info part & stok ada di HTML halaman create
        $response->assertSee('FLEETGUARD');
        $response->assertSee('FS1242');
        $response->assertSee('Stok: 50 PCS');
    }

    public function test_delivery_order_displays_other_warehouse_stock_and_auto_switches_when_first_item_is_in_anekarasa(): void
    {
        $gar = Warehouse::firstOrCreate(
            ['code' => 'GAR'],
            ['name' => 'GUDANG ANEKARASA', 'is_active' => true]
        );

        $fs36231 = Part::create([
            'part_number' => 'FS36231',
            'name' => 'Fuel Water Separator Filter FS36231',
            'brand_id' => $this->brand->id,
            'is_active' => true,
            'unit' => 'PCS',
            'bin_location' => 'Gudang Anekarasa',
        ]);

        // Stok FS36231 adanya di Gudang Anekarasa (250 PCS), sedangkan di GMA = 0 PCS
        StockBalance::create([
            'warehouse_id' => $gar->id,
            'part_id' => $fs36231->id,
            'qty_on_hand' => 250,
        ]);

        // 1. Halaman Create DO default Gudang Asal = GMA, tetapi dropdown harus tetap menginformasikan stok tersedia di Gudang Anekarasa
        $response = $this->get(DeliveryOrderResource::getUrl('create'));
        $response->assertSuccessful();
        $response->assertSee('Tersedia di Gudang Anekarasa: 250 PCS');

        // 2. Simulasikan memilih produk FS36231 pada dropdown item pertama (seperti interaksi user di browser)
        $component = \Livewire\Livewire::test(\App\Filament\Resources\DeliveryOrderResource\Pages\CreateDeliveryOrder::class)
            ->assertSuccessful();

        $firstUuid = array_key_first($component->instance()->data['items'] ?? []);
        $this->assertNotNull($firstUuid);

        $component
            ->set('data.contact_id', $this->customer->id)
            ->set("data.items.{$firstUuid}.part_id", $fs36231->id)
            ->set("data.items.{$firstUuid}.qty", 10);

        // Karena item pertama hanya tersedia di GAR, warehouse_id otomatis pindah ke GAR dan available_stock terisi 250
        $state = $component->instance()->data;
        $firstItem = $state['items'][$firstUuid] ?? [];
        $this->assertEquals($gar->id, $state['warehouse_id']);
        $this->assertEquals(250, (int) ($firstItem['available_stock'] ?? 0));

        // 3. Simpan Surat Jalan dan verifikasi stok terpotong di GAR & tercatat di StockLedger
        $component->call('create')->assertHasNoFormErrors();

        $this->assertEquals(240, StockBalance::where('warehouse_id', $gar->id)->where('part_id', $fs36231->id)->value('qty_on_hand'));
        $this->assertDatabaseHas('stock_ledgers', [
            'warehouse_id' => $gar->id,
            'part_id' => $fs36231->id,
            'reference_type' => 'DELIVERY_ORDER',
            'qty_change' => -10,
            'balance_before' => 250,
            'balance_after' => 240,
        ]);

        // 4. Verifikasi perubahan Gudang Asal secara manual pada item yang sudah dipilih juga memperbarui available_stock
        $component2 = \Livewire\Livewire::test(\App\Filament\Resources\DeliveryOrderResource\Pages\CreateDeliveryOrder::class)
            ->assertSuccessful();

        $uuid2 = array_key_first($component2->instance()->data['items'] ?? []);
        $component2
            ->set('data.warehouse_id', $this->warehouse->id)
            ->set("data.items.{$uuid2}.part_id", $this->part->id);

        $firstItem2 = $component2->instance()->data['items'][$uuid2] ?? [];
        $this->assertEquals(50, (int) ($firstItem2['available_stock'] ?? 0));

        // Pindahkan Gudang Asal ke GAR (di mana $this->part stoknya 0) -> Sisa Stok harus langsung berubah jadi 0
        $component2->set('data.warehouse_id', $gar->id);
        $firstItemAfterSwitch = $component2->instance()->data['items'][$uuid2] ?? [];
        $this->assertEquals(0, (int) ($firstItemAfterSwitch['available_stock'] ?? -1));
    }
}
