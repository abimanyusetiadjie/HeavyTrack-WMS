<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class DeliveryOrder extends Model
{
    use SoftDeletes;

    //

    public function salesOrder() {
        return $this->belongsTo(SalesOrder::class);
    }
    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
    public function contact() {
        return $this->belongsTo(Contact::class);
    }
    public function items() {
        return $this->hasMany(DeliveryOrderItem::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

