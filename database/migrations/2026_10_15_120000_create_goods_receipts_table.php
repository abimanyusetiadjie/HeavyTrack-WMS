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
        if (!Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->string('supplier_do_number', 100)->nullable();
                $table->string('po_reference_number', 100)->nullable();
                $table->date('receipt_date');
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('contact_id')->nullable()->constrained('contacts');
                $table->enum('status', ['DRAFT', 'RECEIVED', 'VOID'])->default('RECEIVED');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
                $table->foreignId('part_id')->constrained('parts');
                $table->integer('qty_received');
                $table->string('unit', 20)->default('PCS');
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->string('bin_location_snapshot', 50)->nullable();
                $table->string('notes', 255)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
