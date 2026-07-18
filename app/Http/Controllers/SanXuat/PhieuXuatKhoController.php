<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\XuatKho\StoreXuatKhoRequest;
use App\Http\Requests\XuatKho\UpdateXuatKhoRequest;
use App\Models\DmSize;
use App\Models\DonHangChiTiet;
use App\Models\MatHang;
use App\Models\Mau;
use App\Models\NhapKho;
use App\Models\PhieuXuatKho;
use App\Models\PhieuXuatKhoChiTiet;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhieuXuatKhoController extends Controller
{
    private const KENH_BAN_OPTIONS = ['Tiktok', 'Shopee', 'Bán sỉ'];

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));
        $tuNgay = trim((string) $request->input('tu_ngay'));
        $denNgay = trim((string) $request->input('den_ngay'));
        $kenhBan = in_array($request->input('kenh_ban'), self::KENH_BAN_OPTIONS, true)
            ? $request->input('kenh_ban')
            : '';
        $maHang = trim((string) $request->input('ma_hang'));
        $maMau = trim((string) $request->input('ma_mau'));
        $maSize = trim((string) $request->input('ma_size'));

        $filters = [
            'q' => $keyword,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'kenh_ban' => $kenhBan,
            'ma_hang' => $maHang,
            'ma_mau' => $maMau,
            'ma_size' => $maSize,
            'per_page' => paginationPerPage(),
        ];

        $sourceGroups = $this->buildSourceGroups();
        $sourceGroupMap = $sourceGroups->keyBy('source_group_key');

        $chiTiets = PhieuXuatKhoChiTiet::query()
            ->whereHas('phieuXuatKho')
            ->with([
                'phieuXuatKho',
                'nhapKho.qc.phanBoMay.cat.matHang',
                'nhapKho.qc.phanBoMay.cat.mau',
                'nhapKho.qc.phanBoMay.cat.size',
                'nhapKho.qc.phanBoMay.donViMay',
                'nhapKho.qc.matHang',
                'nhapKho.qc.mau',
                'nhapKho.qc.size',
                'nhapKho.qc.donHangChiTiet.donHang',
                'nhapKho.donHangChiTiet.donHang',
                'nhapKho.donHangChiTiet.matHang',
                'nhapKho.donHangChiTiet.mau',
                'nhapKho.donHangChiTiet.size',
                'donHangChiTiet.donHang',
                'donHangChiTiet.matHang',
                'donHangChiTiet.mau',
                'donHangChiTiet.size',
            ])
            ->when($tuNgay !== '', fn (Builder $query) => $query->whereHas('phieuXuatKho', fn (Builder $query) => $query->whereDate('ngay_xuat', '>=', $tuNgay)))
            ->when($denNgay !== '', fn (Builder $query) => $query->whereHas('phieuXuatKho', fn (Builder $query) => $query->whereDate('ngay_xuat', '<=', $denNgay)))
            ->when($kenhBan !== '', fn (Builder $query) => $query->whereHas('phieuXuatKho', fn (Builder $query) => $query->where('kenh_ban', 'like', "%{$kenhBan}%")))
            ->when($maHang !== '', function (Builder $query) use ($maHang): void {
                $query->where(function (Builder $query) use ($maHang): void {
                    $query->whereHas('nhapKho.qc.phanBoMay.cat.matHang', fn (Builder $query) => $query->where('ma_hang', $maHang))
                        ->orWhereHas('nhapKho.qc.matHang', fn (Builder $query) => $query->where('ma_hang', $maHang))
                        ->orWhereHas('nhapKho.donHangChiTiet.matHang', fn (Builder $query) => $query->where('ma_hang', $maHang))
                        ->orWhereHas('donHangChiTiet.matHang', fn (Builder $query) => $query->where('ma_hang', $maHang));
                });
            })
            ->when($maMau !== '', function (Builder $query) use ($maMau): void {
                $query->where(function (Builder $query) use ($maMau): void {
                    $query->whereHas('nhapKho.qc.phanBoMay.cat.mau', fn (Builder $query) => $query->where('ma_mau', $maMau))
                        ->orWhereHas('nhapKho.qc.mau', fn (Builder $query) => $query->where('ma_mau', $maMau))
                        ->orWhereHas('nhapKho.donHangChiTiet.mau', fn (Builder $query) => $query->where('ma_mau', $maMau))
                        ->orWhereHas('donHangChiTiet.mau', fn (Builder $query) => $query->where('ma_mau', $maMau));
                });
            })
            ->when($maSize !== '', function (Builder $query) use ($maSize): void {
                $query->where(function (Builder $query) use ($maSize): void {
                    $query->whereHas('nhapKho.qc.phanBoMay.cat.size', fn (Builder $query) => $query->where('ma_size', $maSize))
                        ->orWhereHas('nhapKho.qc.size', fn (Builder $query) => $query->where('ma_size', $maSize))
                        ->orWhereHas('nhapKho.donHangChiTiet.size', fn (Builder $query) => $query->where('ma_size', $maSize))
                        ->orWhereHas('donHangChiTiet.size', fn (Builder $query) => $query->where('ma_size', $maSize));
                });
            })
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->whereHas('phieuXuatKho', function (Builder $query) use ($keyword) {
                        $query->where('so_phieu', 'like', "%{$keyword}%")
                            ->orWhere('kenh_ban', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('donHangChiTiet.donHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_don', 'like', "%{$keyword}%")
                            ->orWhere('ma_kh', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.phanBoMay.cat.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.phanBoMay.cat.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.phanBoMay.cat.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.matHang', function (Builder $query) use ($keyword) {
                        $query->where('ma_hang', 'like', "%{$keyword}%")
                            ->orWhere('ten_hang', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.mau', function (Builder $query) use ($keyword) {
                        $query->where('ma_mau', 'like', "%{$keyword}%")
                            ->orWhere('ten_mau', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.size', function (Builder $query) use ($keyword) {
                        $query->where('ma_size', 'like', "%{$keyword}%")
                            ->orWhere('ten_size', 'like', "%{$keyword}%");
                    })->orWhereHas('nhapKho.qc.phanBoMay.donViMay', function (Builder $query) use ($keyword) {
                        $query->where('ma_don_vi', 'like', "%{$keyword}%")
                            ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                    });
                });
            })
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $chiTiets->getCollection()->transform(function (PhieuXuatKhoChiTiet $chiTiet) use ($sourceGroupMap) {
            $sourceKey = $this->sourceGroupKeyFromNhapKho($chiTiet->nhapKho);
            $sourceGroup = $sourceKey !== null ? $sourceGroupMap->get($sourceKey) : null;

            if ($sourceGroup) {
                $chiTiet->setAttribute('source_group_key', $sourceGroup->source_group_key);
                $chiTiet->setAttribute('source_has_order', $sourceGroup->source_has_order);
                $chiTiet->setAttribute('source_order_number', $sourceGroup->source_order_number);
                $chiTiet->setAttribute('source_customer_number', $sourceGroup->source_customer_number);
                $chiTiet->setAttribute('source_order_quantity', $sourceGroup->source_order_quantity);
                $chiTiet->setAttribute('source_product_code', $sourceGroup->source_product_code);
                $chiTiet->setAttribute('source_product_name', $sourceGroup->source_product_name);
                $chiTiet->setAttribute('source_color', $sourceGroup->source_color);
                $chiTiet->setAttribute('source_size', $sourceGroup->source_size);
                $chiTiet->setAttribute('source_total_imported', $sourceGroup->source_total_imported);
                $chiTiet->setAttribute('source_total_exported', $sourceGroup->source_total_exported);
                $chiTiet->setAttribute('source_total_remaining', $sourceGroup->source_total_remaining);
                $chiTiet->setAttribute('source_kenh_ban', $chiTiet->phieuXuatKho?->kenh_ban ?: $sourceGroup->source_kenh_ban);
            }

            return $chiTiet;
        });

        return view('content.san-xuat.xuat-kho.index', [
            'chiTiets' => $chiTiets,
            'keyword' => $keyword,
            'filters' => $filters,
            'matHangs' => MatHang::query()
                ->where('trang_thai', true)
                ->whereNotNull('ma_hang')
                ->select('ma_hang', DB::raw('MIN(ten_hang) as ten_hang'))
                ->groupBy('ma_hang')
                ->orderBy('ma_hang')
                ->get(),
            'maus' => Mau::query()
                ->where('trang_thai', true)
                ->whereNotNull('ma_mau')
                ->select('ma_mau', DB::raw('MIN(ten_mau) as ten_mau'))
                ->groupBy('ma_mau')
                ->orderBy('ten_mau')
                ->orderBy('ma_mau')
                ->get(),
            'sizes' => DmSize::query()
                ->where('trang_thai', true)
                ->whereNotNull('ma_size')
                ->select('ma_size', DB::raw('MIN(ten_size) as ten_size'))
                ->groupBy('ma_size')
                ->orderBy('ten_size')
                ->orderBy('ma_size')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('content.san-xuat.xuat-kho.create', $this->formOptions());
    }

    public function store(StoreXuatKhoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submitToken = (string) ($data['xuat_kho_submit_token'] ?? '');

        if ($submitToken !== '' && ! Cache::add($this->submitTokenCacheKey($submitToken), true, now()->addMinutes(10))) {
            return $this->redirectToIndex('Phiếu xuất kho này đã được lưu, hệ thống đã chặn lưu trùng.');
        }

        $items = collect($data['items'] ?? [])
            ->filter(fn (array $item): bool => (float) ($item['so_luong_xuat'] ?? 0) > 0)
            ->values();

        try {
            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Vui lòng chọn ít nhất một nguồn hàng để xuất.',
                ]);
            }

            $sourceGroups = $this->buildSourceGroups()->keyBy('id');
            $selectedRows = [];
            $seen = [];

            foreach ($items as $index => $item) {
                $rowNumber = $index + 1;
                $nhapKhoId = (int) $item['nhap_kho_id'];
                $soLuongXuat = (float) $item['so_luong_xuat'];

                if (isset($seen[$nhapKhoId])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.nhap_kho_id" => "Dòng {$rowNumber}: Nguồn xuất này đã được chọn ở dòng {$seen[$nhapKhoId]}.",
                    ]);
                }

                $seen[$nhapKhoId] = $rowNumber;

                /** @var NhapKho|null $nhapKho */
                $nhapKho = $sourceGroups->get($nhapKhoId);

                if (! $nhapKho) {
                    $nhapKho = NhapKho::query()->find($nhapKhoId);

                    if (! $nhapKho) {
                        throw ValidationException::withMessages([
                            "items.{$index}.nhap_kho_id" => "Dòng {$rowNumber}: Nguồn xuất không tồn tại.",
                        ]);
                    }

                    if (($nhapKho->loai_ton ?? 'dat') !== 'dat') {
                        throw ValidationException::withMessages([
                            "items.{$index}.nhap_kho_id" => "Dòng {$rowNumber}: Chỉ được xuất hàng đạt.",
                        ]);
                    }

                    throw ValidationException::withMessages([
                        "items.{$index}.nhap_kho_id" => "Dòng {$rowNumber}: Nguồn xuất đã hết tồn đạt.",
                    ]);
                }

                if (($nhapKho->loai_ton ?? 'dat') !== 'dat') {
                    throw ValidationException::withMessages([
                        "items.{$index}.nhap_kho_id" => "Dòng {$rowNumber}: Chỉ được xuất hàng đạt.",
                    ]);
                }

                if ($soLuongXuat > (float) $nhapKho->source_total_remaining) {
                    throw ValidationException::withMessages([
                        "items.{$index}.so_luong_xuat" => "Dòng {$rowNumber}: SL xuất vượt tồn còn lại.",
                    ]);
                }

                foreach ($this->allocateNhapKhoLots($nhapKho, $soLuongXuat) as $allocation) {
                    $selectedRows[] = $allocation;
                }
            }

            $batchId = ActivityLogger::batchId();

            DB::transaction(function () use ($data, $selectedRows, $batchId) {
                $phieuXuatKho = PhieuXuatKho::create([
                    'so_phieu' => $data['so_phieu'],
                    'ngay_xuat' => $data['ngay_xuat'],
                    'kenh_ban' => $data['kenh_ban'],
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);

                foreach ($selectedRows as $row) {
                    /** @var NhapKho $nhapKho */
                    $nhapKho = $row['nhap_kho'];

                    $phieuXuatKho->chiTiets()->create([
                        'nhap_kho_id' => $nhapKho->id,
                        'don_hang_chi_tiet_id' => $this->donHangChiTietFromNhapKho($nhapKho)?->id,
                        'so_luong_xuat' => $row['so_luong_xuat'],
                        'ghi_chu' => $data['ghi_chu'] ?? null,
                    ]);
                }

                ActivityLogger::log([
                    'action' => count($selectedRows) > 1 ? 'BULK_EXPORT_KHO' : 'EXPORT',
                    'module' => 'Xuất kho',
                    'model_type' => PhieuXuatKho::class,
                    'model_id' => $phieuXuatKho->id,
                    'description' => 'Tạo phiếu xuất kho '.$phieuXuatKho->so_phieu,
                    'new_values' => [
                        'phieu' => ActivityLogger::modelValues($phieuXuatKho),
                        'items' => collect($selectedRows)->map(fn (array $row): array => [
                            'nhap_kho_id' => $row['nhap_kho']->id,
                            'so_luong_xuat' => $row['so_luong_xuat'],
                        ])->values()->all(),
                        'tong_so_luong_xuat' => collect($selectedRows)->sum('so_luong_xuat'),
                    ],
                    'batch_id' => $batchId,
                ]);
            });

            return $this->redirectToIndex('Thêm xuất kho thành công.');
        } catch (\Throwable $exception) {
            if ($submitToken !== '') {
                Cache::forget($this->submitTokenCacheKey($submitToken));
            }

            throw $exception;
        }
    }

    public function edit(PhieuXuatKho $phieu_xuat_kho): View
    {
        $xuatKho = $phieu_xuat_kho;

        $chiTiets = $xuatKho->chiTiets()
            ->with([
                'nhapKho.qc.phanBoMay.cat.matHang',
                'nhapKho.qc.phanBoMay.cat.mau',
                'nhapKho.qc.phanBoMay.cat.size',
                'nhapKho.qc.phanBoMay.donViMay',
                'nhapKho.qc.matHang',
                'nhapKho.qc.mau',
                'nhapKho.qc.size',
                'nhapKho.qc.donHangChiTiet.donHang',
                'nhapKho.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->get();

        $chiTiet = $chiTiets->firstOrFail();
        $currentSourceKey = $this->sourceGroupKeyFromNhapKho($chiTiet->nhapKho);
        $currentSourceQuantity = $chiTiets
            ->filter(fn (PhieuXuatKhoChiTiet $item): bool => $this->sourceGroupKeyFromNhapKho($item->nhapKho) === $currentSourceKey)
            ->sum('so_luong_xuat');

        return view('content.san-xuat.xuat-kho.edit', [
            'phieuXuatKho' => $xuatKho,
            'chiTiet' => $chiTiet,
            'currentSourceQuantity' => $currentSourceQuantity,
            ...$this->formOptions($chiTiet, $xuatKho),
        ]);
    }

    public function update(UpdateXuatKhoRequest $request, PhieuXuatKho $phieu_xuat_kho): RedirectResponse
    {
        $xuatKho = $phieu_xuat_kho;
        $data = $request->validated();
        $chiTiet = $xuatKho->chiTiets()->firstOrFail();
        $nhapKho = NhapKho::query()
            ->with([
                'qc.phanBoMay.cat.matHang',
                'qc.phanBoMay.cat.mau',
                'qc.phanBoMay.cat.size',
                'qc.phanBoMay.cat.donHangChiTiet.donHang',
                'qc.phanBoMay.donHangChiTiet.donHang',
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->findOrFail((int) $data['nhap_kho_id']);

        $selectedRows = $this->allocateNhapKhoLots($nhapKho, (float) $data['so_luong_xuat'], $xuatKho);

        DB::transaction(function () use ($data, $xuatKho, $selectedRows) {
            $xuatKho->update([
                'so_phieu' => $data['so_phieu'],
                'ngay_xuat' => $data['ngay_xuat'],
                'kenh_ban' => $data['kenh_ban'],
                'ghi_chu' => $data['ghi_chu'] ?? null,
            ]);

            $xuatKho->chiTiets()->get()->each->delete();

            foreach ($selectedRows as $row) {
                /** @var NhapKho $nhapKho */
                $nhapKho = $row['nhap_kho'];

                $xuatKho->chiTiets()->create([
                    'nhap_kho_id' => $nhapKho->id,
                    'don_hang_chi_tiet_id' => $this->donHangChiTietFromNhapKho($nhapKho)?->id,
                    'so_luong_xuat' => $row['so_luong_xuat'],
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);
            }
        });

        return $this->redirectToIndex('Cập nhật xuất kho thành công.');
    }

    public function destroy(PhieuXuatKho $phieu_xuat_kho): RedirectResponse
    {
        $xuatKho = $phieu_xuat_kho;

        DB::transaction(function () use ($xuatKho) {
            $xuatKho->chiTiets()->withTrashed()->get()->each->delete();
            $xuatKho->delete();
        });

        return redirect()
            ->route('xuat-kho.index', request()->query())
            ->with('success', 'Xóa xuất kho thành công.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:phieu_xuat_kho,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một phiếu xuất kho để xóa.',
            'ids.min' => 'Vui lòng chọn ít nhất một phiếu xuất kho để xóa.',
        ])['ids'];

        $phieuXuatKhos = PhieuXuatKho::query()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($phieuXuatKhos): void {
            $phieuXuatKhos->each(function (PhieuXuatKho $phieuXuatKho): void {
                $phieuXuatKho->chiTiets()->withTrashed()->get()->each->delete();
                $phieuXuatKho->delete();
            });
        });

        return redirect()
            ->route('xuat-kho.index', $request->query())
            ->with('success', 'Đã xóa '.$phieuXuatKhos->count().' phiếu xuất kho.');
    }

    private function formOptions(
        ?PhieuXuatKhoChiTiet $currentChiTiet = null,
        ?PhieuXuatKho $currentPhieuXuatKho = null
    ): array
    {
        $sourceGroups = $this->buildSourceGroups($currentChiTiet, $currentPhieuXuatKho);
        $currentSourceKey = $currentChiTiet?->nhapKho ? $this->sourceGroupKeyFromNhapKho($currentChiTiet->nhapKho) : null;
        $oldNhapKhoIds = collect(request()->old('items', []))
            ->pluck('nhap_kho_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (! $currentChiTiet) {
            $sourceGroups = $sourceGroups
                ->filter(fn (NhapKho $source): bool => (float) $source->source_total_remaining > 0 || in_array((int) $source->id, $oldNhapKhoIds, true))
                ->values();
        }

        return [
            'nhapKhos' => $sourceGroups,
            'selectedNhapKhoId' => $currentSourceKey !== null
                ? optional($sourceGroups->firstWhere('source_group_key', $currentSourceKey))->id
                : null,
            'sourceOptions' => $sourceGroups
                ->map(fn (NhapKho $nhapKho): array => $this->sourceOptionFromNhapKho($nhapKho))
                ->values(),
            'selectedItems' => collect(request()->old('items', []))
                ->map(function (array $item) use ($sourceGroups): ?array {
                    $nhapKho = $sourceGroups->firstWhere('id', (int) ($item['nhap_kho_id'] ?? 0));

                    if (! $nhapKho) {
                        return null;
                    }

                    return [
                        ...$this->sourceOptionFromNhapKho($nhapKho),
                        'quantity' => $item['so_luong_xuat'] ?? '',
                    ];
                })
                ->filter()
                ->values(),
        ];
    }

    private function sourceOptionFromNhapKho(NhapKho $nhapKho): array
    {
        $hasOrder = (bool) $nhapKho->source_has_order;
        $product = trim(($nhapKho->source_product_code ?? '').'/'.($nhapKho->source_product_name ?? ''), '/');
        $labelParts = array_filter([
            $hasOrder ? $nhapKho->source_order_number : 'Không đơn',
            $hasOrder ? $nhapKho->source_customer_number : null,
            $product !== '' ? $product : null,
            $nhapKho->source_color,
            $nhapKho->source_size,
            'Còn lại: '.$this->formatNumberForOption($nhapKho->source_total_remaining),
        ], fn ($value): bool => $value !== null && $value !== '');

        $label = implode(' - ', $labelParts);
        $searchText = implode(' ', array_filter([
            $label,
            $nhapKho->source_order_number,
            $nhapKho->source_customer_number,
            $nhapKho->source_product_code,
            $nhapKho->source_product_name,
            $nhapKho->source_color,
            $nhapKho->source_size,
            $nhapKho->source_kenh_ban,
            optional($nhapKho->ngay_nhap)->format('d/m/Y'),
            $nhapKho->id,
        ], fn ($value): bool => $value !== null && $value !== ''));

        return [
            'id' => (int) $nhapKho->id,
            'label' => $label !== '' ? $label : 'Nguồn xuất #'.$nhapKho->id,
            'search_text' => mb_strtolower($searchText),
            'has_order' => $hasOrder,
            'order_number' => $hasOrder ? ($nhapKho->source_order_number ?? '') : '',
            'customer_number' => $hasOrder ? ($nhapKho->source_customer_number ?? '') : '',
            'order_quantity' => $hasOrder ? ($nhapKho->source_order_quantity ?? '') : '',
            'product_code' => $nhapKho->source_product_code ?? '',
            'product_name' => $nhapKho->source_product_name ?? '',
            'color' => $nhapKho->source_color ?? '',
            'size' => $nhapKho->source_size ?? '',
            'kenh_ban' => in_array($nhapKho->source_kenh_ban ?? '', self::KENH_BAN_OPTIONS, true) ? $nhapKho->source_kenh_ban : '',
            'imported' => (string) $nhapKho->source_total_imported,
            'exported' => (string) $nhapKho->source_total_exported,
            'remaining' => (string) $nhapKho->source_total_remaining,
        ];
    }

    private function formatNumberForOption(mixed $value): string
    {
        $number = (float) $value;

        if (floor($number) == $number) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
    }

    private function getNhapKhoOptions(?PhieuXuatKhoChiTiet $currentChiTiet = null): Collection
    {
        return $this->buildSourceGroups($currentChiTiet);
    }

    private function ensureXuatKhoLimit(
        NhapKho $nhapKho,
        float $soLuongXuat,
        ?PhieuXuatKhoChiTiet $currentChiTiet = null
    ): void {
        $sourceGroups = $this->buildSourceGroups($currentChiTiet);
        $sourceGroupKey = $this->sourceGroupKeyFromNhapKho($nhapKho);
        $sourceSummary = $sourceGroups->firstWhere('source_group_key', $sourceGroupKey);
        $remaining = (float) ($sourceSummary?->source_total_remaining ?? 0);

        if ($soLuongXuat > $remaining) {
            throw ValidationException::withMessages([
                'so_luong_xuat' => 'Vượt quá số lượng nhập kho còn lại cho phép.',
            ]);
        }
    }

    private function allocateNhapKhoLots(
        NhapKho $sourceNhapKho,
        float $soLuongXuat,
        ?PhieuXuatKho $currentPhieuXuatKho = null
    ): SupportCollection {
        $sourceNhapKho->loadMissing([
            'qc.phanBoMay.cat.matHang',
            'qc.phanBoMay.cat.mau',
            'qc.phanBoMay.cat.size',
            'qc.phanBoMay.cat.donHangChiTiet.donHang',
            'qc.phanBoMay.donHangChiTiet.donHang',
            'qc.phanBoMay.donViMay',
            'qc.matHang',
            'qc.mau',
            'qc.size',
            'qc.donHangChiTiet.donHang',
            'donHangChiTiet.donHang',
        ]);

        $sourceGroupKey = $this->sourceGroupKeyFromNhapKho($sourceNhapKho);

        if ($sourceGroupKey === null) {
            throw ValidationException::withMessages([
                'items' => 'Nguồn xuất không hợp lệ.',
            ]);
        }

        $lots = NhapKho::query()
            ->with([
                'qc.phanBoMay.cat.matHang',
                'qc.phanBoMay.cat.mau',
                'qc.phanBoMay.cat.size',
                'qc.phanBoMay.cat.donHangChiTiet.donHang',
                'qc.phanBoMay.donHangChiTiet.donHang',
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->where('loai_ton', 'dat')
            ->whereNull('deleted_at')
            ->get()
            ->filter(fn (NhapKho $nhapKho): bool => $this->sourceGroupKeyFromNhapKho($nhapKho) === $sourceGroupKey)
            ->sortBy(function (NhapKho $nhapKho): string {
                $date = $nhapKho->ngay_nhap
                    ?? $nhapKho->qc?->ngay_qc
                    ?? $nhapKho->created_at;

                return ($date ? $date->format('Y-m-d H:i:s') : '9999-12-31 23:59:59').sprintf('-%010d', $nhapKho->id);
            })
            ->values();

        $lotIds = $lots->pluck('id')->all();
        $exportedByLot = $lotIds === []
            ? collect()
            : PhieuXuatKhoChiTiet::query()
                ->select('nhap_kho_id', DB::raw('COALESCE(SUM(so_luong_xuat), 0) as total_exported'))
                ->whereIn('nhap_kho_id', $lotIds)
                ->whereNull('deleted_at')
                ->whereHas('phieuXuatKho', function (Builder $query) use ($currentPhieuXuatKho): void {
                    if ($currentPhieuXuatKho) {
                        $query->where('id', '!=', $currentPhieuXuatKho->id);
                    }
                })
                ->groupBy('nhap_kho_id')
                ->pluck('total_exported', 'nhap_kho_id');

        $remainingToExport = $soLuongXuat;
        $allocations = collect();

        foreach ($lots as $lot) {
            if ($remainingToExport <= 0) {
                break;
            }

            $lotRemaining = max(
                0,
                (float) $lot->so_luong_nhap - (float) ($exportedByLot[$lot->id] ?? 0)
            );

            if ($lotRemaining <= 0) {
                continue;
            }

            $quantity = min($lotRemaining, $remainingToExport);
            $remainingToExport = round($remainingToExport - $quantity, 4);

            $allocations->push([
                'nhap_kho' => $lot,
                'so_luong_xuat' => $quantity,
            ]);
        }

        if ($remainingToExport > 0.00009) {
            $available = max(0, $soLuongXuat - $remainingToExport);

            throw ValidationException::withMessages([
                'items' => 'SL xuất vượt tồn còn lại. Nguồn này chỉ còn '.$this->formatNumberForOption($available).'.',
            ]);
        }

        return $allocations;
    }

    private function buildSourceGroups(
        ?PhieuXuatKhoChiTiet $currentChiTiet = null,
        ?PhieuXuatKho $currentPhieuXuatKho = null
    ): Collection
    {
        $nhapKhos = NhapKho::query()
            ->with([
                'qc.phanBoMay.cat.matHang',
                'qc.phanBoMay.cat.mau',
                'qc.phanBoMay.cat.size',
                'qc.phanBoMay.cat.donHangChiTiet.donHang',
                'qc.phanBoMay.donHangChiTiet.donHang',
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->where('loai_ton', 'dat')
            ->whereNull('deleted_at')
            ->get();

        $xuatChiTiets = PhieuXuatKhoChiTiet::query()
            ->with([
                'phieuXuatKho',
                'nhapKho.qc.phanBoMay.cat.matHang',
                'nhapKho.qc.phanBoMay.cat.mau',
                'nhapKho.qc.phanBoMay.cat.size',
                'nhapKho.qc.phanBoMay.cat.donHangChiTiet.donHang',
                'nhapKho.qc.phanBoMay.donHangChiTiet.donHang',
                'nhapKho.qc.phanBoMay.donViMay',
                'nhapKho.qc.matHang',
                'nhapKho.qc.mau',
                'nhapKho.qc.size',
                'nhapKho.qc.donHangChiTiet.donHang',
                'nhapKho.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->whereHas('phieuXuatKho')
            ->whereHas('nhapKho', function (Builder $query) {
                $query->where('loai_ton', 'dat');
            })
            ->whereNull('deleted_at')
            ->get();

        $nhapGroups = $nhapKhos->groupBy(function (NhapKho $nhapKho): ?string {
            return $this->sourceGroupKeyFromNhapKho($nhapKho);
        });

        $xuatGroups = $xuatChiTiets->groupBy(function (PhieuXuatKhoChiTiet $chiTiet): ?string {
            return $this->sourceGroupKeyFromNhapKho($chiTiet->nhapKho);
        });

        $currentSourceKey = $currentChiTiet?->nhapKho ? $this->sourceGroupKeyFromNhapKho($currentChiTiet->nhapKho) : null;

        return $nhapGroups
            ->map(function (SupportCollection $group, ?string $sourceGroupKey) use ($xuatGroups, $currentSourceKey, $currentChiTiet, $currentPhieuXuatKho) {
                if ($sourceGroupKey === null) {
                    return null;
                }

                /** @var NhapKho $representativeNhapKho */
                $representativeNhapKho = $group->sortByDesc('id')->first();
                $totalNhap = (float) $group->sum('so_luong_nhap');
                $totalXuat = (float) ($xuatGroups->get($sourceGroupKey, collect())->sum('so_luong_xuat'));

                if ($currentPhieuXuatKho && $currentSourceKey === $sourceGroupKey) {
                    $totalXuat -= (float) $xuatGroups
                        ->get($sourceGroupKey, collect())
                        ->filter(fn (PhieuXuatKhoChiTiet $chiTiet): bool => (int) $chiTiet->phieu_xuat_kho_id === (int) $currentPhieuXuatKho->id)
                        ->sum('so_luong_xuat');
                } elseif ($currentChiTiet && $currentSourceKey === $sourceGroupKey) {
                    $totalXuat -= (float) $currentChiTiet->so_luong_xuat;
                }

                $donHangChiTiet = $this->donHangChiTietFromNhapKho($representativeNhapKho);

                $representativeNhapKho->setAttribute('source_group_key', $sourceGroupKey);
                $representativeNhapKho->setAttribute('source_has_order', (bool) $donHangChiTiet);
                $representativeNhapKho->setAttribute('source_order_number', $donHangChiTiet?->donHang?->ma_don);
                $representativeNhapKho->setAttribute('source_customer_number', $donHangChiTiet?->donHang?->ma_kh);
                $representativeNhapKho->setAttribute('source_order_quantity', $donHangChiTiet?->so_luong_dat);
                $representativeNhapKho->setAttribute('source_product_code', $representativeNhapKho->qc?->phanBoMay?->cat?->matHang?->ma_hang ?? $representativeNhapKho->qc?->matHang?->ma_hang);
                $representativeNhapKho->setAttribute('source_product_name', $representativeNhapKho->qc?->phanBoMay?->cat?->matHang?->ten_hang ?? $representativeNhapKho->qc?->matHang?->ten_hang);
                $representativeNhapKho->setAttribute('source_color', $representativeNhapKho->qc?->phanBoMay?->cat?->mau?->ten_mau ?? $representativeNhapKho->qc?->mau?->ten_mau);
                $representativeNhapKho->setAttribute('source_size', $representativeNhapKho->qc?->phanBoMay?->cat?->size?->ten_size ?? $representativeNhapKho->qc?->size?->ten_size);
                $sourceKenhBan = $donHangChiTiet?->donHang?->kenh_ban;
                $representativeNhapKho->setAttribute('source_kenh_ban', in_array($sourceKenhBan, self::KENH_BAN_OPTIONS, true) ? $sourceKenhBan : null);
                $representativeNhapKho->setAttribute('source_total_imported', max(0, $totalNhap));
                $representativeNhapKho->setAttribute('source_total_exported', max(0, $totalXuat));
                $representativeNhapKho->setAttribute('source_total_remaining', max(0, $totalNhap - $totalXuat));

                return $representativeNhapKho;
            })
            ->filter()
            ->sortByDesc('id')
            ->values();
    }

    private function sourceGroupKeyFromNhapKho(?NhapKho $nhapKho): ?string
    {
        if (! $nhapKho || ! $nhapKho->qc) {
            return null;
        }

        $qc = $nhapKho->qc;
        $phanBoMay = $qc->phanBoMay;
        $cat = $phanBoMay?->cat;
        $donHangChiTietId = $nhapKho->don_hang_chi_tiet_id
            ?? $qc->don_hang_chi_tiet_id
            ?? $phanBoMay?->don_hang_chi_tiet_id
            ?? $cat?->don_hang_chi_tiet_id;

        if ($donHangChiTietId) {
            return 'order-detail:'.(int) $donHangChiTietId;
        }

        $matHangId = $qc->mat_hang_id ?? $cat?->mat_hang_id;
        $mauId = $qc->mau_id ?? $cat?->mau_id;
        $sizeId = $qc->size_id ?? $cat?->size_id;

        if ($matHangId && $mauId && $sizeId) {
            return sprintf('catalog:%d:%d:%d', $matHangId, $mauId, $sizeId);
        }

        return 'nhap:'.$nhapKho->id;
    }

    private function donHangChiTietFromNhapKho(NhapKho $nhapKho): ?DonHangChiTiet
    {
        return $nhapKho->donHangChiTiet
            ?? $nhapKho->qc?->donHangChiTiet
            ?? $nhapKho->qc?->phanBoMay?->donHangChiTiet
            ?? $nhapKho->qc?->phanBoMay?->cat?->donHangChiTiet;
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('xuat-kho.index')
            ->with('success', $message);
    }

    private function submitTokenCacheKey(string $token): string
    {
        return 'xuat_kho_submit_token:'.$token;
    }
}
