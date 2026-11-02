<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductWarehouseColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_table_replaces_active_column_with_warehouse_column(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_wh@heavytrack.com',
            'name' => 'Owner Wh',
        ]);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI']);
        $gar = Warehouse::firstOrCreate(['code' => 'GAR'], ['name' => 'GUDANG ANEKARASA']);

        $brand = Brand::firstOrCreate(['name' => 'Komatsu'], ['code' => 'KOM']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        // Part 1: Hanya ada di Gudang Mitra Abadi
        $partGma = Part::create([
            'part_number' => 'GMA-001',
            'name' => 'Filter Mitra Abadi',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'unit' => 'PCS',
        ]);
        StockBalance::create([
            'part_id' => $partGma->id,
            'warehouse_id' => $gma->id,
            'qty_on_hand' => 100,
        ]);

        // Part 2: Hanya ada di Gudang Anekarasa
        $partGar = Part::create([
            'part_number' => 'GAR-001',
            'name' => 'Filter Anekarasa',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'unit' => 'PCS',
        ]);
        StockBalance::create([
            'part_id' => $partGar->id,
            'warehouse_id' => $gar->id,
            'qty_on_hand' => 50,
        ]);

        // Part 3: Ada di kedua gudang (GMA & GAR)
        $partBoth = Part::create([
            'part_number' => 'BOTH-001',
            'name' => 'Filter Dual Warehouse',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'unit' => 'PCS',
        ]);
        StockBalance::create([
            'part_id' => $partBoth->id,
            'warehouse_id' => $gma->id,
            'qty_on_hand' => 80,
        ]);
        StockBalance::create([
            'part_id' => $partBoth->id,
            'warehouse_id' => $gar->id,
            'qty_on_hand' => 20,
        ]);

        // 1. Verifikasi definisi kolom tabel
        $columns = ProductResource::table(new Table(new ListProducts()))->getColumns();
        $this->assertArrayNotHasKey('is_active', $columns, 'Kolom is_active harus sudah digantikan');
        $this->assertArrayHasKey('warehouse_locations', $columns, 'Kolom warehouse_locations (Gudang) harus ada');
        $this->assertEquals('Gudang', $columns['warehouse_locations']->getLabel());

        // 2. Verifikasi rendering Livewire tabel produk
        $this->actingAs($owner);
        Livewire::test(ListProducts::class)
            ->assertSuccessful()
            ->assertTableColumnExists('warehouse_locations')
            ->assertCanSeeTableRecords([$partGma, $partGar, $partBoth])
            ->assertSee('Gudang Mitra Abadi')
            ->assertSee('Gudang Anekarasa')
            ->assertSee('Mitra Abadi (80 PCS)')
            ->assertSee('Anekarasa (20 PCS)');

        // 3. Verifikasi filter gudang
        Livewire::test(ListProducts::class)
            ->filterTable('warehouse', 'GAR')
            ->assertCanSeeTableRecords([$partGar, $partBoth])
            ->assertCanNotSeeTableRecords([$partGma]);

        Livewire::test(ListProducts::class)
            ->filterTable('warehouse', 'GMA')
            ->assertCanSeeTableRecords([$partGma, $partBoth])
            ->assertCanNotSeeTableRecords([$partGar]);
    }

    public function test_edit_and_create_product_form_has_lokasi_gudang_dropdown(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_form_wh@heavytrack.com',
            'name' => 'Owner Form Wh',
        ]);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI']);
        $gar = Warehouse::firstOrCreate(['code' => 'GAR'], ['name' => 'GUDANG ANEKARASA']);

        $brand = Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $part = Part::create([
            'part_number' => 'HIT-999',
            'name' => 'Fuel Filter Element',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'bin_location' => 'Gudang Mitra Abadi',
        ]);

        $this->actingAs($owner);

        // 1. Verifikasi form Edit Produk memiliki komponen Select dengan label 'Lokasi Gudang'
        Livewire::test(\App\Filament\Resources\ProductResource\Pages\EditProduct::class, ['record' => $part->id])
            ->assertSuccessful()
            ->assertFormFieldExists('bin_location', function (\Filament\Forms\Components\Select $field) {
                return $field->getLabel() === 'Lokasi Gudang';
            })
            ->fillForm([
                'bin_location' => 'Gudang Anekarasa',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // 2. Verifikasi data tersimpan sebagai Gudang Anekarasa
        $this->assertEquals('Gudang Anekarasa', $part->fresh()->bin_location);

        // 3. Verifikasi form Create Produk juga memiliki field Lokasi Gudang
        Livewire::test(\App\Filament\Resources\ProductResource\Pages\CreateProduct::class)
            ->assertSuccessful()
            ->assertFormFieldExists('bin_location', function (\Filament\Forms\Components\Select $field) {
                return $field->getLabel() === 'Lokasi Gudang';
            });
    }
}
