<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function activeStockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class)->where('qty_on_hand', '>', 0);
    }
}
