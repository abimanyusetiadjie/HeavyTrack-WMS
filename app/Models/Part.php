<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'cost_price' => 'float',
        'selling_price' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Domain Model Lifecycle Hook:
     * Menjamin clean_part_number selalu terisi dan tersinkronisasi dengan part_number
     * tanpa tergantung apakah data diinput melalui UI, Seeder, atau API.
     */
    protected static function booted(): void
    {
        static::saving(function (Part $part) {
            if ($part->isDirty('part_number') || empty($part->clean_part_number)) {
                $part->clean_part_number = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($part->part_number ?? '')));
            }
        });
    }

    /**
     * Data Masking Server-Side (RBAC Lapis Backend):
     * Jika pengguna login BUKAN Owner, nilai cost_price (HPP / Modal) disensor menjadi 0.00
     * sehingga browser/inspect element/network tab tidak pernah menerima data harga modal asli.
     */
    public function getCostPriceAttribute($value): float
    {
        // Izinkan pembacaan asli jika berjalan di background task CLI (misal artisan) tanpa user context
        if (app()->runningInConsole() && ! auth()->check()) {
            return (float) ($value ?? 0);
        }

        $user = auth()->user();
        if (! $user || ! $user->isOwner()) {
            return 0.00;
        }

        return (float) ($value ?? 0);
    }

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
