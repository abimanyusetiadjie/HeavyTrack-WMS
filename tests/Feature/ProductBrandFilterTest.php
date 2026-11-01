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

    public function test_products_are_automatically_sorted_and_grouped_by_brand_and_part_number(): void
    {
        $brandHitachi = Brand::create(['name' => 'Hitachi', 'code' => 'HTC']);
        $brandFleetguard = Brand::create(['name' => 'Fleetguard', 'code' => 'FLG']);
        $brandKobelco = Brand::create(['name' => 'Kobelco', 'code' => 'KOB']);

        // Buat item Hitachi 1
        $p1 = Part::create(['part_number' => '4616545', 'name' => 'Fuel Filter 4616545', 'brand_id' => $brandHitachi->id]);
        // Buat item Fleetguard
        $p2 = Part::create(['part_number' => 'FF5052', 'name' => 'Fuel Filter FF5052', 'brand_id' => $brandFleetguard->id]);
        // Buat item Kobelco
        $p3 = Part::create(['part_number' => 'VH23390', 'name' => 'Fuel Filter Element', 'brand_id' => $brandKobelco->id]);
        // Buat item Hitachi 2 (dibuat belakangan dengan ID lebih tinggi)
        $p4 = Part::create(['part_number' => '4616544', 'name' => 'Fuel Filter 4616544', 'brand_id' => $brandHitachi->id]);

        $query = \App\Filament\Resources\ProductResource::getEloquentQuery()
            ->whereIn('id', [$p1->id, $p2->id, $p3->id, $p4->id])
            ->get();

        $orderedPartNumbers = $query->pluck('part_number')->toArray();

        // Urutan harus Fleetguard (FF5052), lalu Hitachi (4616544, 4616545), lalu Kobelco (VH23390)
        $this->assertEquals([
            'FF5052',
            '4616544',
            '4616545',
            'VH23390',
        ], $orderedPartNumbers);
    }
}
