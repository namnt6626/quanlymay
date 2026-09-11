<?php

namespace Tests\Feature;

use App\Http\Controllers\DonHangOnline\TonKhoOnlineController;
use App\Http\Controllers\SanXuat\TonKhoController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class StockDateFilterTest extends TestCase
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
        Schema::connection('sqlite')->defaultStringLength(191);

        $this->createSchema();
        View::share('errors', new ViewErrorBag);
    }

    public function test_online_stock_uses_selected_date_range_for_all_movements(): void
    {
        // Catches missing date filters on any of: online import, return, or completed-order movements.
        $this->insertOnlineImport('2026-01-10', 10, 100);
        $this->insertOnlineImport('2026-02-05', 3, 30);
        $this->insertOnlineReturn('2026-01-11', 2);
        $this->insertOnlineReturn('2026-02-06', 1);
        $this->insertOnlineSale('2026-01-12', 4, 80);
        $this->insertOnlineSale('2026-02-07', 2, 40);

        $view = (new TonKhoOnlineController)->index($this->request('/ton-kho-online', [
            'tu_ngay' => '2026-02-01',
            'den_ngay' => '2026-02-28',
        ]));

        $data = $view->getData();
        $row = $data['rows']->items()[0];

        $this->assertArrayHasKey('tu_ngay', $data['filters']);
        $this->assertArrayHasKey('den_ngay', $data['filters']);
        $this->assertSame('2026-02-01', $data['filters']['tu_ngay']);
        $this->assertSame('2026-02-28', $data['filters']['den_ngay']);
        $this->assertSame(3.0, (float) $row['so_luong_nhap']);
        $this->assertSame(1.0, (float) $row['so_luong_hoan']);
        $this->assertSame(2.0, (float) $row['so_luong_xuat']);
        $this->assertSame(2.0, (float) $row['so_luong_ton']);
        $this->assertSame(30.0, (float) $data['totals']['tien_nhap']);
        $this->assertSame(40.0, (float) $data['totals']['tien_xuat']);
    }

    public function test_online_stock_product_summary_groups_detail_rows_by_product(): void
    {
        // Catches replacing the detail tab instead of adding a separate product summary tab.
        $this->insertOnlineImport('2026-02-05', 3, 30, 'AO POLO', 'Do', 'M');
        $this->insertOnlineImport('2026-02-05', 2, 50, 'AO POLO', 'Xanh', 'L');
        $this->insertOnlineReturn('2026-02-06', 1, 'AO POLO', 'Do', 'M');
        $this->insertOnlineReturn('2026-02-06', 4, 'AO POLO', 'Xanh', 'L');
        $this->insertOnlineSale('2026-02-07', 2, 40, 'AO POLO', 'Do', 'M');
        $this->insertOnlineSale('2026-02-07', 1, 20, 'AO POLO', 'Xanh', 'L');
        $this->insertOnlineImport('2026-02-05', 7, 140, 'AO THUN', 'Den', 'S');

        $view = (new TonKhoOnlineController)->index($this->request('/ton-kho-online', [
            'tu_ngay' => '2026-02-01',
            'den_ngay' => '2026-02-28',
        ]));

        $data = $view->getData();
        $detailRows = collect($data['rows']->items());

        $this->assertCount(3, $detailRows);
        $this->assertArrayHasKey('productSummaryRows', $data);

        $summaryRows = collect($data['productSummaryRows']);
        $poloSummary = $summaryRows->firstWhere('ten_san_pham', 'AO POLO');

        $this->assertCount(2, $summaryRows);
        $this->assertNotNull($poloSummary);
        $this->assertSame(5.0, (float) $poloSummary['so_luong_nhap']);
        $this->assertSame(5.0, (float) $poloSummary['so_luong_hoan']);
        $this->assertSame(3.0, (float) $poloSummary['so_luong_xuat']);
        $this->assertSame(7.0, (float) $poloSummary['so_luong_ton']);
        $this->assertSame(80.0, (float) $poloSummary['tien_nhap']);
        $this->assertSame(60.0, (float) $poloSummary['tien_xuat']);
        $this->assertSame(-20.0, (float) $poloSummary['chenh_lech_tien']);
        $this->assertStringContainsString('Tổng theo mã hàng', $view->render());
    }

    public function test_production_stock_uses_selected_date_range_for_all_movements(): void
    {
        // Catches missing date filters on any of: cut, QC, warehouse import, or warehouse export movements.
        $product = $this->insertProductionProduct();
        $detailId = $this->insertProductionOrderDetail($product);

        $oldCutId = $this->insertCut($detailId, $product, '2026-01-10', 10);
        $oldAllocationId = $this->insertAllocation($oldCutId, $detailId, '2026-01-10');
        $oldQcId = $this->insertQc($oldAllocationId, $detailId, $product, '2026-01-11', 8, 0, 0);
        $oldImportId = $this->insertWarehouseImport($oldQcId, $detailId, '2026-01-12', 8, 'dat');
        $this->insertWarehouseExport($oldImportId, $detailId, '2026-01-13', 3);

        $cutId = $this->insertCut($detailId, $product, '2026-02-10', 4);
        $allocationId = $this->insertAllocation($cutId, $detailId, '2026-02-10');
        $qcId = $this->insertQc($allocationId, $detailId, $product, '2026-02-11', 3, 1, 0);
        $importId = $this->insertWarehouseImport($qcId, $detailId, '2026-02-12', 2, 'dat');
        $this->insertWarehouseImport($qcId, $detailId, '2026-02-12', 1, 'loi');
        $this->insertWarehouseExport($importId, $detailId, '2026-02-13', 1);

        $view = (new TonKhoController)->index($this->request('/ton-kho', [
            'tu_ngay' => '2026-02-01',
            'den_ngay' => '2026-02-28',
        ]));

        $data = $view->getData();
        $row = $data['tonKhos']->items()[0];

        $this->assertArrayHasKey('tuNgay', $data);
        $this->assertArrayHasKey('denNgay', $data);
        $this->assertSame('2026-02-01', $data['tuNgay']);
        $this->assertSame('2026-02-28', $data['denNgay']);
        $this->assertSame(4.0, (float) $row->da_cat);
        $this->assertSame(3.0, (float) $row->qc_dat);
        $this->assertSame(1.0, (float) $row->qc_loi);
        $this->assertSame(0.0, (float) $row->qc_hong);
        $this->assertSame(1.0, (float) $row->ton_dat);
        $this->assertSame(1.0, (float) $row->ton_loi);
        $this->assertSame(0.0, (float) $row->ton_hong);
        $this->assertSame(1.0, (float) $row->da_xuat);
        $this->assertSame(2.0, (float) $row->tong_ton_vat_ly);
    }

    private function request(string $uri, array $query): Request
    {
        $request = Request::create($uri, 'GET', ['per_page' => 10] + $query);
        $this->app->instance('request', $request);

        return $request;
    }

    private function createSchema(): void
    {
        Schema::create('dm_mat_hang', function (Blueprint $table): void {
            $table->id();
            $table->string('ma_hang')->nullable();
            $table->string('ten_hang')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->softDeletes();
        });

        Schema::create('dm_mau', function (Blueprint $table): void {
            $table->id();
            $table->string('ma_mau')->nullable();
            $table->string('ten_mau')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->softDeletes();
        });

        Schema::create('dm_size', function (Blueprint $table): void {
            $table->id();
            $table->string('ma_size')->nullable();
            $table->string('ten_size')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->softDeletes();
        });

        Schema::create('don_hangs', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_nhan')->nullable();
            $table->string('ma_don')->nullable();
            $table->string('ma_kh')->nullable();
            $table->softDeletes();
        });

        Schema::create('don_hang_chi_tiets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('don_hang_id')->nullable();
            $table->unsignedBigInteger('mat_hang_id')->nullable();
            $table->unsignedBigInteger('mau_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('so_luong_dat', 15, 4)->default(0);
            $table->softDeletes();
        });

        Schema::create('cat', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_cat')->nullable();
            $table->unsignedBigInteger('don_hang_chi_tiet_id')->nullable();
            $table->unsignedBigInteger('mat_hang_id')->nullable();
            $table->unsignedBigInteger('mau_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('so_luong_cat', 15, 4)->default(0);
            $table->softDeletes();
        });

        Schema::create('phan_bo_may', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cat_id')->nullable();
            $table->unsignedBigInteger('don_hang_chi_tiet_id')->nullable();
            $table->date('ngay_phan_bo')->nullable();
            $table->decimal('so_luong_giao', 15, 4)->default(0);
            $table->softDeletes();
        });

        Schema::create('qc', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('phan_bo_may_id')->nullable();
            $table->unsignedBigInteger('don_hang_chi_tiet_id')->nullable();
            $table->unsignedBigInteger('mat_hang_id')->nullable();
            $table->unsignedBigInteger('mau_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->date('ngay_qc')->nullable();
            $table->decimal('so_luong_dat', 15, 4)->default(0);
            $table->decimal('so_luong_loi', 15, 4)->default(0);
            $table->decimal('so_luong_hong', 15, 4)->default(0);
            $table->softDeletes();
        });

        Schema::create('nhap_kho', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('qc_id')->nullable();
            $table->unsignedBigInteger('don_hang_chi_tiet_id')->nullable();
            $table->date('ngay_nhap')->nullable();
            $table->decimal('so_luong_nhap', 15, 4)->default(0);
            $table->string('loai_ton')->default('dat');
            $table->softDeletes();
        });

        Schema::create('phieu_xuat_kho', function (Blueprint $table): void {
            $table->id();
            $table->string('so_phieu')->nullable();
            $table->date('ngay_xuat')->nullable();
            $table->string('kenh_ban')->nullable();
            $table->softDeletes();
        });

        Schema::create('phieu_xuat_kho_chi_tiet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('phieu_xuat_kho_id')->nullable();
            $table->unsignedBigInteger('nhap_kho_id')->nullable();
            $table->unsignedBigInteger('don_hang_chi_tiet_id')->nullable();
            $table->decimal('so_luong_xuat', 15, 4)->default(0);
            $table->softDeletes();
        });

        Schema::create('nhap_hang_online', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_nhap')->nullable();
            $table->softDeletes();
        });

        Schema::create('nhap_hang_online_chi_tiet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('nhap_hang_online_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->string('mau')->nullable();
            $table->string('size')->nullable();
            $table->decimal('so_luong', 15, 4)->default(0);
            $table->decimal('don_gia', 15, 2)->default(0);
            $table->decimal('thanh_tien', 15, 2)->default(0);
            $table->softDeletes();
        });

        Schema::create('hang_hoan_online', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_hoan')->nullable();
            $table->softDeletes();
        });

        Schema::create('hang_hoan_online_chi_tiet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hang_hoan_online_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->string('mau')->nullable();
            $table->string('size')->nullable();
            $table->decimal('so_luong_hoan', 15, 4)->default(0);
            $table->boolean('cong_ton')->default(false);
            $table->softDeletes();
        });

        Schema::create('don_hang_hoan_thanh', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_hoan_thanh')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->softDeletes();
        });

        Schema::create('don_hang_hoan_thanh_chi_tiet', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('don_hang_hoan_thanh_id')->nullable();
            $table->string('mau')->nullable();
            $table->string('size')->nullable();
            $table->decimal('so_luong', 15, 4)->default(0);
            $table->decimal('thanh_tien', 15, 2)->default(0);
            $table->softDeletes();
        });

        Schema::create('online_product_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name')->nullable();
            $table->string('group_name')->nullable();
        });

        Schema::create('online_color_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name')->nullable();
            $table->string('group_name')->nullable();
        });

        Schema::create('online_size_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name')->nullable();
            $table->string('group_name')->nullable();
        });
    }

    private function insertOnlineImport(string $date, int $quantity, int $amount, string $product = 'AO POLO', string $color = 'Do', string $size = 'M'): void
    {
        $id = DB::table('nhap_hang_online')->insertGetId(['ngay_nhap' => $date]);

        DB::table('nhap_hang_online_chi_tiet')->insert([
            'nhap_hang_online_id' => $id,
            'ten_san_pham' => $product,
            'mau' => $color,
            'size' => $size,
            'so_luong' => $quantity,
            'thanh_tien' => $amount,
        ]);
    }

    private function insertOnlineReturn(string $date, int $quantity, string $product = 'AO POLO', string $color = 'Do', string $size = 'M'): void
    {
        $id = DB::table('hang_hoan_online')->insertGetId(['ngay_hoan' => $date]);

        DB::table('hang_hoan_online_chi_tiet')->insert([
            'hang_hoan_online_id' => $id,
            'ten_san_pham' => $product,
            'mau' => $color,
            'size' => $size,
            'so_luong_hoan' => $quantity,
            'cong_ton' => true,
        ]);
    }

    private function insertOnlineSale(string $date, int $quantity, int $amount, string $product = 'AO POLO', string $color = 'Do', string $size = 'M'): void
    {
        $id = DB::table('don_hang_hoan_thanh')->insertGetId([
            'ngay_hoan_thanh' => $date,
            'ten_san_pham' => $product,
        ]);

        DB::table('don_hang_hoan_thanh_chi_tiet')->insert([
            'don_hang_hoan_thanh_id' => $id,
            'mau' => $color,
            'size' => $size,
            'so_luong' => $quantity,
            'thanh_tien' => $amount,
        ]);
    }

    private function insertProductionProduct(): array
    {
        return [
            'mat_hang_id' => DB::table('dm_mat_hang')->insertGetId(['ma_hang' => 'MH01', 'ten_hang' => 'Ao Polo']),
            'mau_id' => DB::table('dm_mau')->insertGetId(['ma_mau' => 'DO', 'ten_mau' => 'Do']),
            'size_id' => DB::table('dm_size')->insertGetId(['ma_size' => 'M', 'ten_size' => 'M']),
        ];
    }

    private function insertProductionOrderDetail(array $product): int
    {
        $orderId = DB::table('don_hangs')->insertGetId([
            'ngay_nhan' => '2026-01-01',
            'ma_don' => 'DH001',
            'ma_kh' => 'KH001',
        ]);

        return DB::table('don_hang_chi_tiets')->insertGetId([
            'don_hang_id' => $orderId,
            ...$product,
            'so_luong_dat' => 20,
        ]);
    }

    private function insertCut(int $detailId, array $product, string $date, int $quantity): int
    {
        return DB::table('cat')->insertGetId([
            'ngay_cat' => $date,
            'don_hang_chi_tiet_id' => $detailId,
            ...$product,
            'so_luong_cat' => $quantity,
        ]);
    }

    private function insertAllocation(int $cutId, int $detailId, string $date): int
    {
        return DB::table('phan_bo_may')->insertGetId([
            'cat_id' => $cutId,
            'don_hang_chi_tiet_id' => $detailId,
            'ngay_phan_bo' => $date,
            'so_luong_giao' => 10,
        ]);
    }

    private function insertQc(int $allocationId, int $detailId, array $product, string $date, int $pass, int $defect, int $damaged): int
    {
        return DB::table('qc')->insertGetId([
            'phan_bo_may_id' => $allocationId,
            'don_hang_chi_tiet_id' => $detailId,
            ...$product,
            'ngay_qc' => $date,
            'so_luong_dat' => $pass,
            'so_luong_loi' => $defect,
            'so_luong_hong' => $damaged,
        ]);
    }

    private function insertWarehouseImport(int $qcId, int $detailId, string $date, int $quantity, string $stockType): int
    {
        return DB::table('nhap_kho')->insertGetId([
            'qc_id' => $qcId,
            'don_hang_chi_tiet_id' => $detailId,
            'ngay_nhap' => $date,
            'so_luong_nhap' => $quantity,
            'loai_ton' => $stockType,
        ]);
    }

    private function insertWarehouseExport(int $importId, int $detailId, string $date, int $quantity): void
    {
        $voucherId = DB::table('phieu_xuat_kho')->insertGetId([
            'so_phieu' => 'PX'.$date,
            'ngay_xuat' => $date,
            'kenh_ban' => 'Online',
        ]);

        DB::table('phieu_xuat_kho_chi_tiet')->insert([
            'phieu_xuat_kho_id' => $voucherId,
            'nhap_kho_id' => $importId,
            'don_hang_chi_tiet_id' => $detailId,
            'so_luong_xuat' => $quantity,
        ]);
    }
}
