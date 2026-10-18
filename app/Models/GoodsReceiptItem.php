<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'unit_cost' => 'float',
    ];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }
}
