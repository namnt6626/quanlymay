<?php

namespace Database\Seeders;

use App\Models\Cat;
use App\Models\DmBanCat;
use App\Models\DmDonViCat;
use App\Models\DmDonViMay;
use App\Models\DmSize;
use App\Models\DonHang;
use App\Models\DonHangChiTiet;
use App\Models\MatHang;
use App\Models\Mau;
use App\Models\NhapKho;
use App\Models\PhanBoMay;
use App\Models\PhieuXuatKho;
use App\Models\PhieuXuatKhoChiTiet;
use App\Models\Qc;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetBusinessTables();

        $matHangs = $this->seedMatHangs();
        $maus = $this->seedMaus();
        $sizes = $this->seedSizes();
        $banCats = $this->seedBanCats();
        $donViCats = $this->seedDonViCats();
        $donViMays = $this->seedDonViMays();

        $chiTiets = $this->seedDonHangs($matHangs, $maus, $sizes);
        $cats = $this->seedCat($chiTiets, $matHangs, $maus, $sizes, $banCats, $donViCats);
        $phanBoMays = $this->seedPhanBoMay($cats, $donViMays);
        $qcs = $this->seedQc($phanBoMays);
        $nhapKhos = $this->seedNhapKho($qcs);
        $this->seedXuatKho($nhapKhos);
    }

    private function resetBusinessTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            (new PhieuXuatKhoChiTiet)->getTable(),
            (new PhieuXuatKho)->getTable(),
            (new NhapKho)->getTable(),
            (new Qc)->getTable(),
            (new PhanBoMay)->getTable(),
            (new Cat)->getTable(),
            (new DonHangChiTiet)->getTable(),
            (new DonHang)->getTable(),
            (new DmDonViMay)->getTable(),
            (new DmDonViCat)->getTable(),
            (new DmBanCat)->getTable(),
            (new DmSize)->getTable(),
            (new Mau)->getTable(),
            (new MatHang)->getTable(),
        ] as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function seedMatHangs(): array
    {
        return collect([
            ['ma_hang' => 'MH001', 'ten_hang' => 'Áo thun basic'],
            ['ma_hang' => 'MH002', 'ten_hang' => 'Áo polo'],
            ['ma_hang' => 'MH003', 'ten_hang' => 'Quần short'],
            ['ma_hang' => 'MH004', 'ten_hang' => 'Váy nữ'],
            ['ma_hang' => 'MH005', 'ten_hang' => 'Áo khoác nhẹ'],
        ])->mapWithKeys(fn (array $data) => [
            $data['ma_hang'] => MatHang::create([
                ...$data,
                'mo_ta' => 'Dữ liệu demo',
                'trang_thai' => true,
            ]),
        ])->all();
    }

    private function seedMaus(): array
    {
        return collect([
            ['ma_mau' => 'DEN', 'ten_mau' => 'Đen'],
            ['ma_mau' => 'TRANG', 'ten_mau' => 'Trắng'],
            ['ma_mau' => 'DO', 'ten_mau' => 'Đỏ'],
            ['ma_mau' => 'NAVY', 'ten_mau' => 'Xanh navy'],
            ['ma_mau' => 'BE', 'ten_mau' => 'Be'],
            ['ma_mau' => 'XAM', 'ten_mau' => 'Xám'],
        ])->mapWithKeys(fn (array $data) => [
            $data['ma_mau'] => Mau::create([...$data, 'trang_thai' => true]),
        ])->all();
    }

    private function seedSizes(): array
    {
        return collect(['S', 'M', 'L', 'XL', 'XXL'])->mapWithKeys(fn (string $size) => [
            $size => DmSize::create([
                'ma_size' => $size,
                'ten_size' => $size,
                'trang_thai' => true,
            ]),
        ])->all();
    }

    private function seedBanCats(): array
    {
        return collect([
            ['ma_ban' => 'BC001', 'ten_ban' => 'Bàn cắt 1'],
            ['ma_ban' => 'BC002', 'ten_ban' => 'Bàn cắt 2'],
            ['ma_ban' => 'BC003', 'ten_ban' => 'Bàn cắt 3'],
        ])->map(fn (array $data) => DmBanCat::create([...$data, 'trang_thai' => true]))->all();
    }

    private function seedDonViCats(): array
    {
        return collect([
            ['ma_don_vi' => 'DVC001', 'ten_don_vi' => 'Tổ cắt 1'],
            ['ma_don_vi' => 'DVC002', 'ten_don_vi' => 'Tổ cắt 2'],
        ])->map(fn (array $data) => DmDonViCat::create([...$data, 'trang_thai' => true]))->all();
    }

    private function seedDonViMays(): array
    {
        return collect([
            ['ma_don_vi' => 'DVM001', 'ten_don_vi' => 'Xưởng may 1'],
            ['ma_don_vi' => 'DVM002', 'ten_don_vi' => 'Xưởng may 2'],
            ['ma_don_vi' => 'DVM003', 'ten_don_vi' => 'Tổ may A'],
            ['ma_don_vi' => 'DVM004', 'ten_don_vi' => 'Tổ may B'],
            ['ma_don_vi' => 'DVM005', 'ten_don_vi' => 'Gia công ngoài 1'],
        ])->map(fn (array $data) => DmDonViMay::create([...$data, 'trang_thai' => true]))->all();
    }

    private function seedDonHangs(array $matHangs, array $maus, array $sizes): array
    {
        $channels = ['Online', 'Shopee', 'TikTok Shop', 'Đại lý', 'Bán buôn'];
        $lines = [
            [['MH001', 'DEN', 'M', 55], ['MH002', 'TRANG', 'L', 42], ['MH003', 'NAVY', 'XL', 36]],
            [['MH001', 'DEN', 'M', 48], ['MH004', 'DO', 'S', 44], ['MH005', 'BE', 'L', 32]],
            [['MH002', 'NAVY', 'M', 62], ['MH003', 'XAM', 'L', 40], ['MH004', 'TRANG', 'XL', 34]],
            [['MH001', 'TRANG', 'S', 38], ['MH005', 'DEN', 'XXL', 45]],
            [['MH003', 'BE', 'M', 58], ['MH002', 'DO', 'L', 54], ['MH004', 'NAVY', 'M', 42]],
            [['MH005', 'XAM', 'XL', 50], ['MH001', 'DEN', 'M', 64], ['MH003', 'TRANG', 'S', 30]],
            [['MH004', 'BE', 'L', 46], ['MH002', 'NAVY', 'XL', 52], ['MH005', 'DO', 'M', 36]],
            [['MH003', 'DEN', 'XXL', 44], ['MH001', 'XAM', 'L', 48]],
        ];

        $chiTiets = [];

        foreach ($lines as $index => $orderLines) {
            $donHang = DonHang::create([
                'ngay_nhan' => Carbon::today()->subDays(28 - ($index * 3))->toDateString(),
                'ma_don' => sprintf('DH%03d', $index + 1),
                'ma_kh' => sprintf('KH%03d', $index + 1),
                'han_giao' => Carbon::today()->addDays(7 + ($index * 3))->toDateString(),
                'kenh_ban' => $channels[$index % count($channels)],
                'ghi_chu' => 'Đơn hàng demo',
            ]);

            foreach ($orderLines as $line) {
                [$maHang, $maMau, $maSize, $soLuong] = $line;
                $chiTiets[] = DonHangChiTiet::create([
                    'don_hang_id' => $donHang->id,
                    'mat_hang_id' => $matHangs[$maHang]->id,
                    'mau_id' => $maus[$maMau]->id,
                    'size_id' => $sizes[$maSize]->id,
                    'so_luong_dat' => $soLuong,
                    'ghi_chu' => 'Chi tiết đơn hàng demo',
                ]);
            }
        }

        return $chiTiets;
    }

    private function seedCat(array $chiTiets, array $matHangs, array $maus, array $sizes, array $banCats, array $donViCats): array
    {
        $cats = [];
        $rates = [1, 0.75, 1.15, 0.55, 0, 0.9, 1.05, 0.6, 1, 0.8];
        $dinhMucs = [1.2, 1.4, 1.6];

        foreach ($chiTiets as $index => $chiTiet) {
            $rate = $rates[$index % count($rates)];

            if ($rate <= 0) {
                continue;
            }

            $soLuongCat = (int) round((float) $chiTiet->so_luong_dat * $rate);
            $dinhMuc = $dinhMucs[$index % count($dinhMucs)];

            $cats[] = Cat::create([
                'ngay_cat' => Carbon::today()->subDays($index % 7)->toDateString(),
                'don_hang_chi_tiet_id' => $chiTiet->id,
                'mat_hang_id' => $chiTiet->mat_hang_id,
                'mau_id' => $chiTiet->mau_id,
                'size_id' => $chiTiet->size_id,
                'ban_cat_id' => $banCats[$index % count($banCats)]->id,
                'don_vi_cat_id' => $donViCats[$index % count($donViCats)]->id,
                'so_luong_cat' => $soLuongCat,
                'dinh_muc' => $dinhMuc,
                'vai_tieu_hao' => $soLuongCat * $dinhMuc,
                'ghi_chu' => 'Cắt demo theo đơn hàng',
            ]);
        }

        $optionalRows = [
            ['MH002', 'DEN', 'M', 28, 1.2],
            ['MH005', 'NAVY', 'L', 34, 1.4],
        ];

        foreach ($optionalRows as $index => $row) {
            [$maHang, $maMau, $maSize, $soLuongCat, $dinhMuc] = $row;

            $cats[] = Cat::create([
                'ngay_cat' => Carbon::today()->subDays($index)->toDateString(),
                'don_hang_chi_tiet_id' => null,
                'mat_hang_id' => $matHangs[$maHang]->id,
                'mau_id' => $maus[$maMau]->id,
                'size_id' => $sizes[$maSize]->id,
                'ban_cat_id' => $banCats[$index % count($banCats)]->id,
                'don_vi_cat_id' => $donViCats[$index % count($donViCats)]->id,
                'so_luong_cat' => $soLuongCat,
                'dinh_muc' => $dinhMuc,
                'vai_tieu_hao' => $soLuongCat * $dinhMuc,
                'ghi_chu' => 'Cắt demo không liên kết đơn hàng',
            ]);
        }

        return $cats;
    }

    private function seedPhanBoMay(array $cats, array $donViMays): array
    {
        $phanBoMays = [];
        $rates = [1, 0.85, 0.7, 0.95, 0.5];

        foreach ($cats as $index => $cat) {
            $soLuongGiao = (int) floor((float) $cat->so_luong_cat * $rates[$index % count($rates)]);

            if ($soLuongGiao <= 0) {
                continue;
            }

            $phanBoMays[] = PhanBoMay::create([
                'cat_id' => $cat->id,
                'don_hang_chi_tiet_id' => $cat->don_hang_chi_tiet_id,
                'ngay_phan_bo' => Carbon::parse($cat->ngay_cat)->addDay()->min(Carbon::today())->toDateString(),
                'don_vi_may_id' => $donViMays[$index % count($donViMays)]->id,
                'so_luong_giao' => $soLuongGiao,
                'ghi_chu' => 'Phân bổ may demo',
            ]);
        }

        return $phanBoMays;
    }

    private function seedQc(array $phanBoMays): array
    {
        $qcs = [];
        $rates = [1, 0.75, 0.9, 0.55, 0.8];

        foreach ($phanBoMays as $index => $phanBoMay) {
            $soLuongQc = (int) floor((float) $phanBoMay->so_luong_giao * $rates[$index % count($rates)]);

            if ($soLuongQc <= 0) {
                continue;
            }

            $soLuongLoi = $index % 3 === 0 ? max(1, (int) floor($soLuongQc * 0.05)) : 0;
            $soLuongHong = $index % 5 === 0 ? max(1, (int) floor($soLuongQc * 0.03)) : 0;
            $soLuongDat = max(0, $soLuongQc - $soLuongLoi - $soLuongHong);

            $qcs[] = Qc::create([
                'phan_bo_may_id' => $phanBoMay->id,
                'don_hang_chi_tiet_id' => $phanBoMay->don_hang_chi_tiet_id,
                'ngay_qc' => Carbon::parse($phanBoMay->ngay_phan_bo)->addDay()->min(Carbon::today())->toDateString(),
                'so_luong_qc' => $soLuongQc,
                'so_luong_dat' => $soLuongDat,
                'so_luong_loi' => $soLuongLoi,
                'so_luong_hong' => $soLuongHong,
                'ghi_chu' => 'QC demo',
            ]);
        }

        return $qcs;
    }

    private function seedNhapKho(array $qcs): array
    {
        $nhapKhos = [];
        $rates = [1, 0.65, 0.85, 0.45, 0.9];

        foreach ($qcs as $index => $qc) {
            $soLuongNhap = (int) floor((float) $qc->so_luong_dat * $rates[$index % count($rates)]);

            if ($soLuongNhap <= 0) {
                continue;
            }

            $nhapKhos[] = NhapKho::create([
                'qc_id' => $qc->id,
                'don_hang_chi_tiet_id' => $qc->don_hang_chi_tiet_id,
                'ngay_nhap' => Carbon::parse($qc->ngay_qc)->addDay()->min(Carbon::today())->toDateString(),
                'so_luong_nhap' => $soLuongNhap,
                'ghi_chu' => 'Nhập kho demo',
            ]);
        }

        return $nhapKhos;
    }

    private function seedXuatKho(array $nhapKhos): void
    {
        $rates = [1, 0.4, 0.75, 0.55, 0.2];

        foreach ($nhapKhos as $index => $nhapKho) {
            $soLuongXuat = (int) floor((float) $nhapKho->so_luong_nhap * $rates[$index % count($rates)]);

            if ($soLuongXuat <= 0) {
                continue;
            }

            $phieuXuatKho = PhieuXuatKho::create([
                'so_phieu' => sprintf('PXK%03d', $index + 1),
                'ngay_xuat' => Carbon::parse($nhapKho->ngay_nhap)->addDay()->min(Carbon::today())->toDateString(),
                'kenh_ban' => $this->kenhBanForNhapKho($nhapKho, $index),
                'ghi_chu' => 'Phiếu xuất kho demo',
            ]);

            PhieuXuatKhoChiTiet::create([
                'phieu_xuat_kho_id' => $phieuXuatKho->id,
                'nhap_kho_id' => $nhapKho->id,
                'don_hang_chi_tiet_id' => $nhapKho->don_hang_chi_tiet_id,
                'so_luong_xuat' => $soLuongXuat,
                'ghi_chu' => 'Chi tiết xuất kho demo',
            ]);
        }
    }

    private function kenhBanForNhapKho(NhapKho $nhapKho, int $index): string
    {
        if ($nhapKho->don_hang_chi_tiet_id) {
            $kenhBan = DonHangChiTiet::query()
                ->with('donHang:id,kenh_ban')
                ->find($nhapKho->don_hang_chi_tiet_id)
                ?->donHang
                ?->kenh_ban;

            if ($kenhBan) {
                return $kenhBan;
            }
        }

        $channels = ['Online', 'Shopee', 'TikTok Shop', 'Đại lý', 'Bán buôn'];

        return $channels[$index % count($channels)];
    }
}
