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
        Schema::create('delivery_orders', function (Blueprint $table) {

            $table->id();
            $table->string('do_number', 50)->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('contact_id')->constrained('contacts');
            $table->date('delivery_date');
            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_plate_number', 30)->nullable();
            $table\->enum('status', ['DRAFT', 'ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED', 'VOID'])->default('ISSUED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
