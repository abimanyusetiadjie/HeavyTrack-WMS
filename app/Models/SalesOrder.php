<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SalesOrder extends Model
{
    use SoftDeletes;

    //

    public function contact() {
        return $this->belongsTo(Contact::class);
    }
    public function deliveryOrders() {
        return $this->hasMany(DeliveryOrder::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

