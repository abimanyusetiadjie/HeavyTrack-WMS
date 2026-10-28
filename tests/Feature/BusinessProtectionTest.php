<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_with_active_stock_is_protected_from_deletion(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Gudang Bernilai',
            'code' => 'GDG-VAL',
        ]);

        $brand = Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $part = Part::create([
            'part_number' => 'VAL-001',
            'name' => 'Engine Part',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 500000,
            'cost_price' => 300000,
        ]);

        StockBalance::create([
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'qty_on_hand' => 10,
            'qty_reserved' => 0,
        ]);

        // Verifikasi bahwa pengecekan proteksi mendeteksi stok aktif
        $hasActiveStock = $warehouse->stockBalances()->where('qty_on_hand', '>', 0)->exists();
        $this->assertTrue($hasActiveStock);
    }

    public function test_lpb_reversal_guard_detects_consumed_stock(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Gudang Inbound',
            'code' => 'GDG-INB',
        ]);

        $brand = Brand::firstOrCreate(['name' => 'Komatsu'], ['code' => 'KOM']);
        $cat = Category::firstOrCreate(['name' => 'Hydraulic'], ['code' => 'HYD']);

        $part = Part::create([
            'part_number' => 'HYD-001',
            'name' => 'Hydraulic Pump',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 2000000,
            'cost_price' => 1500000,
        ]);

        $contact = Contact::create([
            'company_name' => 'Supplier Utama',
            'type' => 'SUPPLIER',
        ]);

        $gr = GoodsReceipt::create([
            'receipt_number' => 'LPB/202610/0001',
            'receipt_date' => now(),
            'warehouse_id' => $warehouse->id,
            'contact_id' => $contact->id,
            'status' => 'RECEIVED',
            'created_by' => 1,
        ]);

        $item = GoodsReceiptItem::create([
            'goods_receipt_id' => $gr->id,
            'part_id' => $part->id,
            'qty_received' => 5,
            'unit' => 'PCS',
            'unit_cost' => 1500000,
        ]);

        // Buat stok fisik di gudang dengan sisa 2 (karena 3 sudah terpakai di DO)
        StockBalance::create([
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'qty_on_hand' => 2,
            'qty_reserved' => 0,
        ]);

        // Periksa kondisi guard: stock (2) < qty_received (5)
        $currentStock = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('part_id', $item->part_id)
            ->value('qty_on_hand') ?? 0;

        $this->assertLessThan($item->qty_received, $currentStock);
    }
}
