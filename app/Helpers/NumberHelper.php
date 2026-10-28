<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Mengubah angka menjadi kalimat terbilang bahasa Indonesia.
     * Contoh: 1250000 -> "Satu Juta Dua Ratus Lima Puluh Ribu"
     */
    public static function terbilang(int|float $number): string
    {
        $number = (int) floor(abs((float) $number));

        if ($number === 0) {
            return 'Nol';
        }

        return trim(self::convertNumberToWords($number));
    }

    protected static function convertNumberToWords(int $number): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($number < 12) {
            return ' ' . $words[$number];
        }

        if ($number < 20) {
            return self::convertNumberToWords($number - 10) . ' Belas';
        }

        if ($number < 100) {
            $ten = (int) ($number / 10);
            $mod = $number % 10;
            return self::convertNumberToWords($ten) . ' Puluh' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 200) {
            $mod = $number - 100;
            return ' Seratus' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 1000) {
            $hundred = (int) ($number / 100);
            $mod = $number % 100;
            return self::convertNumberToWords($hundred) . ' Ratus' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 2000) {
            $mod = $number - 1000;
            return ' Seribu' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 1000000) {
            $thousand = (int) ($number / 1000);
            $mod = $number % 1000;
            return self::convertNumberToWords($thousand) . ' Ribu' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 1000000000) {
            $million = (int) ($number / 1000000);
            $mod = $number % 1000000;
            return self::convertNumberToWords($million) . ' Juta' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        if ($number < 1000000000000) {
            $billion = (int) ($number / 1000000000);
            $mod = $number % 1000000000;
            return self::convertNumberToWords($billion) . ' Miliar' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
        }

        $trillion = (int) ($number / 1000000000000);
        $mod = $number % 1000000000000;
        return self::convertNumberToWords($trillion) . ' Triliun' . ($mod > 0 ? self::convertNumberToWords($mod) : '');
    }

    /**
     * Format angka ke Rupiah dengan kalimat terbilang lengkap.
     * Contoh: 2500000 -> "Dua Juta Lima Ratus Ribu Rupiah"
     */
    public static function terbilangRupiah(int|float $number): string
    {
        $words = self::terbilang($number);
        return $words . ' Rupiah';
    }
}
