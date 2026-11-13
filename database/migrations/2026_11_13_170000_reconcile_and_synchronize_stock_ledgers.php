<?php

use App\Services\InventoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            Schema::hasTable('parts') &&
            Schema::hasTable('warehouses') &&
            Schema::hasTable('stock_balances') &&
            Schema::hasTable('stock_ledgers')
        ) {
            app(InventoryService::class)->reconcileStock();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rekonsiliasi integritas data bersifat permanen
    }
};
