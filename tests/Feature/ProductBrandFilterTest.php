<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductBrandFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_can_filter_by_brand(): void
    {
        $user = User::factory()->create(['email' => 'admin_brand_test@heavytrack.com']);

        $brandA = Brand::create(['name' => 'Komatsu', 'code' => 'KOM']);
        $brandB = Brand::create(['name' => 'Caterpillar', 'code' => 'CAT']);
        $cat = Category::create(['name' => 'Filter', 'code' => 'FLT']);

        $partA = Part::create([
            'part_number' => 'KOM-001',
            'name' => 'Komatsu Fuel Filter',
            'brand_id' => $brandA->id,
            'category_id' => $cat->id,
            'selling_price' => 100000,
            'cost_price' => 80000,
        ]);

        $partB = Part::create([
            'part_number' => 'CAT-001',
            'name' => 'CAT Oil Filter',
            'brand_id' => $brandB->id,
            'category_id' => $cat->id,
            'selling_price' => 200000,
            'cost_price' => 150000,
        ]);

        $this->actingAs($user);

        // 1. Verifikasi elemen UI dropdown merk muncul di view
        Livewire::test(ListProducts::class)
            ->assertSee('Semua Merk')
            ->assertSee('Semua Kategori')
            ->assertSee('Komatsu')
            ->assertSee('Caterpillar');

        // 2. Verifikasi logika filtering query di ListProducts
        $component = new ListProducts();
        $method = new \ReflectionMethod($component, 'getTableQuery');
        $method->setAccessible(true);

        // Tanpa filter
        $allRecords = $method->invoke($component)->pluck('name')->toArray();
        $this->assertContains('Komatsu Fuel Filter', $allRecords);
        $this->assertContains('CAT Oil Filter', $allRecords);

        // Filter Merk A
        $component->filterBrand = (string) $brandA->id;
        $recordsA = $method->invoke($component)->pluck('name')->toArray();
        $this->assertContains('Komatsu Fuel Filter', $recordsA);
        $this->assertNotContains('CAT Oil Filter', $recordsA);

        // Filter Merk B
        $component->filterBrand = (string) $brandB->id;
        $recordsB = $method->invoke($component)->pluck('name')->toArray();
        $this->assertNotContains('Komatsu Fuel Filter', $recordsB);
        $this->assertContains('CAT Oil Filter', $recordsB);

        // Filter Kategori
        $component->filterBrand = '';
        $component->filterCategory = (string) $cat->id;
        $recordsCat = $method->invoke($component)->pluck('name')->toArray();
        $this->assertContains('Komatsu Fuel Filter', $recordsCat);
        $this->assertContains('CAT Oil Filter', $recordsCat);
    }
}
