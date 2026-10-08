<?php

$migrationsPath = __DIR__ . '/database/migrations';
$modelsPath = __DIR__ . '/app/Models';

$schemas = [
    'brands' => "
            \$table->id();
            \$table->string('name', 100)->unique();
            \$table->string('code', 30)->unique()->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
",
    'categories' => "
            \$table->id();
            \$table->string('name', 100)->unique();
            \$table->string('code', 50)->unique()->nullable();
            \$table->timestamps();
            \$table->softDeletes();
",
    'parts' => "
            \$table->id();
            \$table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            \$table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            \$table->string('part_number', 100);
            \$table->string('clean_part_number', 100);
            \$table->string('name', 255);
            \$table->text('description')->nullable();
            \$table->string('unit', 20)->default('PCS');
            \$table->string('bin_location', 50)->nullable();
            \$table->integer('min_stock_level')->default(5);
            \$table->decimal('cost_price', 15, 2)->default(0.00);
            \$table->decimal('selling_price', 15, 2)->default(0.00);
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
            \$table->unique(['brand_id', 'clean_part_number']);
            \$table->index('clean_part_number');
            \$table->index('bin_location');
",
    'part_cross_references' => "
            \$table->id();
            \$table->foreignId('source_part_id')->constrained('parts')->cascadeOnDelete();
            \$table->string('target_part_number', 100);
            \$table->string('target_brand_name', 100)->nullable();
            \$table->string('notes', 255)->nullable();
            \$table->timestamps();
            \$table->softDeletes();
            \$table->index('target_part_number');
",
    'warehouses' => "
            \$table->id();
            \$table->string('code', 20)->unique();
            \$table->string('name', 100);
            \$table->text('address')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
",
    'stock_balances' => "
            \$table->id();
            \$table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            \$table->foreignId('part_id')->constrained('parts')->restrictOnDelete();
            \$table->integer('qty_on_hand')->default(0);
            \$table->integer('qty_reserved')->default(0);
            \$table->timestamps();
            \$table->unique(['warehouse_id', 'part_id']);
",
    'stock_ledgers' => "
            \$table->id();
            \$table->foreignId('warehouse_id')->constrained('warehouses');
            \$table->foreignId('part_id')->constrained('parts');
            \$table->string('reference_type', 50);
            \$table->unsignedBigInteger('reference_id');
            \$table->string('reference_number', 100);
            \$table->integer('qty_change');
            \$table->integer('balance_before');
            \$table->integer('balance_after');
            \$table->text('notes')->nullable();
            \$table->foreignId('created_by')->constrained('users');
            \$table->timestamps();
            \$table->index(['warehouse_id', 'part_id', 'created_at']);
",
    'contacts' => "
            \$table->id();
            \$table->enum('type', ['CUSTOMER', 'SUPPLIER', 'BOTH']);
            \$table->string('company_name', 255);
            \$table->string('pic_name', 100)->nullable();
            \$table->string('phone', 50)->nullable();
            \$table->string('email', 100)->nullable();
            \$table->text('address')->nullable();
            \$table->string('tax_number_npwp', 50)->nullable();
            \$table->integer('term_of_payment_days')->default(0);
            \$table->timestamps();
            \$table->softDeletes();
",
    'sales_orders' => "
            \$table->id();
            \$table->string('order_number', 50)->unique();
            \$table->foreignId('contact_id')->constrained('contacts');
            \$table->date('order_date');
            \$table->enum('status', ['DRAFT', 'CONFIRMED', 'DELIVERED', 'CANCELLED'])->default('DRAFT');
            \$table->decimal('total_amount', 15, 2)->default(0.00);
            \$table->foreignId('created_by')->constrained('users');
            \$table->timestamps();
            \$table->softDeletes();
",
    'delivery_orders' => "
            \$table->id();
            \$table->string('do_number', 50)->unique();
            \$table->foreignId('sales_order_id')->nullable()->constrained('sales_orders');
            \$table->foreignId('warehouse_id')->constrained('warehouses');
            \$table->foreignId('contact_id')->constrained('contacts');
            \$table->date('delivery_date');
            \$table->string('driver_name', 100)->nullable();
            \$table->string('vehicle_plate_number', 30)->nullable();
            \$table->enum('status', ['ISSUED', 'RECEIVED', 'VOID'])->default('ISSUED');
            \$table->text('notes')->nullable();
            \$table->foreignId('created_by')->constrained('users');
            \$table->timestamps();
            \$table->softDeletes();
",
    'delivery_order_items' => "
            \$table->id();
            \$table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            \$table->foreignId('part_id')->constrained('parts');
            \$table->integer('qty');
            \$table->string('unit', 20);
            \$table->string('bin_location_snapshot', 50)->nullable();
            \$table->string('notes', 255)->nullable();
",
    'invoices' => "
            \$table->id();
            \$table->string('invoice_number', 50)->unique();
            \$table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders');
            \$table->foreignId('contact_id')->constrained('contacts');
            \$table->date('invoice_date');
            \$table->date('due_date');
            \$table->decimal('subtotal', 15, 2)->default(0.00);
            \$table->decimal('discount_amount', 15, 2)->default(0.00);
            \$table->decimal('tax_percent', 5, 2)->default(11.00);
            \$table->decimal('tax_amount', 15, 2)->default(0.00);
            \$table->decimal('grand_total', 15, 2)->default(0.00);
            \$table->decimal('paid_amount', 15, 2)->default(0.00);
            \$table->enum('status', ['UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID'])->default('UNPAID');
            \$table->foreignId('created_by')->constrained('users');
            \$table->timestamps();
            \$table->softDeletes();
",
    'invoice_items' => "
            \$table->id();
            \$table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            \$table->foreignId('part_id')->constrained('parts');
            \$table->string('part_number_snapshot', 100);
            \$table->string('part_name_snapshot', 255);
            \$table->integer('qty');
            \$table->string('unit', 20);
            \$table->decimal('unit_price', 15, 2);
            \$table->decimal('discount_percent', 5, 2)->default(0.00);
            \$table->decimal('total_price', 15, 2);
",
    'payments' => "
            \$table->id();
            \$table->string('receipt_number', 50)->unique();
            \$table->foreignId('invoice_id')->constrained('invoices');
            \$table->date('payment_date');
            \$table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'GIRO']);
            \$table->string('bank_name', 100)->nullable();
            \$table->string('reference_number', 100)->nullable();
            \$table->decimal('amount', 15, 2);
            \$table->text('notes')->nullable();
            \$table->foreignId('created_by')->constrained('users');
            \$table->timestamps();
            \$table->softDeletes();
"
];

$files = scandir($migrationsPath);
foreach ($files as $file) {
    if (str_ends_with($file, '.php')) {
        $content = file_get_contents($migrationsPath . '/' . $file);
        
        foreach ($schemas as $table => $schema) {
            if (str_contains($file, 'create_' . $table . '_table')) {
                $pattern = '/Schema::create\(\'' . $table . '\', function \(Blueprint \$table\) \{.*?\}\);/s';
                $replacement = "Schema::create('$table', function (Blueprint \$table) {\n$schema        });";
                $newContent = preg_replace($pattern, $replacement, $content);
                file_put_contents($migrationsPath . '/' . $file, $newContent);
                break;
            }
        }
    }
}
echo "Migrations updated.\n";
