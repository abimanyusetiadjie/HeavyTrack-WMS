<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function salesOrders() {
        return $this->hasMany(SalesOrder::class);
    }

    // Fallback accessor & mutator jika ada yang memanggil contact_person
    public function getContactPersonAttribute()
    {
        return $this->pic_name;
    }

    public function setContactPersonAttribute($value)
    {
        $this->attributes['pic_name'] = $value;
    }
}
