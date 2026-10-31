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

    protected static function booted()
    {
        static::saving(function (InvoiceItem $item) {
            if (!empty($item->part_number_snapshot)) {
                $item->part_number_snapshot = mb_strtoupper($item->part_number_snapshot, 'UTF-8');
            }
            if (!empty($item->part_name_snapshot)) {
                $item->part_name_snapshot = mb_strtoupper($item->part_name_snapshot, 'UTF-8');
            }
            if (!empty($item->unit)) {
                $item->unit = mb_strtoupper($item->unit, 'UTF-8');
            }
        });
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
    public function part() {
        return $this->belongsTo(Part::class)->withTrashed();
    }
}
