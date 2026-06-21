<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\XuatKho\StoreXuatKhoRequest;
use App\Http\Requests\XuatKho\UpdateXuatKhoRequest;
use App\Models\NhapKho;
use App\Models\PhieuXuatKho;
use App\Models\PhieuXuatKhoChiTiet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhieuXuatKhoController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));
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
                'donHangChiTiet.donHang',
            ])
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
            ->paginate(10)
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

        return view('content.san-xuat.xuat-kho.index', compact('chiTiets', 'keyword'));
    }

    public function create(): View
    {
        return view('content.san-xuat.xuat-kho.create', $this->formOptions());
    }

    public function store(StoreXuatKhoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = collect($data['items'] ?? [])
            ->filter(fn (array $item): bool => (float) ($item['so_luong_xuat'] ?? 0) > 0)
            ->values();

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

            $selectedRows[] = [
                'nhap_kho' => $nhapKho,
                'so_luong_xuat' => $soLuongXuat,
            ];
        }

        DB::transaction(function () use ($data, $selectedRows) {
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
                    'don_hang_chi_tiet_id' => $nhapKho->don_hang_chi_tiet_id,
                    'so_luong_xuat' => $row['so_luong_xuat'],
                    'ghi_chu' => $data['ghi_chu'] ?? null,
                ]);
            }
        });

        return $this->redirectToIndex('Thêm xuất kho thành công.');
    }

    public function edit(PhieuXuatKho $phieu_xuat_kho): View
    {
        $xuatKho = $phieu_xuat_kho;

        $chiTiet = $xuatKho->chiTiets()
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
            ->firstOrFail();

        return view('content.san-xuat.xuat-kho.edit', [
            'phieuXuatKho' => $xuatKho,
            'chiTiet' => $chiTiet,
            ...$this->formOptions($chiTiet),
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
                'qc.phanBoMay.donViMay',
                'qc.matHang',
                'qc.mau',
                'qc.size',
                'qc.donHangChiTiet.donHang',
                'donHangChiTiet.donHang',
            ])
            ->findOrFail((int) $data['nhap_kho_id']);

        $this->ensureXuatKhoLimit($nhapKho, (float) $data['so_luong_xuat'], $chiTiet);
        $data['don_hang_chi_tiet_id'] = $nhapKho->don_hang_chi_tiet_id;

        DB::transaction(function () use ($data, $xuatKho, $chiTiet) {
            $xuatKho->update([
                'so_phieu' => $data['so_phieu'],
                'ngay_xuat' => $data['ngay_xuat'],
                'kenh_ban' => $data['kenh_ban'],
                'ghi_chu' => $data['ghi_chu'] ?? null,
            ]);

            $chiTiet->update([
                'nhap_kho_id' => $data['nhap_kho_id'],
                'don_hang_chi_tiet_id' => $data['don_hang_chi_tiet_id'] ?? null,
                'so_luong_xuat' => $data['so_luong_xuat'],
                'ghi_chu' => $data['ghi_chu'] ?? null,
            ]);
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

        return $this->redirectToIndex('Xóa xuất kho thành công.');
    }

    private function formOptions(?PhieuXuatKhoChiTiet $currentChiTiet = null): array
    {
        $sourceGroups = $this->buildSourceGroups($currentChiTiet);
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
            'kenh_ban' => $nhapKho->source_kenh_ban ?? '',
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

    private function buildSourceGroups(?PhieuXuatKhoChiTiet $currentChiTiet = null): Collection
    {
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
            ->where('loai_ton', 'dat')
            ->whereNull('deleted_at')
            ->get();

        $xuatChiTiets = PhieuXuatKhoChiTiet::query()
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
            ->map(function (Collection $group, ?string $sourceGroupKey) use ($xuatGroups, $currentSourceKey, $currentChiTiet) {
                if ($sourceGroupKey === null) {
                    return null;
                }

                /** @var NhapKho $representativeNhapKho */
                $representativeNhapKho = $group->sortByDesc('id')->first();
                $totalNhap = (float) $group->sum('so_luong_nhap');
                $totalXuat = (float) ($xuatGroups->get($sourceGroupKey, collect())->sum('so_luong_xuat'));

                if ($currentChiTiet && $currentSourceKey === $sourceGroupKey) {
                    $totalXuat -= (float) $currentChiTiet->so_luong_xuat;
                }

                $representativeNhapKho->setAttribute('source_group_key', $sourceGroupKey);
                $representativeNhapKho->setAttribute('source_has_order', (bool) $representativeNhapKho->don_hang_chi_tiet_id);
                $representativeNhapKho->setAttribute('source_order_number', $representativeNhapKho->donHangChiTiet?->donHang?->ma_don);
                $representativeNhapKho->setAttribute('source_customer_number', $representativeNhapKho->donHangChiTiet?->donHang?->ma_kh);
                $representativeNhapKho->setAttribute('source_order_quantity', $representativeNhapKho->donHangChiTiet?->so_luong_dat);
                $representativeNhapKho->setAttribute('source_product_code', $representativeNhapKho->qc?->phanBoMay?->cat?->matHang?->ma_hang ?? $representativeNhapKho->qc?->matHang?->ma_hang);
                $representativeNhapKho->setAttribute('source_product_name', $representativeNhapKho->qc?->phanBoMay?->cat?->matHang?->ten_hang ?? $representativeNhapKho->qc?->matHang?->ten_hang);
                $representativeNhapKho->setAttribute('source_color', $representativeNhapKho->qc?->phanBoMay?->cat?->mau?->ten_mau ?? $representativeNhapKho->qc?->mau?->ten_mau);
                $representativeNhapKho->setAttribute('source_size', $representativeNhapKho->qc?->phanBoMay?->cat?->size?->ten_size ?? $representativeNhapKho->qc?->size?->ten_size);
                $representativeNhapKho->setAttribute('source_kenh_ban', $representativeNhapKho->donHangChiTiet?->donHang?->kenh_ban);
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

        return 'nhap:'.$nhapKho->id;
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('xuat-kho.index')
            ->with('success', $message);
    }
}
