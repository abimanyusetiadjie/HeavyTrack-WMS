<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari ID kategori Element Assy dan Heavy Equipment Filter (case-insensitive)
        $categoriesToDelete = DB::table('categories')
            ->whereRaw("LOWER(name) IN ('element assy', 'heavy equipment filter')")
            ->pluck('id');

        if ($categoriesToDelete->isNotEmpty()) {
            // Lepas relasi category_id di tabel parts
            DB::table('parts')
                ->whereIn('category_id', $categoriesToDelete)
                ->update(['category_id' => null]);

            // Hapus data kategori
            DB::table('categories')
                ->whereIn('id', $categoriesToDelete)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
