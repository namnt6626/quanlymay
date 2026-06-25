<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\NhapKho\UpdateNhapKhoRequest;
use App\Models\NhapKho;
use App\Models\Qc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NhapKhoController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));
        $tuNgay = trim((string) $request->input('tu_ngay'));
        $denNgay = trim((string) $request->input('den_ngay'));
        $maDon = trim((string) $request->input('ma_don'));
        $maKh = trim((string) $request->input('ma_kh'));
        $maHang = trim((string) $request->input('ma_hang'));
        $mau = trim((string) $request->input('mau'));
        $size = trim((string) $request->input('size'));
        $loaiTon = trim((string) $request->input('loai_ton'));
        $loaiTon = in_array($loaiTon, ['dat', 'loi', 'hong'], true) ? $loaiTon : '';
        $sourceGroups = $this->buildSourceGroups();
        $sourceGroupMap = $sourceGroups->keyBy('source_group_key');

        $nhapKhos = NhapKho::query()
            ->with([
                'qc.phanBoMay.cat.matHang',
                'qc.phanBoMay.cat.mau',
                'qc.phanBoMay.cat.size',
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->when($tuNgay !== '', function (Builder $query) use ($tuNgay) {
                $query->whereDate('ngay_nhap', '>=', $tuNgay);
            })
            ->when($denNgay !== '', function (Builder $query) use ($denNgay) {
                $query->whereDate('ngay_nhap', '<=', $denNgay);
            })
            ->when($maDon !== '', function (Builder $query) use ($maDon) {
                $query->where(function (Builder $query) use ($maDon) {
                    $query->whereHas('qc.donHangChiTiet.donHang', function (Builder $query) use ($maDon) {
                        $query->where('ma_don', 'like', "%{$maDon}%");
                    })->orWhereHas('donHangChiTiet.donHang', function (Builder $query) use ($maDon) {
                        $query->where('ma_don', 'like', "%{$maDon}%");
                    });
                });
            })
            ->when($maKh !== '', function (Builder $query) use ($maKh) {
                $query->where(function (Builder $query) use ($maKh) {
                    $query->whereHas('qc.donHangChiTiet.donHang', function (Builder $query) use ($maKh) {
                        $query->where('ma_kh', 'like', "%{$maKh}%");
                    })->orWhereHas('donHangChiTiet.donHang', function (Builder $query) use ($maKh) {
                        $query->where('ma_kh', 'like', "%{$maKh}%");
                    });
                });
            })
            ->when($maHang !== '', function (Builder $query) use ($maHang) {
                $query->where(function (Builder $query) use ($maHang) {
                    $query->whereHas('qc.phanBoMay.cat.matHang', function (Builder $query) use ($maHang) {
                        $query->where('ma_hang', 'like', "%{$maHang}%")
                            ->orWhere('ten_hang', 'like', "%{$maHang}%");
                    })->orWhereHas('qc.matHang', function (Builder $query) use ($maHang) {
                        $query->where('ma_hang', 'like', "%{$maHang}%")
                            ->orWhere('ten_hang', 'like', "%{$maHang}%");
                    });
                });
            })
            ->when($mau !== '', function (Builder $query) use ($mau) {
                $query->where(function (Builder $query) use ($mau) {
                    $query->whereHas('qc.phanBoMay.cat.mau', function (Builder $query) use ($mau) {
                        $query->where('ma_mau', 'like', "%{$mau}%")
                            ->orWhere('ten_mau', 'like', "%{$mau}%");
                    })->orWhereHas('qc.mau', function (Builder $query) use ($mau) {
                        $query->where('ma_mau', 'like', "%{$mau}%")
                            ->orWhere('ten_mau', 'like', "%{$mau}%");
                    });
                });
            })
            ->when($size !== '', function (Builder $query) use ($size) {
                $query->where(function (Builder $query) use ($size) {
                    $query->whereHas('qc.phanBoMay.cat.size', function (Builder $query) use ($size) {
                        $query->where('ma_size', 'like', "%{$size}%")
                            ->orWhere('ten_size', 'like', "%{$size}%");
                    })->orWhereHas('qc.size', function (Builder $query) use ($size) {
                        $query->where('ma_size', 'like', "%{$size}%")
                            ->orWhere('ten_size', 'like', "%{$size}%");
                    });
                });
            })
            ->when($loaiTon !== '', function (Builder $query) use ($loaiTon) {
                $query->where('loai_ton', $loaiTon);
            })
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->whereHas('qc.donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.phanBoMay.cat.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.phanBoMay.cat.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.phanBoMay.cat.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('qc.phanBoMay.donViMay', function (Builder $query) use ($keyword) {
                        $query->where('ma_don_vi', 'like', "%{$keyword}%")
                            ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                    });
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        $nhapKhos->getCollection()->transform(function (NhapKho $nhapKho) use ($sourceGroupMap) {
            $sourceKey = $this->sourceGroupKeyFromNhapKho($nhapKho);
            $sourceGroup = $sourceKey !== null ? $sourceGroupMap->get($sourceKey) : null;

            if ($sourceGroup) {
                $nhapKho->setAttribute('source_group_key', $sourceGroup->source_group_key);
                $nhapKho->setAttribute('source_has_order', $sourceGroup->source_has_order);
                $nhapKho->setAttribute('source_order_number', $sourceGroup->source_order_number);
                $nhapKho->setAttribute('source_customer_number', $sourceGroup->source_customer_number);
                $nhapKho->setAttribute('source_order_quantity', $sourceGroup->source_order_quantity);
                $nhapKho->setAttribute('source_product_code', $sourceGroup->source_product_code);
                $nhapKho->setAttribute('source_product_name', $sourceGroup->source_product_name);
                $nhapKho->setAttribute('source_color', $sourceGroup->source_color);
                $nhapKho->setAttribute('source_size', $sourceGroup->source_size);
                $nhapKho->setAttribute('source_unit_name', $sourceGroup->source_unit_name);
                $nhapKho->setAttribute('source_total_qc', $sourceGroup->source_total_qc);
                $nhapKho->setAttribute('source_total_imported', $sourceGroup->source_total_imported);
                $nhapKho->setAttribute('source_total_remaining', $sourceGroup->source_total_remaining);
            }

            return $nhapKho;
        });

        return view('content.san-xuat.nhap-kho.index', compact(
            'nhapKhos',
            'keyword',
            'tuNgay',
            'denNgay',
            'maDon',
            'maKh',
            'maHang',
            'mau',
            'size',
            'loaiTon'
        ));
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('nhap-kho.index')
            ->with('error', 'Nhập kho hiện được tự động sinh từ QC.');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route('nhap-kho.index')
            ->with('error', 'Không thể tạo nhập kho thủ công. Vui lòng thực hiện QC để hệ thống tự nhập kho.');
    }

    public function edit(NhapKho $nhapKho): View
    {
        if ($nhapKho->auto_from_qc) {
            abort(403, 'Nhập kho tự động từ QC không được chỉnh sửa trực tiếp.');
        }

        $nhapKho->load([
            'qc.phanBoMay.cat.matHang',
            'qc.phanBoMay.cat.mau',
            'qc.phanBoMay.cat.size',
            'qc.phanBoMay.donViMay',
            'qc.matHang',
            'qc.mau',
            'qc.size',
            'qc.donHangChiTiet.donHang',
            'donHangChiTiet.donHang',
        ]);

        return view('content.san-xuat.nhap-kho.edit', [
            'nhapKho' => $nhapKho,
            ...$this->formOptions($nhapKho),
        ]);
    }

    public function update(UpdateNhapKhoRequest $request, NhapKho $nhapKho): RedirectResponse
    {
        if ($nhapKho->auto_from_qc) {
            abort(403, 'Nhập kho tự động từ QC không được chỉnh sửa trực tiếp.');
        }

        $data = $request->validated();
        $qc = Qc::query()
            ->with([
                'phanBoMay.cat.matHang',
                'phanBoMay.cat.mau',
                'phanBoMay.cat.size',
                'phanBoMay.donViMay',
                'matHang',
                'mau',
                'size',
                'donHangChiTiet.donHang',
            ])
            ->findOrFail((int) $data['qc_id']);

        $this->ensureNhapKhoLimit($qc, (float) $data['so_luong_nhap'], $nhapKho);
        $data['don_hang_chi_tiet_id'] = $qc->don_hang_chi_tiet_id;

        $nhapKho->update($data);

        return $this->redirectToIndex('Cập nhật nhập kho thành công.');
    }

    public function destroy(NhapKho $nhapKho): RedirectResponse
    {
        if ($nhapKho->auto_from_qc) {
            return redirect()
                ->route('nhap-kho.index')
                ->with('error', 'Nhập kho tự động từ QC không được xóa trực tiếp.');
        }

        $nhapKho->delete();

        return $this->redirectToIndex('Xóa nhập kho thành công.');
    }

    private function formOptions(?NhapKho $currentNhapKho = null): array
    {
        $sourceGroups = $this->buildSourceGroups($currentNhapKho);
        $currentSourceKey = $currentNhapKho?->qc ? $this->sourceGroupKeyFromQc($currentNhapKho->qc) : null;
        $oldQcId = request()->old('qc_id');

        return [
            'qcs' => $currentNhapKho
                ? $sourceGroups
                : $sourceGroups
                    ->filter(fn (Qc $source) => (float) $source->source_total_remaining > 0 || (string) $source->id === (string) $oldQcId)
                    ->values(),
            'selectedQcId' => $currentSourceKey !== null
                ? optional($sourceGroups->firstWhere('source_group_key', $currentSourceKey))->id
                : null,
        ];
    }

    private function getQcOptions(?NhapKho $currentNhapKho = null): Collection
    {
        return $this->buildSourceGroups($currentNhapKho);
    }

    private function ensureNhapKhoLimit(Qc $qc, float $soLuongNhap, ?NhapKho $currentNhapKho = null): void
    {
        $sourceGroups = $this->buildSourceGroups($currentNhapKho);
        $sourceGroupKey = $this->sourceGroupKeyFromQc($qc);
        $sourceSummary = $sourceGroups->firstWhere('source_group_key', $sourceGroupKey);
        $remaining = (float) ($sourceSummary?->source_total_remaining ?? 0);

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'qc_id' => 'Nguồn QC đã nhập kho đủ, vui lòng chọn nguồn QC khác.',
            ]);
        }

        if ($soLuongNhap > $remaining) {
            throw ValidationException::withMessages([
                'so_luong_nhap' => 'Vượt quá số lượng QC đạt còn lại cho phép.',
            ]);
        }
    }

    private function buildSourceGroups(?NhapKho $currentNhapKho = null): Collection
    {
        $qcs = Qc::query()
            ->with([
                'phanBoMay.cat.matHang',
                'phanBoMay.cat.mau',
                'phanBoMay.cat.size',
                'phanBoMay.donViMay',
                'donHangChiTiet.donHang',
            ])
            ->whereNull('deleted_at')
            ->get();

        $nhapKhos = NhapKho::query()
            ->with([
                'qc.phanBoMay.cat.matHang',
                'qc.phanBoMay.cat.mau',
                'qc.phanBoMay.cat.size',
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->whereNull('deleted_at')
            ->get();

        $qcGroups = $qcs->groupBy(function (Qc $qc): ?string {
            return $this->sourceGroupKeyFromQc($qc);
        });

        $nhapGroups = $nhapKhos->groupBy(function (NhapKho $nhapKho): ?string {
            return $this->sourceGroupKeyFromNhapKho($nhapKho);
        });

        $currentSourceKey = $currentNhapKho?->qc ? $this->sourceGroupKeyFromQc($currentNhapKho->qc) : null;

        return $qcGroups
            ->map(function (SupportCollection $group, ?string $sourceGroupKey) use ($nhapGroups, $currentSourceKey, $currentNhapKho) {
                if ($sourceGroupKey === null) {
                    return null;
                }

                /** @var Qc $representativeQc */
                $representativeQc = $group->sortByDesc('id')->first();
                $totalQc = (float) $group->sum('so_luong_dat');
                $totalNhap = (float) ($nhapGroups->get($sourceGroupKey, collect())->sum('so_luong_nhap'));

                if ($currentNhapKho && $currentSourceKey === $sourceGroupKey) {
                    $totalNhap -= (float) $currentNhapKho->so_luong_nhap;
                }

                $representativeQc->setAttribute('source_group_key', $sourceGroupKey);
                $representativeQc->setAttribute('source_has_order', (bool) $representativeQc->don_hang_chi_tiet_id);
                $representativeQc->setAttribute('source_order_number', $representativeQc->donHangChiTiet?->donHang?->ma_don);
                $representativeQc->setAttribute('source_customer_number', $representativeQc->donHangChiTiet?->donHang?->ma_kh);
                $representativeQc->setAttribute('source_order_quantity', $representativeQc->donHangChiTiet?->so_luong_dat);
                $representativeQc->setAttribute('source_product_code', $representativeQc->phanBoMay?->cat?->matHang?->ma_hang ?? $representativeQc->matHang?->ma_hang);
                $representativeQc->setAttribute('source_product_name', $representativeQc->phanBoMay?->cat?->matHang?->ten_hang ?? $representativeQc->matHang?->ten_hang);
                $representativeQc->setAttribute('source_color', $representativeQc->phanBoMay?->cat?->mau?->ten_mau ?? $representativeQc->mau?->ten_mau);
                $representativeQc->setAttribute('source_size', $representativeQc->phanBoMay?->cat?->size?->ten_size ?? $representativeQc->size?->ten_size);
                $representativeQc->setAttribute('source_unit_name', $representativeQc->phanBoMay?->donViMay?->ten_don_vi);
                $representativeQc->setAttribute('source_total_qc', max(0, $totalQc));
                $representativeQc->setAttribute('source_total_imported', max(0, $totalNhap));
                $representativeQc->setAttribute('source_total_remaining', max(0, $totalQc - $totalNhap));

                return $representativeQc;
            })
            ->filter()
            ->sortByDesc('id')
            ->values();
    }

    private function sourceGroupKeyFromQc(?Qc $qc): ?string
    {
        if (! $qc) {
            return null;
        }

        if ($qc->don_hang_chi_tiet_id !== null) {
            return 'order:'.$qc->don_hang_chi_tiet_id.':unit:'.($qc->phanBoMay?->don_vi_may_id ?? '');
        }

        return 'plain:'
            .($qc->phanBoMay?->cat?->mat_hang_id ?? $qc->mat_hang_id ?? '').':'
            .($qc->phanBoMay?->cat?->mau_id ?? $qc->mau_id ?? '').':'
            .($qc->phanBoMay?->cat?->size_id ?? $qc->size_id ?? '').':unit:'
            .($qc->phanBoMay?->don_vi_may_id ?? '');
    }

    private function sourceGroupKeyFromNhapKho(?NhapKho $nhapKho): ?string
    {
        return $nhapKho?->qc ? $this->sourceGroupKeyFromQc($nhapKho->qc) : null;
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('nhap-kho.index')
            ->with('success', $message);
    }
}
