<?php

namespace Tests\Unit;

use App\Helpers\NumberHelper;
use PHPUnit\Framework\TestCase;

class NumberHelperTest extends TestCase
{
    public function test_terbilang_basic_numbers(): void
    {
        $this->assertEquals('Nol Rupiah', NumberHelper::terbilangRupiah(0));
        $this->assertEquals('Satu Rupiah', NumberHelper::terbilangRupiah(1));
        $this->assertEquals('Sepuluh Rupiah', NumberHelper::terbilangRupiah(10));
        $this->assertEquals('Sebelas Rupiah', NumberHelper::terbilangRupiah(11));
        $this->assertEquals('Dua Belas Rupiah', NumberHelper::terbilangRupiah(12));
        $this->assertEquals('Dua Puluh Rupiah', NumberHelper::terbilangRupiah(20));
        $this->assertEquals('Dua Puluh Lima Rupiah', NumberHelper::terbilangRupiah(25));
    }

    public function test_terbilang_hundreds_and_thousands(): void
    {
        $this->assertEquals('Seratus Rupiah', NumberHelper::terbilangRupiah(100));
        $this->assertEquals('Seratus Lima Rupiah', NumberHelper::terbilangRupiah(105));
        $this->assertEquals('Dua Ratus Lima Puluh Rupiah', NumberHelper::terbilangRupiah(250));
        $this->assertEquals('Seribu Rupiah', NumberHelper::terbilangRupiah(1000));
        $this->assertEquals('Seribu Lima Ratus Rupiah', NumberHelper::terbilangRupiah(1500));
        $this->assertEquals('Dua Ribu Lima Ratus Rupiah', NumberHelper::terbilangRupiah(2500));
    }

    public function test_terbilang_millions_and_billions(): void
    {
        $this->assertEquals('Satu Juta Rupiah', NumberHelper::terbilangRupiah(1000000));
        $this->assertEquals('Dua Juta Lima Ratus Ribu Rupiah', NumberHelper::terbilangRupiah(2500000));
        $this->assertEquals('Seratus Lima Juta Rupiah', NumberHelper::terbilangRupiah(105000000));
        $this->assertEquals('Satu Miliar Rupiah', NumberHelper::terbilangRupiah(1000000000));
    }
}
