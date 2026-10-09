<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Part extends Model
{
    use SoftDeletes;

    //

    public function brand() {
        return $this->belongsTo(Brand::class);
    }
    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function stockBalances() {
        return $this->hasMany(StockBalance::class);
    }
    public function crossReferences() {
        return $this->hasMany(PartCrossReference::class, 'source_part_id');
    }
}

