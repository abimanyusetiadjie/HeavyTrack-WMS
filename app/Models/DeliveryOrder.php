<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected static function booted()
    {
        static::saving(function (DeliveryOrder $do) {
            if (!empty($do->do_number)) {
                $do->do_number = mb_strtoupper($do->do_number, 'UTF-8');
            }
            if (!empty($do->driver_name)) {
                $do->driver_name = mb_strtoupper($do->driver_name, 'UTF-8');
            }
            if (!empty($do->vehicle_plate_number)) {
                $do->vehicle_plate_number = mb_strtoupper($do->vehicle_plate_number, 'UTF-8');
            }
            if (!empty($do->notes)) {
                $do->notes = mb_strtoupper($do->notes, 'UTF-8');
            }
        });
    }

    public function invoice() {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }

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
