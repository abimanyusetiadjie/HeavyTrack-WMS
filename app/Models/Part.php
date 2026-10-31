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

    public static function parsePrice($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return 0.0;
        }

        $str = trim($value);

        // Hilangkan prefix "Rp", spasi, dan karakter non-angka/titik/koma
        $str = preg_replace('/[^\d.,]/', '', $str);

        if ($str === '') {
            return 0.0;
        }

        // Jika mengandung kedua tanda (titik DAN koma), misal 1.500.000,50 atau 1,500,000.50
        if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastComma > $lastDot) {
                // Titik = ribuan, koma = desimal: 1.500.000,50
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Koma = ribuan, titik = desimal: 1,500,000.50
                $str = str_replace(',', '', $str);
            }
            return (float) $str;
        }

        // Jika mengandung koma (misal 150,000 atau 1,500,000 atau 2,3 atau 7,5)
        if (strpos($str, ',') !== false) {
            $parts = explode(',', $str);
            if (count($parts) > 2) {
                return (float) str_replace(',', '', $str);
            }
            if (isset($parts[1]) && strlen($parts[1]) === 3 && (int) $parts[0] >= 1) {
                return (float) str_replace(',', '', $str);
            }
            return (float) ($parts[0] . '.' . ($parts[1] ?? '0'));
        }

        // Jika mengandung titik (misal 150.000 atau 1.500.000 atau 2.3 atau 7.5)
        if (strpos($str, '.') !== false) {
            $parts = explode('.', $str);
            if (count($parts) > 2) {
                return (float) str_replace('.', '', $str);
            }
            if (isset($parts[1]) && strlen($parts[1]) === 3 && (int) $parts[0] >= 1) {
                return (float) str_replace('.', '', $str);
            }
            return (float) ($parts[0] . '.' . ($parts[1] ?? '0'));
        }

        return (float) $str;
    }

    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['selling_price'] = self::parsePrice($value);
    }

    public function setCostPriceAttribute($value): void
    {
        $this->attributes['cost_price'] = self::parsePrice($value);
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
