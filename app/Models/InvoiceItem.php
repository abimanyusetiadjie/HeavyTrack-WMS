<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InvoiceItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'float',
        'discount_percent' => 'float',
        'total_price' => 'float',
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
    public function part() {
        return $this->belongsTo(Part::class);
    }
}

