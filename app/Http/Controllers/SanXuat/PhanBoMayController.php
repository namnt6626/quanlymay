<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhanBoMay\StorePhanBoMayRequest;
use App\Http\Requests\PhanBoMay\UpdatePhanBoMayRequest;
use App\Models\Cat;
use App\Models\DmDonViMay;
use App\Models\DonHang;
use App\Models\MatHang;
use App\Models\PhanBoMay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhanBoMayController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));
        $matHangId = $request->integer('mat_hang_id') ?: null;

        $phanBoMays = PhanBoMay::query()
            ->with(['cat.matHang', 'cat.mau', 'cat.size', 'cat.donHangChiTiet.donHang', 'donViMay'])
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->whereHas('cat.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('cat.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('cat.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('cat.donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('donViMay', function (Builder $query) use ($keyword) {
                        $query->where('ma_don_vi', 'like', "%{$keyword}%")
                            ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                    });
                });
            })
            ->when($matHangId, function (Builder $query) use ($matHangId) {
                $query->whereHas('cat', function (Builder $query) use ($matHangId) {
                    $query->where('mat_hang_id', $matHangId);
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        $sourceGroups = $this->buildSourceGroups();
        $sourceGroupMap = $sourceGroups->keyBy('source_group_key');

        $phanBoMays->getCollection()->transform(function (PhanBoMay $phanBoMay) use ($sourceGroupMap) {
            $sourceKey = $this->sourceKeyFromCat($phanBoMay->cat);
            $sourceGroup = $sourceKey !== null ? $sourceGroupMap->get($sourceKey) : null;

            $phanBoMay->setAttribute('source_total_cat', (float) ($sourceGroup?->total_cat ?? 0));

            return $phanBoMay;
        });

        $matHangs = MatHang::query()
            ->whereIn('id', Cat::query()->select('mat_hang_id')->distinct())
            ->orderBy('ten_hang')
            ->get();

        return view('content.san-xuat.phan-bo-may.index', compact(
            'phanBoMays',
            'keyword',
            'matHangId',
            'matHangs'
        ));
    }

    public function create(): View
    {
        return view('content.san-xuat.phan-bo-may.create', $this->formOptions());
    }

    public function store(StorePhanBoMayRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submitToken = (string) ($data['phan_bo_may_submit_token'] ?? '');

        if ($submitToken !== '' && ! Cache::add($this->submitTokenCacheKey($submitToken), true, now()->addMinutes(10))) {
            return $this->redirectToIndex('Phiếu phân bổ này đã được lưu, hệ thống đã chặn lưu trùng.');
        }

        try {
            if (! empty($data['allocations'])) {
                $this->storeGroupedAllocations($data);

                return $this->redirectToIndex('Thêm phân bổ may thành công.');
            }

            $this->ensureAllocationAllowed((int) $data['cat_id'], (float) $data['so_luong_giao']);

            $sourceCat = Cat::query()->with(['donHangChiTiet.donHang'])->findOrFail((int) $data['cat_id']);
            $data['don_hang_chi_tiet_id'] = $sourceCat->don_hang_chi_tiet_id;

            PhanBoMay::create($data);

            return $this->redirectToIndex('Thêm phân bổ may thành công.');
        } catch (\Throwable $exception) {
            if ($submitToken !== '') {
                Cache::forget($this->submitTokenCacheKey($submitToken));
            }

            throw $exception;
        }
    }

    public function edit(PhanBoMay $phanBoMay): View
    {
        $phanBoMay->load(['cat.donHangChiTiet.donHang', 'cat.matHang', 'cat.mau', 'cat.size', 'donViMay']);

        return view('content.san-xuat.phan-bo-may.edit', [
            'phanBoMay' => $phanBoMay,
            ...$this->formOptions($phanBoMay),
        ]);
    }

    public function update(UpdatePhanBoMayRequest $request, PhanBoMay $phanBoMay): RedirectResponse
    {
        $data = $request->validated();
        $data['cat_id'] = $phanBoMay->cat_id;
        $this->ensureAllocationAllowed((int) $data['cat_id'], (float) $data['so_luong_giao'], $phanBoMay);

        $sourceCat = Cat::query()->with(['donHangChiTiet.donHang'])->findOrFail((int) $data['cat_id']);
        $data['don_hang_chi_tiet_id'] = $sourceCat->don_hang_chi_tiet_id;

        $phanBoMay->update($data);

        return $this->redirectToIndex('Cập nhật phân bổ may thành công.');
    }

    public function destroy(PhanBoMay $phanBoMay): RedirectResponse
    {
        $phanBoMay->delete();

        return $this->redirectToIndex('Xóa phân bổ may thành công.');
    }

    private function formOptions(?PhanBoMay $currentPhanBoMay = null): array
    {
        $sourceCats = $this->buildSourceGroups($currentPhanBoMay);
        $currentSourceKey = $currentPhanBoMay?->cat ? $this->sourceKeyFromCat($currentPhanBoMay->cat) : null;

        return [
            'cats' => $sourceCats,
            'selectedCatId' => $currentSourceKey !== null
                ? optional($sourceCats->firstWhere('source_group_key', $currentSourceKey))->id
                : null,
            'donViMays' => DmDonViMay::query()
                ->where('trang_thai', true)
                ->orderBy('ten_don_vi')
                ->get(),
            'donHangs' => DonHang::query()
                ->whereHas('chiTiets', function (Builder $query): void {
                    $query->whereIn('id', Cat::query()
                        ->select('don_hang_chi_tiet_id')
                        ->whereNotNull('don_hang_chi_tiet_id')
                        ->whereNull('deleted_at'));
                })
                ->orderByDesc('id')
                ->get(),
            'allocationOptions' => $this->buildAllocationOptions(),
        ];
    }

    private function buildAllocationOptions(): array
    {
        $cats = Cat::query()
            ->with(['matHang', 'mau', 'size', 'donHangChiTiet.donHang'])
            ->whereNull('deleted_at')
            ->orderBy('ngay_cat')
            ->orderBy('id')
            ->get();

        $allocatedByCat = PhanBoMay::query()
            ->whereNull('deleted_at')
            ->select('cat_id', DB::raw('COALESCE(SUM(so_luong_giao), 0) as allocated'))
            ->groupBy('cat_id')
            ->pluck('allocated', 'cat_id');

        $groups = [];

        foreach ($cats as $cat) {
            $allocated = (float) ($allocatedByCat[$cat->id] ?? 0);
            $remaining = max(0, (float) $cat->so_luong_cat - $allocated);

            if ($remaining <= 0) {
                continue;
            }

            $donHangChiTiet = $cat->donHangChiTiet;
            $donHang = $donHangChiTiet?->donHang;
            $groupKey = $donHangChiTiet
                ? 'order:'.$donHangChiTiet->id
                : 'plain:'.$cat->mat_hang_id.':'.$cat->mau_id.':'.$cat->size_id;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'don_hang_id' => $donHang?->id,
                    'don_hang_chi_tiet_id' => $donHangChiTiet?->id,
                    'ma_don' => $donHang?->ma_don,
                    'ma_kh' => $donHang?->ma_kh,
                    'mat_hang_id' => $cat->mat_hang_id,
                    'ma_hang' => $cat->matHang?->ma_hang,
                    'ten_hang' => $cat->matHang?->ten_hang,
                    'mau_id' => $cat->mau_id,
                    'ten_mau' => $cat->mau?->ten_mau,
                    'size_id' => $cat->size_id,
                    'ten_size' => $cat->size?->ten_size,
                    'sl_cat' => 0,
                    'allocated' => 0,
                    'remaining' => 0,
                ];
            }

            $groups[$groupKey]['sl_cat'] += (float) $cat->so_luong_cat;
            $groups[$groupKey]['allocated'] += $allocated;
            $groups[$groupKey]['remaining'] += $remaining;
        }

        return array_values($groups);
    }

    private function storeGroupedAllocations(array $data): void
    {
        $allocations = collect($data['allocations'])
            ->filter(fn ($allocation) => is_array($allocation))
            ->filter(fn (array $allocation) => (float) ($allocation['so_luong_giao'] ?? 0) > 0)
            ->groupBy(fn (array $allocation): string => implode(':', [
                $allocation['don_hang_chi_tiet_id'] ?: 'plain',
                (int) $allocation['mat_hang_id'],
                (int) $allocation['mau_id'],
                (int) $allocation['size_id'],
            ]))
            ->map(fn ($group): array => [
                'group_key' => $group->first()['group_key'] ?? null,
                'don_hang_chi_tiet_id' => $group->first()['don_hang_chi_tiet_id'] ?? null,
                'mat_hang_id' => (int) $group->first()['mat_hang_id'],
                'mau_id' => (int) $group->first()['mau_id'],
                'size_id' => (int) $group->first()['size_id'],
                'so_luong_giao' => $group->sum(fn (array $allocation): float => (float) ($allocation['so_luong_giao'] ?? 0)),
            ])
            ->values();

        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages([
                'allocations' => 'Vui lòng nhập ít nhất một số lượng giao may.',
            ]);
        }

        DB::transaction(function () use ($data, $allocations): void {
            foreach ($allocations as $allocation) {
                $quantity = (float) ($allocation['so_luong_giao'] ?? 0);

                $this->allocateByFifo(
                    allocation: $allocation,
                    quantity: $quantity,
                    commonData: [
                        'ngay_phan_bo' => $data['ngay_phan_bo'],
                        'don_vi_may_id' => $data['don_vi_may_id'],
                        'ghi_chu' => $data['ghi_chu'] ?? null,
                    ]
                );
            }
        });
    }

    private function allocateByFifo(array $allocation, float $quantity, array $commonData): void
    {
        $cats = Cat::query()
            ->with('donHangChiTiet')
            ->whereNull('deleted_at')
            ->when(! empty($allocation['don_hang_chi_tiet_id']), function (Builder $query) use ($allocation): void {
                $query->where('don_hang_chi_tiet_id', $allocation['don_hang_chi_tiet_id'])
                    ->where('mat_hang_id', $allocation['mat_hang_id'])
                    ->where('mau_id', $allocation['mau_id'])
                    ->where('size_id', $allocation['size_id']);
            }, function (Builder $query) use ($allocation): void {
                $query->whereNull('don_hang_chi_tiet_id')
                    ->where('mat_hang_id', $allocation['mat_hang_id'])
                    ->where('mau_id', $allocation['mau_id'])
                    ->where('size_id', $allocation['size_id']);
            })
            ->orderBy('ngay_cat')
            ->orderBy('id')
            ->get();

        if ($cats->isEmpty()) {
            throw ValidationException::withMessages([
                'allocations' => 'Không tìm thấy dữ liệu cắt còn lại để phân bổ.',
            ]);
        }

        $allocatedByCat = PhanBoMay::query()
            ->whereNull('deleted_at')
            ->whereIn('cat_id', $cats->pluck('id'))
            ->select('cat_id', DB::raw('COALESCE(SUM(so_luong_giao), 0) as allocated'))
            ->groupBy('cat_id')
            ->pluck('allocated', 'cat_id');

        $remainingToAllocate = $quantity;
        $available = 0.0;

        foreach ($cats as $cat) {
            $available += max(0, (float) $cat->so_luong_cat - (float) ($allocatedByCat[$cat->id] ?? 0));
        }

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'allocations' => 'Số lượng giao may vượt số lượng còn lại.',
            ]);
        }

        foreach ($cats as $cat) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $catRemaining = max(0, (float) $cat->so_luong_cat - (float) ($allocatedByCat[$cat->id] ?? 0));

            if ($catRemaining <= 0) {
                continue;
            }

            $quantityForCat = min($remainingToAllocate, $catRemaining);

            PhanBoMay::create([
                ...$commonData,
                'cat_id' => $cat->id,
                'don_hang_chi_tiet_id' => $cat->don_hang_chi_tiet_id,
                'so_luong_giao' => $quantityForCat,
            ]);

            $remainingToAllocate -= $quantityForCat;
        }
    }

    private function buildSourceGroups(?PhanBoMay $currentPhanBoMay = null): Collection
    {
        $cats = Cat::query()
            ->with(['matHang', 'mau', 'size', 'donHangChiTiet.donHang'])
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $allocatedByGroup = PhanBoMay::query()
            ->with(['cat.donHangChiTiet.donHang'])
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(function (PhanBoMay $phanBoMay): string {
                return $this->sourceKeyFromCat($phanBoMay->cat) ?? 'unknown';
            });

        $currentSourceKey = $currentPhanBoMay?->cat ? $this->sourceKeyFromCat($currentPhanBoMay->cat) : null;

        return $cats
            ->groupBy(function (Cat $cat): string {
                return $this->sourceKeyFromCat($cat) ?? 'unknown';
            })
            ->map(function (Collection $group, string $sourceGroupKey) use ($allocatedByGroup, $currentPhanBoMay, $currentSourceKey) {
                /** @var Cat $representativeCat */
                $representativeCat = $group->sortByDesc('id')->first();
                $totalCat = (float) $group->sum('so_luong_cat');
                $allocated = (float) ($allocatedByGroup->get($sourceGroupKey, collect())->sum('so_luong_giao'));

                if ($currentPhanBoMay && $currentSourceKey === $sourceGroupKey) {
                    $allocated -= (float) $currentPhanBoMay->so_luong_giao;
                }

                $representativeCat->setAttribute('source_group_key', $sourceGroupKey);
                $representativeCat->setAttribute('total_cat', $totalCat);
                $representativeCat->setAttribute('total_phan_bo', max(0, $allocated));
                $representativeCat->setAttribute('so_luong_con_lai', max(0, $totalCat - $allocated));

                return $representativeCat;
            })
            ->sortByDesc('id')
            ->values();
    }

    private function ensureAllocationAllowed(int $catId, float $soLuongGiao, ?PhanBoMay $currentPhanBoMay = null): void
    {
        $cat = Cat::query()
            ->with(['donHangChiTiet.donHang'])
            ->select('id', 'don_hang_chi_tiet_id', 'mat_hang_id', 'mau_id', 'size_id')
            ->findOrFail($catId);

        $catQuery = Cat::query()->whereNull('deleted_at');

        if ($cat->don_hang_chi_tiet_id !== null) {
            $catQuery->where('don_hang_chi_tiet_id', $cat->don_hang_chi_tiet_id);
        } else {
            $catQuery->whereNull('don_hang_chi_tiet_id')
                ->where('mat_hang_id', $cat->mat_hang_id)
                ->where('mau_id', $cat->mau_id)
                ->where('size_id', $cat->size_id);
        }

        $totalCat = (float) $catQuery->sum('so_luong_cat');

        $allocatedQuery = PhanBoMay::query()
            ->whereNull('deleted_at')
            ->whereHas('cat', function (Builder $query) use ($cat): void {
                if ($cat->don_hang_chi_tiet_id !== null) {
                    $query->where('don_hang_chi_tiet_id', $cat->don_hang_chi_tiet_id);
                } else {
                    $query->whereNull('don_hang_chi_tiet_id')
                        ->where('mat_hang_id', $cat->mat_hang_id)
                        ->where('mau_id', $cat->mau_id)
                        ->where('size_id', $cat->size_id);
                }
            })
            ->when($currentPhanBoMay, function (Builder $query) use ($currentPhanBoMay): void {
                $query->whereKeyNot($currentPhanBoMay->getKey());
            });

        $allocated = (float) $allocatedQuery->sum('so_luong_giao');

        $remaining = (float) $totalCat - (float) $allocated;

        if ($soLuongGiao > $remaining) {
            throw ValidationException::withMessages([
                'so_luong_giao' => 'Vượt quá số lượng cắt cho phép',
            ]);
        }
    }

    private function sourceKeyFromCat(?Cat $cat): ?string
    {
        if (! $cat) {
            return null;
        }

        if ($cat->don_hang_chi_tiet_id !== null) {
            return 'order:'.$cat->don_hang_chi_tiet_id;
        }

        return 'plain:'.$cat->mat_hang_id.':'.$cat->mau_id.':'.$cat->size_id;
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('phan-bo-may.index')
            ->with('success', $message);
    }

    private function submitTokenCacheKey(string $token): string
    {
        return 'phan_bo_may_submit_token:'.$token;
    }
}
