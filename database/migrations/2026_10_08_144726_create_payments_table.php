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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->date('payment_date');
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'GIRO']);
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('amount', 15, 2);
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
        Schema::dropIfExists('payments');
    }
};
