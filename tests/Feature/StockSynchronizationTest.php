<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Warehouse $warehouse;
    private Part $part;
    private Contact $supplier;
    private Contact $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'stock_tester@heavytrack.com']);
        $this->warehouse = Warehouse::create(['name' => 'Gudang Utama', 'code' => 'GDG-01']);
        $this->supplier = Contact::create(['company_name' => 'PT Supplier', 'type' => 'SUPPLIER']);
        $this->customer = Contact::create(['company_name' => 'PT Customer', 'type' => 'CUSTOMER']);

        $brand = Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $this->part = Part::create([
            'part_number' => 'SYNC-001',
            'name' => 'Filter Sync Test',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 150000,
            'cost_price' => 100000,
        ]);
    }

    public function test_goods_receipt_adds_stock_and_creates_ledger(): void
    {
        $inventoryService = app(InventoryService::class);

        // Penerimaan barang 30 pcs
        $gr = GoodsReceipt::create([
            'receipt_number' => 'LPB/202610/0001',
            'receipt_date' => now(),
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->supplier->id,
            'status' => 'DRAFT',
            'created_by' => $this->user->id,
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $gr->id,
            'part_id' => $this->part->id,
            'qty_received' => 30,
            'unit' => 'PCS',
        ]);

        // Transisi status ke RECEIVED
        $gr->status = 'RECEIVED';
        $gr->save();

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(30, $balance->qty_on_hand);

        $this->assertDatabaseHas('stock_ledgers', [
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'reference_type' => 'GOODS_RECEIPT',
            'qty_change' => 30,
            'balance_after' => 30,
        ]);
    }

    public function test_delivery_order_deducts_stock_and_cancelling_reverses_it(): void
    {
        $inventoryService = app(InventoryService::class);
        // Saldo awal 50 pcs
        $inventoryService->addStock($this->warehouse->id, $this->part->id, 50, 'INITIAL_STOCK', $this->part->id, 'INIT/1', 'Saldo Awal');

        // Buat Surat Jalan 10 pcs status DRAFT
        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0001',
            'delivery_date' => now(),
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->customer->id,
            'status' => 'DRAFT',
            'created_by' => $this->user->id,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'part_id' => $this->part->id,
            'qty' => 10,
            'unit' => 'PCS',
        ]);

        // Dikonfirmasi -> Stok harus berkurang dari 50 menjadi 40
        $do->status = 'CONFIRMED';
        $do->save();

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(40, $balance->qty_on_hand);

        // Batalkan (VOID) -> Stok harus kembali dari 40 menjadi 50
        $do->status = 'VOID';
        $do->save();

        $balance->refresh();
        $this->assertEquals(50, $balance->qty_on_hand);
    }

    public function test_force_delete_soft_deleted_do_does_not_double_reverse_stock(): void
    {
        $inventoryService = app(InventoryService::class);
        $inventoryService->addStock($this->warehouse->id, $this->part->id, 50, 'INITIAL_STOCK', $this->part->id, 'INIT/2', 'Saldo Awal');

        $do = DeliveryOrder::create([
            'do_number' => 'SJ/202610/0002',
            'delivery_date' => now(),
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->customer->id,
            'status' => 'CONFIRMED',
            'created_by' => $this->user->id,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'part_id' => $this->part->id,
            'qty' => 15,
            'unit' => 'PCS',
        ]);

        // Simulasikan deduction saat create
        $inventoryService->deductStock($this->warehouse->id, $this->part->id, 15, 'DELIVERY_ORDER', $do->id, $do->do_number, 'Potong Stok');

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(35, $balance->qty_on_hand);

        // 1. Soft delete -> Stok dikembalikan (35 + 15 = 50)
        $do->delete();
        $balance->refresh();
        $this->assertEquals(50, $balance->qty_on_hand);

        // 2. Force delete -> Stok TIDAK BOLEH dikembalikan lagi (harus tetap 50)
        $do->forceDelete();
        $balance->refresh();
        $this->assertEquals(50, $balance->qty_on_hand);
    }

    public function test_reconciliation_detects_and_fixes_discrepancy(): void
    {
        $inventoryService = app(InventoryService::class);
        $inventoryService->addStock($this->warehouse->id, $this->part->id, 40, 'INITIAL_STOCK', $this->part->id, 'INIT/3', 'Saldo Awal');

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(40, $balance->qty_on_hand);

        // Sengaja sabotase / rusak balance fisik secara manual tanpa melalui ledger
        $balance->qty_on_hand = 999;
        $balance->save();

        // Jalankan rekonsiliasi
        $result = $inventoryService->reconcileStock();

        $this->assertGreaterThanOrEqual(1, $result['discrepancies']);

        // Saldo fisik harus otomatis kembali ke nilai mutasi ledger asli (40)
        $balance->refresh();
        $this->assertEquals(40, $balance->qty_on_hand);
    }

    public function test_stock_ledger_list_page_has_tabs_for_quick_filtering(): void
    {
        $this->actingAs($this->user);
        $this->user->syncRoles([\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner'])]);

        $page = new \App\Filament\Resources\StockLedgerResource\Pages\ListStockLedgers();
        $tabs = $page->getTabs();

        $this->assertArrayHasKey('all', $tabs);
        $this->assertArrayHasKey('delivery_order', $tabs);
        $this->assertArrayHasKey('goods_receipt', $tabs);
        $this->assertArrayHasKey('adjustment', $tabs);
        $this->assertArrayHasKey('void', $tabs);
        $this->assertArrayHasKey('initial', $tabs);

        $response = $this->get(\App\Filament\Resources\StockLedgerResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('Semua Mutasi');
        $response->assertSee('Surat Jalan (DO)');
        $response->assertSee('Restock Masuk');
        $response->assertSee('Koreksi Stok');
        $response->assertSee('Void / Batal');
    }
}
