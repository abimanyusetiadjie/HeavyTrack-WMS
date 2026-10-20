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
        // Update data faktur yang sudah ada: hilangkan PPN 11% sehingga Grand Total = Subtotal - Diskon
        DB::table('invoices')->update([
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => DB::raw('subtotal - discount_amount'),
        ]);

        // Ubah default kolom tax_percent dan tax_amount menjadi 0 di MySQL
        try {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        } catch (\Throwable $e) {
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
