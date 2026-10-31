<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartPriceMutatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_mutators_sanitize_dot_and_comma_formats(): void
    {
        $brand = Brand::create(['name' => 'Fleetguard', 'code' => 'FLT']);
        $cat = Category::create(['name' => 'Fuel Filter', 'code' => 'FF']);

        // Test with string containing Indonesian thousands dot
        $part = Part::create([
            'part_number' => 'FS36231',
            'name' => 'Fuel Water Separator',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'cost_price' => '23.000',
            'selling_price' => '155.000',
        ]);

        $this->assertEquals(23000, $part->getRawOriginal('cost_price'));
        $this->assertEquals(155000, $part->getRawOriginal('selling_price'));

        // Test with Rp prefix and spaces
        $part->update([
            'cost_price' => 'Rp 120.000',
            'selling_price' => 'Rp 180.000',
        ]);

        $this->assertEquals(120000, $part->getRawOriginal('cost_price'));
        $this->assertEquals(180000, $part->getRawOriginal('selling_price'));

        // Test with numeric float
        $part->update([
            'cost_price' => 75000.50,
            'selling_price' => 95000,
        ]);

        $this->assertEquals(75000.50, $part->getRawOriginal('cost_price'));
        $this->assertEquals(95000, $part->getRawOriginal('selling_price'));
    }
}
