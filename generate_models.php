<?php

$modelsPath = __DIR__ . '/app/Models';

$modelDefinitions = [
    'Brand' => [
        'fillable' => "['name', 'code', 'is_active']",
        'relations' => "
    public function parts() {
        return \$this->hasMany(Part::class);
    }
"
    ],
    'Category' => [
        'fillable' => "['name', 'code']",
        'relations' => "
    public function parts() {
        return \$this->hasMany(Part::class);
    }
"
    ],
    'Part' => [
        'fillable' => "['brand_id', 'category_id', 'part_number', 'clean_part_number', 'name', 'description', 'unit', 'bin_location', 'min_stock_level', 'cost_price', 'selling_price', 'is_active']",
        'relations' => "
    public function brand() {
        return \$this->belongsTo(Brand::class);
    }
    public function category() {
        return \$this->belongsTo(Category::class);
    }
    public function stockBalances() {
        return \$this->hasMany(StockBalance::class);
    }
    public function crossReferences() {
        return \$this->hasMany(PartCrossReference::class, 'source_part_id');
    }
"
    ],
    'PartCrossReference' => [
        'fillable' => "['source_part_id', 'target_part_number', 'target_brand_name', 'notes']",
        'relations' => "
    public function sourcePart() {
        return \$this->belongsTo(Part::class, 'source_part_id');
    }
"
    ],
    'Warehouse' => [
        'fillable' => "['code', 'name', 'address', 'is_active']",
        'relations' => "
    public function stockBalances() {
        return \$this->hasMany(StockBalance::class);
    }
"
    ],
    'StockBalance' => [
        'fillable' => "['warehouse_id', 'part_id', 'qty_on_hand', 'qty_reserved']",
        'relations' => "
    public function warehouse() {
        return \$this->belongsTo(Warehouse::class);
    }
    public function part() {
        return \$this->belongsTo(Part::class);
    }
"
    ],
    'StockLedger' => [
        'fillable' => "['warehouse_id', 'part_id', 'reference_type', 'reference_id', 'reference_number', 'qty_change', 'balance_before', 'balance_after', 'notes', 'created_by']",
        'relations' => "
    public function warehouse() {
        return \$this->belongsTo(Warehouse::class);
    }
    public function part() {
        return \$this->belongsTo(Part::class);
    }
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
"
    ],
    'Contact' => [
        'fillable' => "['type', 'company_name', 'pic_name', 'phone', 'email', 'address', 'tax_number_npwp', 'term_of_payment_days']",
        'relations' => "
    public function salesOrders() {
        return \$this->hasMany(SalesOrder::class);
    }
"
    ],
    'SalesOrder' => [
        'fillable' => "['order_number', 'contact_id', 'order_date', 'status', 'total_amount', 'created_by']",
        'relations' => "
    public function contact() {
        return \$this->belongsTo(Contact::class);
    }
    public function deliveryOrders() {
        return \$this->hasMany(DeliveryOrder::class);
    }
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
"
    ],
    'DeliveryOrder' => [
        'fillable' => "['do_number', 'sales_order_id', 'warehouse_id', 'contact_id', 'delivery_date', 'driver_name', 'vehicle_plate_number', 'status', 'notes', 'created_by']",
        'relations' => "
    public function salesOrder() {
        return \$this->belongsTo(SalesOrder::class);
    }
    public function warehouse() {
        return \$this->belongsTo(Warehouse::class);
    }
    public function contact() {
        return \$this->belongsTo(Contact::class);
    }
    public function items() {
        return \$this->hasMany(DeliveryOrderItem::class);
    }
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
"
    ],
    'DeliveryOrderItem' => [
        'fillable' => "['delivery_order_id', 'part_id', 'qty', 'unit', 'bin_location_snapshot', 'notes']",
        'relations' => "
    public function deliveryOrder() {
        return \$this->belongsTo(DeliveryOrder::class);
    }
    public function part() {
        return \$this->belongsTo(Part::class);
    }
"
    ],
    'Invoice' => [
        'fillable' => "['invoice_number', 'delivery_order_id', 'contact_id', 'invoice_date', 'due_date', 'subtotal', 'discount_amount', 'tax_percent', 'tax_amount', 'grand_total', 'paid_amount', 'status', 'created_by']",
        'relations' => "
    public function deliveryOrder() {
        return \$this->belongsTo(DeliveryOrder::class);
    }
    public function contact() {
        return \$this->belongsTo(Contact::class);
    }
    public function items() {
        return \$this->hasMany(InvoiceItem::class);
    }
    public function payments() {
        return \$this->hasMany(Payment::class);
    }
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
"
    ],
    'InvoiceItem' => [
        'fillable' => "['invoice_id', 'part_id', 'part_number_snapshot', 'part_name_snapshot', 'qty', 'unit', 'unit_price', 'discount_percent', 'total_price']",
        'relations' => "
    public function invoice() {
        return \$this->belongsTo(Invoice::class);
    }
    public function part() {
        return \$this->belongsTo(Part::class);
    }
"
    ],
    'Payment' => [
        'fillable' => "['receipt_number', 'invoice_id', 'payment_date', 'payment_method', 'bank_name', 'reference_number', 'amount', 'notes', 'created_by']",
        'relations' => "
    public function invoice() {
        return \$this->belongsTo(Invoice::class);
    }
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
"
    ]
];

foreach ($modelDefinitions as $model => $def) {
    $filePath = $modelsPath . '/' . $model . '.php';
    if (!file_exists($filePath)) continue;
    
    $content = file_get_contents($filePath);
    
    $useSoftDeletes = in_array($model, ['DeliveryOrderItem', 'InvoiceItem', 'StockBalance']) ? "" : "use Illuminate\Database\Eloquent\SoftDeletes;\n";
    $softDeletesTrait = in_array($model, ['DeliveryOrderItem', 'InvoiceItem', 'StockBalance']) ? "" : "use SoftDeletes;\n    ";
    
    // Add SoftDeletes use statement if not exists
    if (!str_contains($content, 'SoftDeletes')) {
        $content = str_replace("use Illuminate\Database\Eloquent\Model;", "use Illuminate\Database\Eloquent\Model;\n$useSoftDeletes", $content);
        $content = str_replace("use HasFactory;", "use HasFactory;\n    $softDeletesTrait", $content);
    }
    
    // Add fillable
    $fillableCode = "protected \$fillable = " . $def['fillable'] . ";\n";
    if (!str_contains($content, '$fillable')) {
        $content = preg_replace('/(use HasFactory;(\n\s+use SoftDeletes;)?\n)/', "$1\n    $fillableCode", $content);
    }
    
    // Add relations
    $content = preg_replace('/\}$/', $def['relations'] . "}\n", $content);
    
    file_put_contents($filePath, $content);
}

echo "Models updated.\n";
