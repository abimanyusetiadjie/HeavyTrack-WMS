<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('DRAFT', 'UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID') NOT NULL DEFAULT 'UNPAID'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE invoices SET status = 'UNPAID' WHERE status = 'DRAFT'");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID') NOT NULL DEFAULT 'UNPAID'");
        }
    }
};
