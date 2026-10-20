<?php

namespace Tests\Unit;

use App\Services\DonHangHoanThanh\ComboRowExpander;
use PHPUnit\Framework\TestCase;

class ComboRowExpanderTest extends TestCase
{
    public function test_it_creates_one_physical_item_row_for_each_combo_color(): void
    {
        $rows = (new ComboRowExpander)->expand([
            'mau' => 'Đen+be',
            'size' => 'XL',
            'so_luong' => 3.0,
            'thanh_tien' => 200000.0,
        ], ['Đen', 'be']);

        $this->assertCount(2, $rows);
        $this->assertSame('Đen', $rows[0]['mau']);
        $this->assertSame(3.0, $rows[0]['so_luong']);
        $this->assertSame('be', $rows[1]['mau']);
        $this->assertSame(3.0, $rows[1]['so_luong']);
    }

    public function test_it_allocates_money_without_increasing_the_order_total(): void
    {
        $rows = (new ComboRowExpander)->expand([
            'mau' => 'Đen+be+Trắng',
            'size' => 'L',
            'so_luong' => 1.0,
            'thanh_tien' => 100000.0,
        ], ['Đen', 'be', 'Trắng']);

        $this->assertSame([33333.34, 33333.33, 33333.33], array_column($rows, 'thanh_tien'));
        $this->assertSame(100000.0, array_sum(array_column($rows, 'thanh_tien')));
    }

    public function test_it_adds_repeated_combo_colors_into_one_inventory_row(): void
    {
        $rows = (new ComboRowExpander)->expand([
            'mau' => 'Trắng+Trắng',
            'size' => 'M',
            'so_luong' => 2.0,
            'thanh_tien' => 180000.0,
        ], ['Trắng', 'Trắng']);

        $this->assertCount(1, $rows);
        $this->assertSame('Trắng', $rows[0]['mau']);
        $this->assertSame(4.0, $rows[0]['so_luong']);
        $this->assertSame(180000.0, $rows[0]['thanh_tien']);
    }

    public function test_it_marks_rows_with_the_number_of_physical_combo_items(): void
    {
        $rows = (new ComboRowExpander)->expand([
            'mau' => 'Đen+be',
            'size' => 'XL',
            'so_luong' => 1.0,
            'thanh_tien' => 200000.0,
        ], ['Đen', 'be']);

        $this->assertSame([2, 2], array_column($rows, 'combo_count'));
    }
}
