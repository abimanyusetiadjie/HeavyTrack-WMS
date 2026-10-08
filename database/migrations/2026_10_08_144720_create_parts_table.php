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
        Schema::create('parts', function (Blueprint $table) {

            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('part_number', 100);
            $table->string('clean_part_number', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('unit', 20)->default('PCS');
            $table->string('bin_location', 50)->nullable();
            $table->integer('min_stock_level')->default(5);
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('selling_price', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['brand_id', 'clean_part_number']);
            $table->index('clean_part_number');
            $table->index('bin_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
