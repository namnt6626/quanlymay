<?php

namespace Tests\Feature;

use App\Services\Excel\SimpleXlsxWriter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CompletedOrderComboImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('dm_kenh_ban', function (Blueprint $table): void {
            $table->id();
            $table->string('ten_kenh');
            $table->boolean('trang_thai')->default(true);
            $table->softDeletes();
        });

        DB::table('dm_kenh_ban')->insert([
            'ten_kenh' => 'TikTok',
            'trang_thai' => true,
        ]);

        View::share('errors', new ViewErrorBag);
        $this->withoutMiddleware();
    }

    public function test_standard_order_file_previews_each_combo_color_as_a_physical_item(): void
    {
        $response = $this->preview([
            'Product Name',
            'Variation',
            'Quantity',
            'SKU Subtotal After Discount',
            'Created Time',
        ], [[
            'AL-86',
            'Đen+be, XL',
            1,
            200000,
            '20/10/2026 10:00:00',
        ]]);

        $response->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(2, $rows);
        $this->assertSame(['Đen', 'be'], array_column($rows, 'mau'));
        $this->assertSame([1.0, 1.0], array_column($rows, 'so_luong'));
        $this->assertSame(200000.0, array_sum(array_column($rows, 'thanh_tien')));
        $response->assertSee('Combo 2');
    }

    public function test_vietnamese_shopee_file_expands_combo_colors(): void
    {
        $response = $this->preview([
            'Ngày đặt hàng',
            'SKU phân loại hàng',
            'Tên phân loại hàng',
            'Số lượng',
            'Tổng giá trị đơn hàng (VND)',
        ], [[
            '20/10/2026 10:00:00',
            'AL-86',
            'Đen+be, XL',
            1,
            200000,
        ]]);

        $response->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(2, $rows);
        $this->assertSame(['Đen', 'be'], array_column($rows, 'mau'));
    }

    public function test_product_info_file_expands_combo_colors(): void
    {
        $response = $this->preview(['product_info'], [[
            '[1] Product Name: Áo nữ AL-86; Variation Name: Đen+be, XL; Price: 200000; Quantity: 1; SKU Reference No.: AL-86; Parent SKU Reference No.: AL-86;',
        ]]);

        $response->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(2, $rows);
        $this->assertSame(['Đen', 'be'], array_column($rows, 'mau'));
    }

    private function preview(array $headings, array $rows): TestResponse
    {
        $path = (new SimpleXlsxWriter)->write('Orders', $headings, $rows);

        try {
            return $this->post(route('don-hang-hoan-thanh.preview'), [
                'kenh_ban' => 'TikTok',
                'file_excel' => new UploadedFile(
                    $path,
                    'orders.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);
        } finally {
            @unlink($path);
        }
    }
}
