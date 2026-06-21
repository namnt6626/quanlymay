<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qc\StoreQcRequest;
use App\Http\Requests\Qc\UpdateQcRequest;
use App\Models\Cat;
use App\Models\MatHang;
use App\Models\NhapKho;
use App\Models\PhanBoMay;
use App\Models\PhieuXuatKhoChiTiet;
use App\Models\Qc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QcController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $qcs = Qc::query()
            ->with([
                'phanBoMay.cat.matHang',
                'phanBoMay.cat.mau',
                'phanBoMay.cat.size',
                'phanBoMay.donViMay',
                'donHangChiTiet.donHang',
                'matHang',
                'mau',
                'size',
            ])
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->whereHas('phanBoMay.cat.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('phanBoMay.cat.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('phanBoMay.cat.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('phanBoMay.donViMay', function (Builder $query) use ($keyword) {
                        $query->where('ma_don_vi', 'like', "%{$keyword}%")
                            ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                    })->orWhereHas('donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $sourceGroups = $this->buildSourceGroups();
        $sourceGroupMap = $sourceGroups->keyBy('source_group_key');

        $qcs->getCollection()->transform(function (Qc $qc) use ($sourceGroupMap) {
            $sourceKey = $this->sourceGroupKeyFromPhanBoMay($qc->phanBoMay);
            $sourceGroup = $sourceKey !== null ? $sourceGroupMap->get($sourceKey) : null;

            if ($sourceGroup) {
                $qc->setAttribute('source_total_cut', $sourceGroup->source_total_cut);
                $qc->setAttribute('source_total_delivered', $sourceGroup->source_total_delivered);
                $qc->setAttribute('source_total_qc', $sourceGroup->source_total_qc);
                $qc->setAttribute('source_total_remaining', $sourceGroup->source_total_remaining);
            }

            return $qc;
        });

        return view('content.san-xuat.qc.index', compact('qcs', 'keyword'));
    }

    public function create(): View
    {
        return view('content.san-xuat.qc.create', $this->formOptions());
    }

    public function store(StoreQcRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (($data['qc_mode'] ?? 'from_allocation') === 'manual') {
            $this->storeManualQcGroups($data);

            return $this->redirectToIndex('Thêm QC thành công.');
        }

        $this->storeAllocationQcGroups($data);

        return $this->redirectToIndex('Thêm QC thành công.');
    }

    public function edit(Qc $qc): View
    {
        $qc->load(['phanBoMay.cat.donHangChiTiet.donHang', 'phanBoMay.donHangChiTiet.donHang', 'phanBoMay.donViMay', 'donHangChiTiet.donHang']);

        return view('content.san-xuat.qc.edit', [
            'qc' => $qc,
            ...$this->formOptions($qc),
        ]);
    }

    public function update(UpdateQcRequest $request, Qc $qc): RedirectResponse
    {
        $data = $request->validated();

        if ($qc->phan_bo_may_id === null && empty($data['phan_bo_may_id'])) {
            $this->ensureQcDatCanBeChanged($qc, (float) $data['so_luong_dat']);

            DB::transaction(function () use ($qc, $data): void {
                $qc->update([
                    'ngay_qc' => $data['ngay_qc'],
                    'so_luong_qc' => $data['so_luong_qc'],
                    'so_luong_dat' => $data['so_luong_dat'],
                    'so_luong_loi' => $data['so_luong_loi'],
                    'so_luong_hong' => $data['so_luong_hong'],
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);

                $this->syncAutoNhapKhoFromQc($qc->fresh());
            });

            return $this->redirectToIndex('Cập nhật QC thành công.');
        }

        $phanBoMay = PhanBoMay::query()
            ->with(['cat.donHangChiTiet.donHang', 'cat.matHang', 'cat.mau', 'cat.size', 'donHangChiTiet.donHang', 'donViMay'])
            ->findOrFail((int) $data['phan_bo_may_id']);

        if ($this->sourceGroupKeyFromPhanBoMay($phanBoMay) !== $this->sourceGroupKeyFromPhanBoMay($qc->phanBoMay)) {
            $this->ensureQcLimit($phanBoMay, (float) $data['so_luong_qc'], $qc);
        }

        $data['don_hang_chi_tiet_id'] = $phanBoMay->don_hang_chi_tiet_id;
        $data['mat_hang_id'] = $phanBoMay->cat?->mat_hang_id;
        $data['mau_id'] = $phanBoMay->cat?->mau_id;
        $data['size_id'] = $phanBoMay->cat?->size_id;

        $this->ensureQcDatCanBeChanged($qc, (float) $data['so_luong_dat']);

        DB::transaction(function () use ($qc, $data): void {
            $qc->update($data);
            $this->syncAutoNhapKhoFromQc($qc->fresh());
        });

        return $this->redirectToIndex('Cập nhật QC thành công.');
    }

    public function destroy(Qc $qc): RedirectResponse
    {
        $this->ensureQcCanBeDeleted($qc);

        DB::transaction(function () use ($qc): void {
            $qc->nhapKhos()
                ->where('auto_from_qc', true)
                ->get()
                ->each
                ->delete();

            $qc->delete();
        });

        return $this->redirectToIndex('Xóa QC thành công.');
    }

    private function formOptions(?Qc $currentQc = null): array
    {
        $sourceGroups = $this->buildSourceGroups($currentQc);
        $currentSourceKey = $currentQc?->phanBoMay ? $this->sourceGroupKeyFromPhanBoMay($currentQc->phanBoMay) : null;
        $oldAllocationIds = collect(request()->old('allocation_groups', []))
            ->filter(fn ($group) => is_array($group))
            ->pluck('phan_bo_may_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'phanBoMays' => $currentQc
                ? $sourceGroups
                : $sourceGroups
                    ->filter(fn (PhanBoMay $source) => (float) $source->source_total_remaining > 0 || $oldAllocationIds->contains((int) $source->id))
                    ->values(),
            'selectedPhanBoMayId' => $currentSourceKey !== null
                ? optional($sourceGroups->firstWhere('source_group_key', $currentSourceKey))->id
                : null,
            'manualProducts' => MatHang::query()
                ->whereIn('id', Cat::query()->select('mat_hang_id')->distinct())
                ->orderBy('ma_hang')
                ->get(),
            'manualItems' => $this->buildManualItems(),
        ];
    }

    private function storeAllocationQcGroups(array $data): void
    {
        $groups = collect($data['allocation_groups'] ?? [])
            ->filter(fn ($group) => is_array($group))
            ->map(function (array $group, int|string $index): array {
                $group['so_luong_qc'] = $this->quantityTotal($group);
                $group['row_index'] = $index;

                return $group;
            })
            ->filter(fn (array $group): bool => (float) $group['so_luong_qc'] > 0);

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages([
                'allocation_groups' => 'Vui lòng nhập ít nhất một dòng QC.',
            ]);
        }

        DB::transaction(function () use ($data, $groups): void {
            foreach ($groups as $group) {
                $phanBoMay = PhanBoMay::query()
                    ->with(['cat.donHangChiTiet.donHang', 'donHangChiTiet.donHang', 'donViMay'])
                    ->findOrFail((int) $group['phan_bo_may_id']);

                $this->ensureQcSourceIsAvailable(
                    $phanBoMay,
                    null,
                    'allocation_groups.'.$group['row_index'].'.so_luong_qc'
                );

                $qc = Qc::create([
                    'phan_bo_may_id' => $phanBoMay->id,
                    'don_hang_chi_tiet_id' => $phanBoMay->don_hang_chi_tiet_id,
                    'mat_hang_id' => $phanBoMay->cat?->mat_hang_id,
                    'mau_id' => $phanBoMay->cat?->mau_id,
                    'size_id' => $phanBoMay->cat?->size_id,
                    'ngay_qc' => $data['ngay_qc'],
                    'so_luong_qc' => (float) $group['so_luong_qc'],
                    'so_luong_dat' => (float) ($group['sl_dat'] ?? 0),
                    'so_luong_loi' => (float) ($group['sl_loi'] ?? 0),
                    'so_luong_hong' => (float) ($group['sl_hong'] ?? 0),
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);

                $this->syncAutoNhapKhoFromQc($qc);
            }
        });
    }

    private function storeManualQcGroups(array $data): void
    {
        $items = collect($data['manual_groups'] ?? [])
            ->filter(fn ($group) => is_array($group))
            ->flatMap(function (array $group): array {
                return collect($group['items'] ?? [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(function (array $item) use ($group): array {
                        $item['mat_hang_id'] = $group['mat_hang_id'] ?? null;
                        $item['so_luong_qc'] = $this->quantityTotal($item);

                        return $item;
                    })
                    ->filter(fn (array $item): bool => (float) $item['so_luong_qc'] > 0)
                    ->values()
                    ->all();
            })
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Vui lòng nhập ít nhất một dòng QC.',
            ]);
        }

        DB::transaction(function () use ($data, $items): void {
            foreach ($items as $item) {
                $qc = Qc::create([
                    'phan_bo_may_id' => null,
                    'don_hang_chi_tiet_id' => null,
                    'mat_hang_id' => $item['mat_hang_id'],
                    'mau_id' => $item['mau_id'],
                    'size_id' => $item['size_id'],
                    'ngay_qc' => $data['ngay_qc'],
                    'so_luong_qc' => (float) ($item['so_luong_qc'] ?? 0),
                    'so_luong_dat' => (float) ($item['sl_dat'] ?? 0),
                    'so_luong_loi' => (float) ($item['sl_loi'] ?? 0),
                    'so_luong_hong' => (float) ($item['sl_hong'] ?? 0),
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);

                $this->syncAutoNhapKhoFromQc($qc);
            }
        });
    }

    private function syncAutoNhapKhoFromQc(Qc $qc): void
    {
        $qc->loadMissing('phanBoMay.cat');

        $hasAutoNhapKho = NhapKho::withTrashed()
            ->where('qc_id', $qc->id)
            ->where('auto_from_qc', true)
            ->exists();
        $hasManualNhapKho = NhapKho::withTrashed()
            ->where('qc_id', $qc->id)
            ->where(function ($query) {
                $query->whereNull('auto_from_qc')->orWhere('auto_from_qc', false);
            })
            ->exists();

        if (! $hasAutoNhapKho && $hasManualNhapKho) {
            return;
        }

        $quantities = [
            'dat' => (float) $qc->so_luong_dat,
            'loi' => (float) $qc->so_luong_loi,
            'hong' => (float) $qc->so_luong_hong,
        ];

        foreach ($quantities as $loaiTon => $quantity) {
            $nhapKho = NhapKho::withTrashed()
                ->where('qc_id', $qc->id)
                ->where('loai_ton', $loaiTon)
                ->where('auto_from_qc', true)
                ->first();

            if ($quantity <= 0) {
                if ($nhapKho && ! $nhapKho->trashed()) {
                    $nhapKho->delete();
                }

                continue;
            }

            if ($nhapKho) {
                if ($nhapKho->trashed()) {
                    $nhapKho->restore();
                }

                $nhapKho->update([
                    'don_hang_chi_tiet_id' => $qc->don_hang_chi_tiet_id,
                    'ngay_nhap' => $qc->ngay_qc,
                    'so_luong_nhap' => $quantity,
                    'ghi_chu' => 'Tự động nhập kho từ QC',
                ]);

                continue;
            }

            NhapKho::create([
                'qc_id' => $qc->id,
                'don_hang_chi_tiet_id' => $qc->don_hang_chi_tiet_id,
                'ngay_nhap' => $qc->ngay_qc,
                'so_luong_nhap' => $quantity,
                'loai_ton' => $loaiTon,
                'auto_from_qc' => true,
                'ghi_chu' => 'Tự động nhập kho từ QC',
            ]);
        }
    }

    private function ensureQcDatCanBeChanged(Qc $qc, float $newSoLuongDat): void
    {
        $exported = $this->exportedDatQuantity($qc);

        if ($newSoLuongDat < $exported) {
            throw ValidationException::withMessages([
                'so_luong_dat' => 'QC này đã phát sinh xuất hàng, không thể giảm SL đạt xuống thấp hơn số đã xuất.',
            ]);
        }
    }

    private function ensureQcCanBeDeleted(Qc $qc): void
    {
        if ($this->exportedDatQuantity($qc) > 0) {
            throw ValidationException::withMessages([
                'qc' => 'QC này đã phát sinh xuất hàng, không thể xóa.',
            ]);
        }
    }

    private function exportedDatQuantity(Qc $qc): float
    {
        return (float) PhieuXuatKhoChiTiet::query()
            ->whereHas('nhapKho', function (Builder $query) use ($qc): void {
                $query->where('qc_id', $qc->id)
                    ->where('loai_ton', 'dat');
            })
            ->whereNull('deleted_at')
            ->sum('so_luong_xuat');
    }

    private function quantityTotal(array $data): float
    {
        return round(
            (float) ($data['sl_dat'] ?? 0)
            + (float) ($data['sl_loi'] ?? 0)
            + (float) ($data['sl_hong'] ?? 0),
            4
        );
    }

    private function buildManualItems(): array
    {
        return Cat::query()
            ->with(['matHang', 'mau', 'size'])
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(fn (Cat $cat): string => $cat->mat_hang_id.':'.$cat->mau_id.':'.$cat->size_id)
            ->map(function (Collection $group): array {
                /** @var Cat $cat */
                $cat = $group->first();

                return [
                    'mat_hang_id' => $cat->mat_hang_id,
                    'ma_hang' => $cat->matHang?->ma_hang,
                    'ten_hang' => $cat->matHang?->ten_hang,
                    'mau_id' => $cat->mau_id,
                    'ten_mau' => $cat->mau?->ten_mau,
                    'size_id' => $cat->size_id,
                    'ten_size' => $cat->size?->ten_size,
                ];
            })
            ->values()
            ->all();
    }

    private function buildSourceGroups(?Qc $currentQc = null): Collection
    {
        $phanBoMays = PhanBoMay::query()
            ->with(['cat.donHangChiTiet.donHang', 'cat.matHang', 'cat.mau', 'cat.size', 'donViMay', 'donHangChiTiet.donHang'])
            ->whereNull('deleted_at')
            ->get();

        $qcTotalsByGroup = Qc::query()
            ->with(['phanBoMay.cat.donHangChiTiet.donHang', 'phanBoMay.donHangChiTiet.donHang'])
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(function (Qc $qc): ?string {
                return $this->sourceGroupKeyFromPhanBoMay($qc->phanBoMay);
            });

        $currentSourceKey = $currentQc?->phanBoMay ? $this->sourceGroupKeyFromPhanBoMay($currentQc->phanBoMay) : null;

        return $phanBoMays
            ->groupBy(function (PhanBoMay $phanBoMay): string {
                return $this->sourceGroupKeyFromPhanBoMay($phanBoMay);
            })
            ->map(function (Collection $group, string $sourceGroupKey) use ($qcTotalsByGroup, $currentSourceKey, $currentQc) {
                /** @var PhanBoMay $representativePhanBoMay */
                $representativePhanBoMay = $group->sortByDesc('id')->first();
                $totalCut = (float) $group->sum(function (PhanBoMay $phanBoMay) {
                    return (float) $phanBoMay->cat?->so_luong_cat;
                });
                $totalDelivered = (float) $group->sum('so_luong_giao');
                $totalQc = (float) ($qcTotalsByGroup->get($sourceGroupKey, collect())->sum(function (Qc $qc) {
                    return (float) $qc->so_luong_qc;
                }));

                if ($currentQc && $currentSourceKey === $sourceGroupKey) {
                    $totalQc -= (float) $currentQc->so_luong_qc;
                }

                $representativePhanBoMay->setAttribute('source_group_key', $sourceGroupKey);
                $representativePhanBoMay->setAttribute('source_has_order', (bool) $representativePhanBoMay->don_hang_chi_tiet_id);
                $representativePhanBoMay->setAttribute('source_order_number', $representativePhanBoMay->donHangChiTiet?->donHang?->ma_don);
                $representativePhanBoMay->setAttribute('source_customer_number', $representativePhanBoMay->donHangChiTiet?->donHang?->ma_kh);
                $representativePhanBoMay->setAttribute('source_order_quantity', $representativePhanBoMay->donHangChiTiet?->so_luong_dat);
                $representativePhanBoMay->setAttribute('source_product_code', $representativePhanBoMay->cat?->matHang?->ma_hang);
                $representativePhanBoMay->setAttribute('source_product_name', $representativePhanBoMay->cat?->matHang?->ten_hang);
                $representativePhanBoMay->setAttribute('source_color', $representativePhanBoMay->cat?->mau?->ten_mau);
                $representativePhanBoMay->setAttribute('source_size', $representativePhanBoMay->cat?->size?->ten_size);
                $representativePhanBoMay->setAttribute('source_unit_name', $representativePhanBoMay->donViMay?->ten_don_vi);
                $representativePhanBoMay->setAttribute('source_total_cut', $totalCut);
                $representativePhanBoMay->setAttribute('source_total_delivered', $totalDelivered);
                $representativePhanBoMay->setAttribute('source_total_qc', max(0, $totalQc));
                $representativePhanBoMay->setAttribute('source_total_remaining', max(0, $totalDelivered - $totalQc));

                return $representativePhanBoMay;
            })
            ->sortByDesc('id')
            ->values();
    }

    private function ensureQcLimit(PhanBoMay $phanBoMay, float $soLuongQc, ?Qc $currentQc = null): void
    {
        $this->ensureQcSourceIsAvailable($phanBoMay, $currentQc);
    }

    private function ensureQcSourceIsAvailable(PhanBoMay $phanBoMay, ?Qc $currentQc = null, string $errorKey = 'so_luong_qc'): void
    {
        $sourceGroups = $this->buildSourceGroups($currentQc);
        $sourceGroupKey = $this->sourceGroupKeyFromPhanBoMay($phanBoMay);
        $sourceSummary = $sourceGroups->firstWhere('source_group_key', $sourceGroupKey);

        $remaining = (float) ($sourceSummary?->source_total_remaining ?? 0);
        $sourceLabel = $this->sourceLabel($sourceSummary ?: $phanBoMay);

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                $errorKey => "Nguồn QC {$sourceLabel} đã QC đủ, vui lòng tải lại dữ liệu.",
            ]);
        }
    }

    private function sourceLabel(?PhanBoMay $phanBoMay): string
    {
        if (! $phanBoMay) {
            return 'không xác định';
        }

        $cat = $phanBoMay->cat;
        $donHang = $phanBoMay->donHangChiTiet?->donHang ?? $cat?->donHangChiTiet?->donHang;

        return implode(' - ', array_filter([
            $donHang?->ma_don,
            $cat?->matHang?->ma_hang,
            $cat?->mau?->ten_mau,
            $cat?->size?->ten_size,
            $phanBoMay->donViMay?->ten_don_vi,
        ])) ?: ('#'.$phanBoMay->id);
    }

    private function sourceGroupKeyFromPhanBoMay(?PhanBoMay $phanBoMay): ?string
    {
        if (! $phanBoMay) {
            return null;
        }

        if ($phanBoMay->don_hang_chi_tiet_id !== null) {
            return 'order:'.$phanBoMay->don_hang_chi_tiet_id.':unit:'.$phanBoMay->don_vi_may_id;
        }

        return 'plain:'
            .($phanBoMay->cat?->mat_hang_id ?? '').':'
            .($phanBoMay->cat?->mau_id ?? '').':'
            .($phanBoMay->cat?->size_id ?? '').':unit:'
            .$phanBoMay->don_vi_may_id;
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('qc.index')
            ->with('success', $message);
    }
}
