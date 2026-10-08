<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InvoiceItem extends Model
{
    //

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
    public function part() {
        return $this->belongsTo(Part::class);
    }
}

