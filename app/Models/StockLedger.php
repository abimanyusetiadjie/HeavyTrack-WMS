<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class StockLedger extends Model
{
    //

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
    public function part() {
        return $this->belongsTo(Part::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

