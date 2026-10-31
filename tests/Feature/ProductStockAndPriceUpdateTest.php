<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockAndPriceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_product_price_and_physical_stock_directly(): void
    {
        $owner = User::factory()->create();
        $ownerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncRoles([$ownerRole]);
        $this->actingAs($owner);

        $warehouse = Warehouse::create([
            'name' => 'Gudang Utama (Pusat)',
            'code' => 'GDG-PUSAT',
            'is_active' => true,
        ]);

        $brand = Brand::create(['name' => 'Komatsu', 'code' => 'KOM']);
        $cat = Category::create(['name' => 'Filter', 'code' => 'FLT']);

        // 1. Create product with initial stock
        $createPage = new CreateProduct();
        $part = Part::create([
            'part_number' => 'KOM-100',
            'name' => 'Komatsu Air Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 150000,
            'cost_price' => 100000,
            'min_stock_level' => 5,
        ]);

        // Simulate afterCreate
        $refProp = new \ReflectionProperty($createPage, 'record');
        $refProp->setAccessible(true);
        $refProp->setValue($createPage, $part);
        $createPage->data = ['initial_stock' => 75];

        $afterCreateMethod = new \ReflectionMethod($createPage, 'afterCreate');
        $afterCreateMethod->setAccessible(true);
        $afterCreateMethod->invoke($createPage);

        // Check stock balance created
        $balance = StockBalance::where('part_id', $part->id)->where('warehouse_id', $warehouse->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(75, $balance->qty_on_hand);

        // Check stock ledger created
        $ledger = StockLedger::where('part_id', $part->id)->latest('id')->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(75, $ledger->qty_change);
        $this->assertEquals(75, $ledger->balance_after);

        // 2. Edit product price and physical stock
        $part->update([
            'selling_price' => 200000,
            'cost_price' => 130000,
        ]);

        $editPage = new EditProduct();
        $refPropEdit = new \ReflectionProperty($editPage, 'record');
        $refPropEdit->setAccessible(true);
        $refPropEdit->setValue($editPage, $part);
        $editPage->data = ['initial_stock' => 120];

        $afterSaveMethod = new \ReflectionMethod($editPage, 'afterSave');
        $afterSaveMethod->setAccessible(true);
        $afterSaveMethod->invoke($editPage);

        // Check updated stock balance
        $balance->refresh();
        $this->assertEquals(120, $balance->qty_on_hand);

        // Check updated stock ledger
        $ledgerEdit = StockLedger::where('part_id', $part->id)->latest('id')->first();
        $this->assertEquals(45, $ledgerEdit->qty_change); // 120 - 75 = +45
        $this->assertEquals(75, $ledgerEdit->balance_before);
        $this->assertEquals(120, $ledgerEdit->balance_after);
        $this->assertEquals('STOCK_ADJUSTMENT', $ledgerEdit->reference_type);
    }
}
