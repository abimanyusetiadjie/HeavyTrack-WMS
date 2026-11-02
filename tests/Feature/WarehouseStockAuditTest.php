<?php

namespace Tests\Feature;

use App\Filament\Resources\WarehouseResource;
use App\Filament\Resources\WarehouseResource\Pages\ListWarehouses;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseStockAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_list_only_counts_active_stock_and_ignores_zero_balances(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_audit@heavytrack.com',
            'name' => 'Owner Audit',
        ]);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI']);
        $gar = Warehouse::firstOrCreate(['code' => 'GAR'], ['name' => 'GUDANG ANEKARASA']);

        $brand = Brand::firstOrCreate(['name' => 'Komatsu'], ['code' => 'KOM']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $part = Part::create([
            'part_number' => 'KOM-12345',
            'name' => 'Hydraulic Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'unit' => 'PCS',
        ]);

        // Simulasikan produk pernah dipindah ke Anekarasa lalu dipindahkan kembali ke Mitra Abadi:
        // Saldo di GMA = 100 PCS
        StockBalance::create([
            'part_id' => $part->id,
            'warehouse_id' => $gma->id,
            'qty_on_hand' => 100,
            'qty_reserved' => 0,
        ]);

        // Row di GAR ada di database, tetapi saldo fisiknya 0 PCS
        StockBalance::create([
            'part_id' => $part->id,
            'warehouse_id' => $gar->id,
            'qty_on_hand' => 0,
            'qty_reserved' => 0,
        ]);

        $this->actingAs($owner);

        // Render tabel Daftar Gudang
        $component = Livewire::test(ListWarehouses::class)
            ->assertSuccessful()
            ->assertTableColumnExists('active_stock_balances_count')
            ->assertTableColumnExists('total_physical_stock');

        // Pastikan relasi activeStockBalances pada model bekerja sesuai ekspektasi
        $this->assertEquals(1, $gma->activeStockBalances()->count());
        $this->assertEquals(0, $gar->activeStockBalances()->count(), 'Gudang Anekarasa harus menghitung 0 jenis part aktif jika saldo fisiknya 0');

        $this->assertEquals(100, (int) $gma->stockBalances()->sum('qty_on_hand'));
        $this->assertEquals(0, (int) $gar->stockBalances()->sum('qty_on_hand'));
    }
}
