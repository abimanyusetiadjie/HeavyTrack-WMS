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
        Schema::create('part_cross_references', function (Blueprint $table) {

            $table->id();
            $table->foreignId('source_part_id')->constrained('parts')->cascadeOnDelete();
            $table->string('target_part_number', 100);
            $table->string('target_brand_name', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('target_part_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_cross_references');
    }
};
