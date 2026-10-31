<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'tax_percent' => 'float',
        'tax_amount' => 'float',
        'grand_total' => 'float',
        'paid_amount' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function (Invoice $invoice) {
            if (!empty($invoice->invoice_number)) {
                $invoice->invoice_number = mb_strtoupper($invoice->invoice_number, 'UTF-8');
            }
        });
    }

    public function deliveryOrder() {
        return $this->belongsTo(DeliveryOrder::class);
    }
    public function contact() {
        return $this->belongsTo(Contact::class);
    }
    public function items() {
        return $this->hasMany(InvoiceItem::class);
    }
    public function payments() {
        return $this->hasMany(Payment::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
