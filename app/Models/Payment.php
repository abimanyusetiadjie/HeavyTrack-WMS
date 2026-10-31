<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function (Payment $payment) {
            if (!empty($payment->receipt_number)) {
                $payment->receipt_number = mb_strtoupper($payment->receipt_number, 'UTF-8');
            }
            if (!empty($payment->reference_number)) {
                $payment->reference_number = mb_strtoupper($payment->reference_number, 'UTF-8');
            }
            if (!empty($payment->bank_name)) {
                $payment->bank_name = mb_strtoupper($payment->bank_name, 'UTF-8');
            }
            if (!empty($payment->notes)) {
                $payment->notes = mb_strtoupper($payment->notes, 'UTF-8');
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
