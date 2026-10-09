<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use Illuminate\Support\Facades\DB;

class ClientDataSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['FS36231', 'Fleetguard', 'Fuel Water Separator Filter FS36231', 'Fuel Filter', 'PCS', 'Fleetguard Fuel Water Separator'],
            ['FS1280', 'Fleetguard', 'Fuel Water Separator Filter FS1280', 'Fuel Filter', 'PCS', 'Fleetguard Fuel Filter / Water Separator'],
            ['FS1242', 'Fleetguard', 'Fuel Water Separator Filter FS1242', 'Fuel Filter', 'PCS', 'Fleetguard Fuel Water Separator'],
            ['FF5052', 'Fleetguard', 'Fuel Filter FF5052', 'Fuel Filter', 'PCS', 'Fleetguard Fuel Filter Spin-on'],
            ['LF3349', 'Fleetguard', 'Lube Oil Filter LF3349', 'Oil Filter', 'PCS', 'Fleetguard Lube Filter Full-Flow'],
            ['4616545', 'Hitachi', 'Fuel Filter 4616545', 'Fuel Filter', 'PCS', 'Hitachi Genuine Parts Fuel Filter'],
            ['4616544', 'Hitachi', 'Fuel Filter 4616544', 'Fuel Filter', 'PCS', 'Hitachi Genuine Fuel Filter'],
            ['4658521', 'Hitachi', 'Engine Oil Filter 4658521RCP', 'Oil Filter', 'PCS', 'Hitachi Genuine Engine Oil Filter'],
            ['VH23390-E0020T1', 'Kobelco', 'Fuel Filter Element VH23390-E0020T1', 'Fuel Filter', 'PCS', 'Kobelco Genuine Parts Filter Element'],
            ['YN21P01157R100', 'Kobelco', 'Hydraulic Return Filter YN21P01157R100', 'Hydraulic Filter', 'PCS', 'Kobelco Filter Cartridge'],
            ['523-4987', 'CAT', 'Fuel Filter Element 523-4987', 'Fuel Filter', 'PCS', 'Caterpillar Secondary Fuel Filter Element'],
            ['509-5694', 'CAT', 'Fuel Filter Water Separator 509-5694', 'Fuel Filter', 'PCS', 'Caterpillar Primary Fuel Filter Water Separator'],
            ['53C0574', 'LiuGong', 'Oil Filter Element 53C0574', 'Oil Filter', 'PCS', 'LiuGong Genuine Parts Filter'],
            ['53C0436ID', 'LiuGong', 'Fuel Water Separator 53C0436ID', 'Fuel Filter', 'PCS', 'LiuGong Genuine Parts Fuel Water Separator'],
            ['800194923', 'XCMG', 'Filter Element 800194923', 'Heavy Equipment Filter', 'PCS', 'XCMG Genuine Filter Part'],
            ['2040PM', 'Parker Racor', 'Turbine Series Fuel Filter 2040PM', 'Fuel Filter', 'PCS', 'Parker Racor 30 Micron Fuel Filter Element'],
            ['2020PM', 'Parker Racor', 'Turbine Series Fuel Filter 2020PM', 'Fuel Filter', 'PCS', 'Parker Racor 30 Micron Fuel Filter Element'],
            ['A104-01460', 'SANY', 'Air / Element Filter A104-01460', 'Air Filter', 'PCS', 'Sany Genuine Parts Element Filter'],
            ['20Y-04-J1130', 'Komatsu', 'Element Assy Filter 20Y-04-J1130', 'Hydraulic Filter', 'PCS', 'Komatsu Genuine Parts Element Assy']
        ];

        DB::beginTransaction();
        try {
            foreach ($products as $p) {
                // Get or Create Brand
                $brandName = trim($p[1]);
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName],
                    ['code' => strtoupper(substr(str_replace(' ', '', $brandName), 0, 5))]
                );

                // Get or Create Category
                $catName = trim($p[3]);
                $category = Category::firstOrCreate(
                    ['name' => $catName],
                    ['code' => strtoupper(substr(str_replace(' ', '', $catName), 0, 5))]
                );

                // Create Part
                Part::firstOrCreate(
                    ['part_number' => trim($p[0])],
                    [
                        'name' => trim($p[2]),
                        'brand_id' => $brand->id,
                        'category_id' => $category->id,
                        'uom' => trim($p[4]),
                        'description' => trim($p[5]),
                        'min_stock_level' => 5,
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
