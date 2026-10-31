<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockAndPriceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_fields_format_empty_when_zero_and_can_update_cleanly(): void
    {
        $owner = User::factory()->create();
        $ownerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncRoles([$ownerRole]);
        $this->actingAs($owner);

        $brand = Brand::create(['name' => 'Komatsu', 'code' => 'KOM']);
        $cat = Category::create(['name' => 'Filter', 'code' => 'FLT']);

        // 1. Buat produk baru dengan harga 0
        $part = Part::create([
            'part_number' => 'KOM-100',
            'name' => 'Komatsu Air Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'selling_price' => 0,
            'cost_price' => 0,
            'min_stock_level' => 5,
        ]);

        // Verifikasi format table kolom
        $columns = ProductResource::table(new \Filament\Tables\Table(new \App\Filament\Resources\ProductResource\Pages\ListProducts()))->getColumns();
        $this->assertArrayHasKey('cost_price', $columns);
        $this->assertArrayHasKey('selling_price', $columns);

        $costCol = $columns['cost_price'];
        $sellingCol = $columns['selling_price'];

        $costCol->record($part);
        $sellingCol->record($part);

        $this->assertEquals('-', $costCol->formatState(0));
        $this->assertEquals('-', $sellingCol->formatState(0));
        $this->assertEquals('Rp 150.000', $sellingCol->formatState(150000));
        $this->assertEquals('Rp 100.000', $costCol->formatState(100000));

        // 2. Update harga dengan format rupiah
        $part->update([
            'selling_price' => '250.000',
            'cost_price' => '175.000',
        ]);

        $part->refresh();
        $this->assertEquals(250000, $part->selling_price);
        $this->assertEquals(175000, $part->cost_price);
    }

    public function test_brand_and_category_select_are_preloaded_and_models_uppercase(): void
    {
        $owner = User::factory()->create();
        $ownerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncRoles([$ownerRole]);
        $this->actingAs($owner);

        $brand = Brand::firstOrCreate(['name' => 'VOLVO HEAVY'], ['code' => 'VLV']);
        $cat = Category::firstOrCreate(['name' => 'HYDRAULIC FILTER'], ['code' => 'HF']);

        // Test Livewire Create Product form fields exist
        \Livewire\Livewire::test(\App\Filament\Resources\ProductResource\Pages\CreateProduct::class)
            ->assertFormFieldExists('brand_id')
            ->assertFormFieldExists('category_id');

        // Test Create Product page renders preloaded brands and categories
        $response = $this->get(ProductResource::getUrl('create'));
        $response->assertSuccessful();
        $response->assertSee('VOLVO HEAVY');
        $response->assertSee('HYDRAULIC FILTER');
    }
}
