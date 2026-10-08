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
        Schema::create('sales_orders', function (Blueprint $table) {

            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('contact_id')->constrained('contacts');
            $table->date('order_date');
            $table->enum('status', ['DRAFT', 'CONFIRMED', 'DELIVERED', 'CANCELLED'])->default('DRAFT');
            $table->decimal('total_amount', 15, 2)->default(0.00);
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
        Schema::dropIfExists('sales_orders');
    }
};
