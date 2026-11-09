<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverview;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardEstimatedStockValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_overview_displays_estimated_stock_value_based_on_selling_price_not_hpp(): void
    {
        $owner = User::factory()->create();
        $ownerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncRoles([$ownerRole]);
        $this->actingAs($owner);

        $warehouse = Warehouse::firstOrCreate(['code' => 'GMA'], [
            'name' => 'Gudang Mitra Abadi',
            'is_active' => true,
        ]);

        $brand = Brand::create(['name' => 'Caterpillar', 'code' => 'CAT']);
        $cat = Category::create(['name' => 'Filter', 'code' => 'FLT']);

        // Part 1: 10 PCS @ Harga Jual 150.000 (HPP 100.000) -> Nilai Jual = 1.500.000 (Bukan HPP 1.000.000)
        $part1 = Part::create([
            'part_number' => 'CAT-001',
            'name' => 'Oil Filter Heavy',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'cost_price' => 100000,
            'selling_price' => 150000,
            'is_active' => true,
        ]);
        StockBalance::create([
            'part_id' => $part1->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 10,
        ]);

        // Part 2: 5 PCS @ Harga Jual 80.000 (HPP 50.000) -> Nilai Jual = 400.000 (Bukan HPP 250.000)
        $part2 = Part::create([
            'part_number' => 'CAT-002',
            'name' => 'Air Filter Heavy',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'cost_price' => 50000,
            'selling_price' => 80000,
            'is_active' => true,
        ]);
        StockBalance::create([
            'part_id' => $part2->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 5,
        ]);

        // Total Nilai Jual = 1.500.000 + 400.000 = Rp 1.900.000
        // (Jika dari HPP = 1.000.000 + 250.000 = 1.250.000)
        // Kita buktikan bahwa nilai yang muncul adalah Rp 1.900.000 (berdasarkan HARGA JUAL)
        $component = Livewire::test(StatsOverview::class);
        $component->assertSuccessful();
        $component->assertSee('Estimasi Nilai Barang');
        $component->assertSee('Rp 1.900.000');
        $component->assertDontSee('Rp 1.250.000');
        $component->assertSee('Kalkulasi seluruh stok x harga jual');
    }

    public function test_stats_overview_handles_decimal_selling_price_accurately(): void
    {
        $owner = User::factory()->create();
        $ownerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncRoles([$ownerRole]);
        $this->actingAs($owner);

        $warehouse = Warehouse::firstOrCreate(['code' => 'GMA'], [
            'name' => 'Gudang Mitra Abadi',
            'is_active' => true,
        ]);

        $brand = Brand::create(['name' => 'Komatsu Genuine', 'code' => 'KOM']);
        $cat = Category::create(['name' => 'Seal', 'code' => 'SL']);

        // Part dengan harga jual desimal: 10 PCS @ Rp 8.5 -> Total Rp 85
        $part = Part::create([
            'part_number' => 'KOM-888',
            'name' => 'O-Ring Seal',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'cost_price' => 5.0,
            'selling_price' => 8.5,
            'is_active' => true,
        ]);
        StockBalance::create([
            'part_id' => $part->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 10,
        ]);

        $component = Livewire::test(StatsOverview::class);
        $component->assertSuccessful();
        $component->assertSee('Estimasi Nilai Barang');
        $component->assertSee('Rp 85');
    }
}
