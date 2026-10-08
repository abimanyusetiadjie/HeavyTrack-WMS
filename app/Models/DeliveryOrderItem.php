<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DeliveryOrderItem extends Model
{
    //

    public function deliveryOrder() {
        return $this->belongsTo(DeliveryOrder::class);
    }
    public function part() {
        return $this->belongsTo(Part::class);
    }
}

