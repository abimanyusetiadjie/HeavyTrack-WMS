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
        Schema::create('stock_ledgers', function (Blueprint $table) {

            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('part_id')->constrained('parts');
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number', 100);
            $table->integer('qty_change');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['warehouse_id', 'part_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
