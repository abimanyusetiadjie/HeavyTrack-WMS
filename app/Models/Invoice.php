<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Invoice extends Model
{
    //

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

