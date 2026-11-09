<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 4)->default(0.0000)->change();
            $table->decimal('selling_price', 15, 4)->default(0.0000)->change();
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->default(0.0000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0.00)->change();
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->default(0.00)->change();
            $table->decimal('selling_price', 15, 2)->default(0.00)->change();
        });
    }
};
