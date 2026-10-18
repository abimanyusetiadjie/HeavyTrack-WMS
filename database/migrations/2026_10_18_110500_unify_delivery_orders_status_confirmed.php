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
        // Pastikan seluruh surat jalan yang aktif (non-VOID) diupdate menjadi CONFIRMED
        DB::table('delivery_orders')
            ->where('status', '!=', 'VOID')
            ->update(['status' => 'CONFIRMED']);

        // Set default value kolom status di MySQL menjadi CONFIRMED
        try {
            DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('DRAFT', 'ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED', 'VOID') NOT NULL DEFAULT 'CONFIRMED'");
        } catch (\Throwable $e) {
            // Ignore if running on SQLite or restricted DB
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('DRAFT', 'ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED', 'VOID') NOT NULL DEFAULT 'ISSUED'");
        } catch (\Throwable $e) {
        }
    }
};
