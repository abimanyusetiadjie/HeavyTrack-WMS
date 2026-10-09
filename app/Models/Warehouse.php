<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Warehouse extends Model
{
    use SoftDeletes;

    //

    public function stockBalances() {
        return $this->hasMany(StockBalance::class);
    }
}

