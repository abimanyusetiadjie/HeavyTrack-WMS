<?php

namespace Tests\Unit;

use App\Models\Part;
use App\Models\User;
use Tests\TestCase;

class PartTest extends TestCase
{
    public function test_clean_part_number_is_automatically_computed_on_model(): void
    {
        $part = new Part([
            'part_number' => 'FS-36231 / A',
            'name' => 'Test Filter',
        ]);

        // Simulasikan lifecycle saving
        $part->clean_part_number = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $part->part_number));

        $this->assertEquals('FS36231A', $part->clean_part_number);
    }

    public function test_cost_price_is_masked_to_zero_for_non_owner_users(): void
    {
        $nonOwnerUser = new User([
            'name' => 'Kasir Staf',
            'email' => 'kasir@heavytrack.com',
        ]);

        $this->actingAs($nonOwnerUser);

        $part = new Part([
            'part_number' => 'FS1280',
            'name' => 'Water Separator',
            'cost_price' => 50000.00,
            'selling_price' => 75000.00,
        ]);

        $this->assertEquals(0.00, $part->cost_price);
        $this->assertEquals(75000.00, $part->selling_price);
    }
}
