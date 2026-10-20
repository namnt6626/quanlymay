<?php

namespace Tests\Unit;

use App\Services\DonHangHoanThanh\PhanLoaiParser;
use PHPUnit\Framework\TestCase;

class PhanLoaiParserTest extends TestCase
{
    public function test_it_exposes_each_combo_color_separately(): void
    {
        $result = (new PhanLoaiParser)->parse('Đen+be, XL');

        $this->assertSame([
            'mau' => 'Đen+be',
            'size' => 'XL',
            'colors' => ['Đen', 'be'],
        ], $result);
    }

    public function test_it_keeps_a_regular_variation_as_one_color(): void
    {
        $result = (new PhanLoaiParser)->parse('Trắng, M');

        $this->assertSame([
            'mau' => 'Trắng',
            'size' => 'M',
            'colors' => ['Trắng'],
        ], $result);
    }
}
