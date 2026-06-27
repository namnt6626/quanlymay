<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cat\StoreCatRequest;
use App\Http\Requests\Cat\UpdateCatRequest;
use App\Models\Cat;
use App\Models\DmBanCat;
use App\Models\DmDonViCat;
use App\Models\DmSize;
use App\Models\DonHang;
use App\Models\DonHangChiTiet;
use App\Models\MatHang;
use App\Models\Mau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));
        $tuNgay = trim((string) ($request->input('tu_ngay') ?: $request->input('ngay_cat')));
        $denNgay = trim((string) ($request->input('den_ngay') ?: $request->input('ngay_cat')));
        $matHangId = $request->integer('mat_hang_id') ?: null;
        $mauId = $request->integer('mau_id') ?: null;
        $sizeId = $request->integer('size_id') ?: null;
        $donViCatId = $request->integer('don_vi_cat_id') ?: null;
        $kieuCat = trim((string) $request->input('kieu_cat'));
        $kieuCat = in_array($kieuCat, ['don_hang', 'tu_do'], true) ? $kieuCat : '';

        $filters = [
            'q' => $keyword,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'mat_hang_id' => $matHangId,
            'mau_id' => $mauId,
            'size_id' => $sizeId,
            'don_vi_cat_id' => $donViCatId,
            'kieu_cat' => $kieuCat,
            'per_page' => paginationPerPage(),
        ];

        $cats = Cat::query()
            ->with(['matHang', 'mau', 'size', 'banCat', 'donViCat', 'donHangChiTiet.donHang'])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereHas('matHang', function ($query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })
                        ->orWhereHas('mau', function ($query) use ($keyword) {
                            $query->where('ma_mau', 'like', "%{$keyword}%")
                                ->orWhere('ten_mau', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('size', function ($query) use ($keyword) {
                            $query->where('ma_size', 'like', "%{$keyword}%")
                                ->orWhere('ten_size', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('donHangChiTiet.donHang', function ($query) use ($keyword) {
                            $query->where('ma_don', 'like', "%{$keyword}%")
                                ->orWhere('ma_kh', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('donViCat', function ($query) use ($keyword) {
                            $query->where('ma_don_vi', 'like', "%{$keyword}%")
                                ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($tuNgay !== '', fn ($query) => $query->whereDate('ngay_cat', '>=', $tuNgay))
            ->when($denNgay !== '', fn ($query) => $query->whereDate('ngay_cat', '<=', $denNgay))
            ->when($matHangId, fn ($query) => $query->where('mat_hang_id', $matHangId))
            ->when($mauId, fn ($query) => $query->where('mau_id', $mauId))
            ->when($sizeId, fn ($query) => $query->where('size_id', $sizeId))
            ->when($donViCatId, fn ($query) => $query->where('don_vi_cat_id', $donViCatId))
            ->when($kieuCat === 'don_hang', fn ($query) => $query->whereNotNull('don_hang_chi_tiet_id'))
            ->when($kieuCat === 'tu_do', fn ($query) => $query->whereNull('don_hang_chi_tiet_id'))
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('content.san-xuat.cat.index', [
            'cats' => $cats,
            'filters' => $filters,
            'keyword' => $keyword,
            'ngayCat' => $tuNgay === $denNgay ? $tuNgay : '',
            'matHangs' => MatHang::query()->where('trang_thai', true)->orderBy('ma_hang')->get(['id', 'ma_hang', 'ten_hang']),
            'maus' => Mau::query()->where('trang_thai', true)->orderBy('ten_mau')->get(['id', 'ma_mau', 'ten_mau']),
            'sizes' => DmSize::query()->where('trang_thai', true)->orderBy('ten_size')->get(['id', 'ma_size', 'ten_size']),
            'donViCats' => DmDonViCat::query()->where('trang_thai', true)->orderBy('ten_don_vi')->get(['id', 'ma_don_vi', 'ten_don_vi']),
        ]);
    }

    public function create(): View
    {
        return view('content.san-xuat.cat.create', $this->formOptions());
    }

    public function store(StoreCatRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $submitToken = (string) ($validated['cat_submit_token'] ?? '');

        if ($submitToken !== '' && ! Cache::add($this->submitTokenCacheKey($submitToken), true, now()->addMinutes(10))) {
            return $this->redirectToIndex('Lần cắt này đã được lưu, hệ thống đã chặn lưu trùng.');
        }

        try {
            if (! empty($validated['don_hang_id'])) {
                DB::transaction(function () use ($validated): void {
                    $banCatId = $this->resolveBanCatId($validated['ban_cat_ten']);
                    $chiTiets = collect($validated['chi_tiets'] ?? [])
                        ->filter(fn (array $chiTiet) => (float) ($chiTiet['so_luong_cat'] ?? 0) > 0)
                        ->groupBy(fn (array $chiTiet): int => (int) $chiTiet['don_hang_chi_tiet_id'])
                        ->map(fn ($group): array => [
                            'don_hang_chi_tiet_id' => (int) $group->first()['don_hang_chi_tiet_id'],
                            'so_luong_cat' => $group->sum(fn (array $chiTiet): float => (float) ($chiTiet['so_luong_cat'] ?? 0)),
                        ])
                        ->values();

                    if ($chiTiets->isEmpty()) {
                        return;
                    }

                    $donHangChiTiets = DonHangChiTiet::query()
                        ->where('don_hang_id', $validated['don_hang_id'])
                        ->whereIn('id', $chiTiets->pluck('don_hang_chi_tiet_id')->all())
                        ->get()
                        ->keyBy('id');

                    $cutTotals = $donHangChiTiets->isEmpty()
                        ? collect()
                        : Cat::query()
                            ->whereNull('deleted_at')
                            ->whereIn('don_hang_chi_tiet_id', $donHangChiTiets->keys())
                            ->select('don_hang_chi_tiet_id', DB::raw('COALESCE(SUM(so_luong_cat), 0) as total_cut'))
                            ->groupBy('don_hang_chi_tiet_id')
                            ->pluck('total_cut', 'don_hang_chi_tiet_id');

                    $commonData = [
                        'ngay_cat' => $validated['ngay_cat'],
                        'ban_cat_id' => $banCatId,
                        'don_vi_cat_id' => $validated['don_vi_cat_id'],
                        'dinh_muc' => $validated['dinh_muc'],
                        'ghi_chu' => $validated['ghi_chu'] ?? null,
                    ];

                    $createdCount = 0;

                    foreach ($chiTiets as $chiTietData) {
                        $donHangChiTiet = $donHangChiTiets->get((int) $chiTietData['don_hang_chi_tiet_id']);

                        if (! $donHangChiTiet) {
                            continue;
                        }

                        $soLuongCat = (float) $chiTietData['so_luong_cat'];
                        $daCat = (float) ($cutTotals[$donHangChiTiet->id] ?? 0);
                        $soLuongCanCat = (float) $donHangChiTiet->so_luong_dat - $daCat;

                        if ($soLuongCat <= 0 || $soLuongCanCat <= 0) {
                            continue;
                        }

                        Cat::create([
                            ...$commonData,
                            'don_hang_chi_tiet_id' => $donHangChiTiet->id,
                            'mat_hang_id' => $donHangChiTiet->mat_hang_id,
                            'mau_id' => $donHangChiTiet->mau_id,
                            'size_id' => $donHangChiTiet->size_id,
                            'so_luong_cat' => $soLuongCat,
                            'vai_tieu_hao' => round($soLuongCat * (float) $validated['dinh_muc'], 4),
                        ]);

                        $createdCount++;
                    }

                    if ($createdCount === 0) {
                        throw ValidationException::withMessages([
                            'chi_tiets' => 'Không có dòng nào cần cắt.',
                        ]);
                    }
                });

                return $this->redirectToIndex('Thêm lần cắt theo đơn hàng thành công.');
            }

            if (! empty($validated['items'])) {
                $this->storeFixedItems($validated);
            } else {
                Cat::create($this->buildPayload($validated));
            }

            return $this->redirectToIndex('Thêm lần cắt thành công.');
        } catch (\Throwable $exception) {
            if ($submitToken !== '') {
                Cache::forget($this->submitTokenCacheKey($submitToken));
            }

            throw $exception;
        }
    }

    public function edit(Cat $cat): View
    {
        return view('content.san-xuat.cat.edit', [
            'cat' => $cat,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCatRequest $request, Cat $cat): RedirectResponse
    {
        $cat->update($this->buildPayload($request->validated()));

        return $this->redirectToIndex('Cập nhật lần cắt thành công.');
    }

    public function destroy(Cat $cat): RedirectResponse
    {
        DB::transaction(function () use ($cat): void {
            $cat->delete();
        });

        return $this->redirectToIndex('Xóa lần cắt thành công.');
    }

    private function formOptions(): array
    {
        $donHangs = DonHang::query()
            ->with(['chiTiets.matHang', 'chiTiets.mau', 'chiTiets.size'])
            ->whereHas('chiTiets')
            ->latest('id')
            ->get();

        $chiTietIds = $donHangs
            ->flatMap(fn (DonHang $donHang) => $donHang->chiTiets->pluck('id'))
            ->filter()
            ->values();

        $cutTotals = $chiTietIds->isEmpty()
            ? collect()
            : Cat::query()
                ->whereNull('deleted_at')
                ->whereIn('don_hang_chi_tiet_id', $chiTietIds)
                ->select('don_hang_chi_tiet_id', DB::raw('COALESCE(SUM(so_luong_cat), 0) as total_cut'))
                ->groupBy('don_hang_chi_tiet_id')
                ->pluck('total_cut', 'don_hang_chi_tiet_id');

        $donHangs->each(function (DonHang $donHang) use ($cutTotals): void {
            $donHang->chiTiets->each(function (DonHangChiTiet $chiTiet) use ($cutTotals): void {
                $daCat = (float) ($cutTotals[$chiTiet->id] ?? 0);
                $canCat = max(0, (float) $chiTiet->so_luong_dat - $daCat);

                $chiTiet->setAttribute('so_luong_da_cat', $daCat);
                $chiTiet->setAttribute('so_luong_can_cat', $canCat);
            });
        });

        $matHangs = MatHang::query()
            ->where('trang_thai', true)
            ->orderBy('ten_hang')
            ->get();
        $maus = Mau::query()
            ->where('trang_thai', true)
            ->orderBy('ten_mau')
            ->get();
        $sizes = DmSize::query()
            ->where('trang_thai', true)
            ->orderBy('ten_size')
            ->get();
        $fixedItemOptions = $maus
            ->flatMap(fn (Mau $mau) => $sizes->map(fn (DmSize $size): array => [
                'mau_id' => (int) $mau->id,
                'ten_mau' => $mau->ten_mau,
                'size_id' => (int) $size->id,
                'ten_size' => $size->ten_size,
            ]))
            ->values()
            ->all();

        return [
            'donHangChiTiets' => DonHangChiTiet::query()
                ->with(['donHang', 'matHang', 'mau', 'size'])
                ->whereHas('donHang')
                ->orderByDesc('id')
                ->get(),
            'donHangs' => $donHangs,
            'matHangs' => $matHangs,
            'maus' => $maus,
            'sizes' => $sizes,
            'donViCats' => DmDonViCat::query()
                ->where('trang_thai', true)
                ->orderBy('ten_don_vi')
                ->get(),
            'fixedItemOptions' => $fixedItemOptions,
        ];
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('cat.index')
            ->with('success', $message);
    }

    private function buildPayload(array $data): array
    {
        $data['ban_cat_id'] = $this->resolveBanCatId($data['ban_cat_ten']);
        unset($data['ban_cat_ten']);

        if (! empty($data['don_hang_chi_tiet_id'])) {
            $donHangChiTiet = DonHangChiTiet::query()->findOrFail($data['don_hang_chi_tiet_id']);

            $data['mat_hang_id'] = $donHangChiTiet->mat_hang_id;
            $data['mau_id'] = $donHangChiTiet->mau_id;
            $data['size_id'] = $donHangChiTiet->size_id;
        } else {
            $data['don_hang_chi_tiet_id'] = null;
        }

        $data['vai_tieu_hao'] = round(((float) $data['so_luong_cat'] * (float) $data['dinh_muc']), 4);

        return $data;
    }

    private function storeFixedItems(array $validated): void
    {
        DB::transaction(function () use ($validated): void {
            $banCatId = $this->resolveBanCatId($validated['ban_cat_ten']);
            $items = collect($validated['items'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->filter(fn (array $item) => (float) ($item['so_luong_cat'] ?? 0) > 0)
                ->groupBy(fn (array $item): string => (int) $item['mau_id'].'-'.(int) $item['size_id'])
                ->map(fn ($group): array => [
                    'mau_id' => (int) $group->first()['mau_id'],
                    'size_id' => (int) $group->first()['size_id'],
                    'so_luong_cat' => $group->sum(fn (array $item): float => (float) ($item['so_luong_cat'] ?? 0)),
                ])
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Vui lòng nhập ít nhất một dòng cắt.',
                ]);
            }

            $commonData = [
                'ngay_cat' => $validated['ngay_cat'],
                'don_hang_chi_tiet_id' => null,
                'mat_hang_id' => $validated['mat_hang_id'],
                'ban_cat_id' => $banCatId,
                'don_vi_cat_id' => $validated['don_vi_cat_id'],
                'dinh_muc' => $validated['dinh_muc'],
                'ghi_chu' => $validated['ghi_chu'] ?? null,
            ];

            foreach ($items as $item) {
                $soLuongCat = (float) $item['so_luong_cat'];

                Cat::create([
                    ...$commonData,
                    'mau_id' => $item['mau_id'],
                    'size_id' => $item['size_id'],
                    'so_luong_cat' => $soLuongCat,
                    'vai_tieu_hao' => round($soLuongCat * (float) $validated['dinh_muc'], 4),
                ]);
            }
        });
    }

    private function submitTokenCacheKey(string $token): string
    {
        return 'cat_submit_token:'.$token;
    }

    private function resolveBanCatId(string $tenBan): int
    {
        $tenBan = trim($tenBan);

        $banCat = DmBanCat::query()
            ->where('ten_ban', $tenBan)
            ->first();

        if ($banCat) {
            if (! $banCat->trang_thai) {
                $banCat->update(['trang_thai' => true]);
            }

            return $banCat->id;
        }

        return DmBanCat::create([
            'ma_ban' => 'BC-'.Str::upper(Str::random(8)),
            'ten_ban' => $tenBan,
            'trang_thai' => true,
        ])->id;
    }
}
