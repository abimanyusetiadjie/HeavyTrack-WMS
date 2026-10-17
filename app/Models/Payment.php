<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Payment extends Model
{
    use SoftDeletes;

    protected $casts = [
        'amount' => 'float',
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

