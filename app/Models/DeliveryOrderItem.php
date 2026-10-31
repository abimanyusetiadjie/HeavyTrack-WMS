<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted()
    {
        static::saving(function (DeliveryOrderItem $item) {
            if (!empty($item->unit)) {
                $item->unit = mb_strtoupper($item->unit, 'UTF-8');
            }
            if (!empty($item->notes)) {
                $item->notes = mb_strtoupper($item->notes, 'UTF-8');
            }
        });
    }

    public function deliveryOrder() {
        return $this->belongsTo(DeliveryOrder::class);
    }
    public function part() {
        return $this->belongsTo(Part::class)->withTrashed();
    }
}
