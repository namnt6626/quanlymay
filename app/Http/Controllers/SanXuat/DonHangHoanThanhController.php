<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonHangHoanThanh\CommitImportRequest;
use App\Http\Requests\DonHangHoanThanh\PreviewImportRequest;
use App\Http\Requests\DonHangHoanThanh\StoreDonHangHoanThanhRequest;
use App\Http\Requests\DonHangHoanThanh\UpdateDonHangHoanThanhRequest;
use App\Models\DonHangHoanThanh;
use App\Models\DonHangHoanThanhChiTiet;
use App\Services\DonHangHoanThanh\DonHangHoanThanhService;
use App\Services\DonHangHoanThanh\PhanLoaiParser;
use App\Services\DonHangHoanThanh\XlsxReader;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DonHangHoanThanhController extends Controller
{
    public function __construct(private readonly DonHangHoanThanhService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'ten_san_pham' => trim((string) $request->input('ten_san_pham')),
            'mau' => trim((string) $request->input('mau')),
            'size' => trim((string) $request->input('size')),
            'tu_ngay' => trim((string) $request->input('tu_ngay')),
            'den_ngay' => trim((string) $request->input('den_ngay')),
            'kenh_ban' => in_array($request->input('kenh_ban'), ['Tiktok', 'Shopee', 'Bán sỉ'], true) ? $request->input('kenh_ban') : '',
            'nguon' => in_array($request->input('nguon'), ['excel', 'thu_cong'], true) ? $request->input('nguon') : '',
            'per_page' => paginationPerPage(),
        ];

        $detailFilter = function ($detail) use ($filters): void {
            $detail
                ->when($filters['mau'] !== '', fn (Builder $query) => $query->where('mau', $filters['mau']))
                ->when($filters['size'] !== '', fn (Builder $query) => $query->where('size', $filters['size']))
                ->when($filters['nguon'] !== '', fn (Builder $query) => $query->where('nguon', $filters['nguon']));
        };

        $orders = DonHangHoanThanh::query()
            ->with(['chiTiets' => $detailFilter])
            ->withSum(['chiTiets as tong_so_luong' => $detailFilter], 'so_luong')
            ->withSum(['chiTiets as tong_thanh_tien' => $detailFilter], 'thanh_tien')
            ->when($filters['ten_san_pham'] !== '', fn (Builder $query) => $query->where('ten_san_pham', 'like', '%'.$filters['ten_san_pham'].'%'))
            ->when($filters['mau'] !== '' || $filters['size'] !== '' || $filters['nguon'] !== '', fn (Builder $query) => $query->whereHas('chiTiets', $detailFilter))
            ->when($filters['tu_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan_thanh', '>=', $filters['tu_ngay']))
            ->when($filters['den_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan_thanh', '<=', $filters['den_ngay']))
            ->when($filters['kenh_ban'] !== '', fn (Builder $query) => $query->where('kenh_ban', $filters['kenh_ban']))
            ->orderByDesc('ngay_hoan_thanh')->orderByDesc('id')
            ->paginate($filters['per_page'])->withQueryString();

        $filterOptions = $this->filterOptions();

        return view('content.san-xuat.don-hang-hoan-thanh.index', [
            'orders' => $orders,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        return view('content.san-xuat.don-hang-hoan-thanh.create');
    }

    public function store(StoreDonHangHoanThanhRequest $request): RedirectResponse
    {
        $this->service->saveManual($request->validated());
        return redirect()->route('don-hang-hoan-thanh.index')->with('success', 'Thêm xuất hàng thành công.');
    }

    public function edit(DonHangHoanThanh $donHangHoanThanh): View
    {
        $donHangHoanThanh->load('chiTiets');
        return view('content.san-xuat.don-hang-hoan-thanh.edit', ['order' => $donHangHoanThanh]);
    }

    public function update(UpdateDonHangHoanThanhRequest $request, DonHangHoanThanh $donHangHoanThanh): RedirectResponse
    {
        $this->service->saveManual($request->validated(), $donHangHoanThanh);
        return redirect()->route('don-hang-hoan-thanh.index')->with('success', 'Cập nhật xuất hàng thành công.');
    }

    public function destroy(DonHangHoanThanh $donHangHoanThanh): RedirectResponse
    {
        $donHangHoanThanh->delete();
        return back()->with('success', 'Xóa xuất hàng thành công.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:don_hang_hoan_thanh,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một dòng xuất hàng để xóa.',
            'ids.min' => 'Vui lòng chọn ít nhất một dòng xuất hàng để xóa.',
        ])['ids'];

        $orders = DonHangHoanThanh::query()->whereIn('id', $ids)->get();

        DB::transaction(fn () => $orders->each->delete());

        return redirect()
            ->route('don-hang-hoan-thanh.index', $request->query())
            ->with('success', 'Đã xóa '.$orders->count().' dòng xuất hàng.');
    }

    public function importForm(): View
    {
        return view('content.san-xuat.don-hang-hoan-thanh.import');
    }

    public function preview(PreviewImportRequest $request, XlsxReader $reader, PhanLoaiParser $parser): View
    {
        $channel = $request->validated('kenh_ban');
        $sheetRows = $reader->rows($request->file('file_excel')->getRealPath());

        $productInfoHeaderIndex = $this->findHeaderIndex($sheetRows, fn (array $values) => $this->isProductInfoHeader($values));
        if ($productInfoHeaderIndex !== false) {
            [$rows, $ignored] = $this->parseProductInfoRows($sheetRows, $productInfoHeaderIndex, $parser, $channel);

            if ($rows === []) return back()->withErrors(['file_excel' => 'File không có dòng dữ liệu hợp lệ.']);
            return view('content.san-xuat.don-hang-hoan-thanh.preview', compact('rows', 'ignored'));
        }

        $vietnameseShopeeHeaderIndex = $this->findHeaderIndex($sheetRows, fn (array $values) => $this->isVietnameseShopeeHeader($values));
        if ($vietnameseShopeeHeaderIndex !== false) {
            [$rows, $ignored] = $this->parseVietnameseShopeeRows($sheetRows, $vietnameseShopeeHeaderIndex, $parser, $channel);

            if ($rows === []) return back()->withErrors(['file_excel' => 'File không có dòng dữ liệu hợp lệ.']);
            return view('content.san-xuat.don-hang-hoan-thanh.preview', compact('rows', 'ignored'));
        }

        $headerIndex = collect($sheetRows)->search(fn (array $row) => $this->isHeader($row['values']));
        if ($headerIndex === false) {
            return back()->withErrors([
                'file_excel' => 'Chưa nhận diện được định dạng file. Hiện hỗ trợ: file cột Product Name/Variation/Quantity, file cột product_info, hoặc file Shopee tiếng Việt có Ngày đặt hàng/SKU phân loại hàng/Tên phân loại hàng/Số lượng.',
            ]);
        }

        $header = $sheetRows[$headerIndex]['values'];
        $columns = $this->mapColumns($header);
        $rows = [];
        $ignored = [];

        foreach (array_slice($sheetRows, $headerIndex + 1) as $sheetRow) {
            $values = $sheetRow['values'];
            $product = trim((string) ($values[$columns['product']] ?? ''));
            $quantity = trim((string) ($values[$columns['quantity']] ?? ''));
            $dateText = trim((string) ($values[$columns['created']] ?? ''));
            if ($product === '' && $quantity === '' && $dateText === '') continue;
            $date = $this->parseDate($dateText);
            if ($product === '' || ! is_numeric($quantity) || (float) $quantity <= 0 || ! $date) {
                $ignored[] = $sheetRow['row'];
                continue;
            }
            $variation = trim((string) ($values[$columns['variation']] ?? ''));
            $split = $parser->parse($variation);
            $rows[] = [
                'dong_excel' => $sheetRow['row'],
                'ngay_hoan_thanh' => $date->toDateString(),
                'thoi_gian_tao_goc' => $date->format('Y-m-d H:i:s'),
                'ten_san_pham' => $product,
                'kenh_ban' => $channel,
                'phan_loai_goc' => $variation,
                'mau' => $split['mau'], 'size' => $split['size'],
                'so_luong' => (float) $quantity,
                'thanh_tien' => (float) ($values[$columns['subtotal']] ?? 0),
            ];
        }

        if ($rows === []) return back()->withErrors(['file_excel' => 'File không có dòng dữ liệu hợp lệ.']);
        return view('content.san-xuat.don-hang-hoan-thanh.preview', compact('rows', 'ignored'));
    }

    public function commit(CommitImportRequest $request): RedirectResponse
    {
        $count = $this->service->import($request->validated('rows'));
        return redirect()->route('don-hang-hoan-thanh.index')->with('success', "Đã nhập thành công {$count} dòng từ Excel.");
    }

    private function isHeader(array $values): bool
    {
        $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $values);

        return count(array_intersect(['product name', 'seller sku'], $normalized)) > 0
            && in_array('variation', $normalized, true)
            && in_array('quantity', $normalized, true);
    }

    private function findHeaderIndex(array $sheetRows, callable $matches): int|false
    {
        return collect($sheetRows)->search(fn (array $row) => $matches($row['values']));
    }

    private function isProductInfoHeader(array $values): bool
    {
        return in_array('product_info', array_map(fn ($value) => $this->normalizeHeader((string) $value), $values), true);
    }

    private function isVietnameseShopeeHeader(array $values): bool
    {
        $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $values);

        return in_array('ngày đặt hàng', $normalized, true)
            && in_array('sku phân loại hàng', $normalized, true)
            && in_array('tên phân loại hàng', $normalized, true)
            && in_array('số lượng', $normalized, true);
    }

    private function parseVietnameseShopeeRows(array $sheetRows, int $headerIndex, PhanLoaiParser $parser, string $channel): array
    {
        $header = $sheetRows[$headerIndex]['values'] ?? [];
        $columns = $this->mapVietnameseShopeeColumns($header);
        $rows = [];
        $ignored = [];

        foreach (array_slice($sheetRows, $headerIndex + 1) as $sheetRow) {
            $values = $sheetRow['values'];
            $dateText = trim((string) ($values[$columns['created']] ?? ''));
            $product = trim((string) ($values[$columns['product']] ?? ''));
            $variation = trim((string) ($values[$columns['variation']] ?? ''));
            $quantity = trim((string) ($values[$columns['quantity']] ?? ''));
            $subtotal = trim((string) ($values[$columns['subtotal']] ?? ''));

            if ($dateText === '' && $product === '' && $variation === '' && $quantity === '' && $subtotal === '') continue;

            $date = $this->parseDate($dateText);
            if (! $date || $variation === '' || ! is_numeric($quantity) || (float) $quantity <= 0) {
                $ignored[] = $sheetRow['row'];
                continue;
            }

            $split = $parser->parse($variation);
            $rows[] = [
                'dong_excel' => $sheetRow['row'],
                'ngay_hoan_thanh' => $date->toDateString(),
                'thoi_gian_tao_goc' => $date->format('Y-m-d H:i:s'),
                'ten_san_pham' => $product,
                'kenh_ban' => $channel,
                'phan_loai_goc' => $variation,
                'mau' => $split['mau'],
                'size' => $split['size'],
                'so_luong' => (float) $quantity,
                'thanh_tien' => $subtotal !== '' ? $this->parseLocalizedNumber($subtotal) : '',
            ];
        }

        return [$rows, $ignored];
    }

    private function mapVietnameseShopeeColumns(array $header): array
    {
        $aliases = [
            'created' => ['ngày đặt hàng'],
            'product' => ['sku phân loại hàng'],
            'variation' => ['tên phân loại hàng'],
            'quantity' => ['số lượng'],
            'subtotal' => ['tổng giá trị đơn hàng (vnd)'],
        ];
        $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);
        $mapped = [];
        foreach ($aliases as $key => $labels) {
            $column = false;
            foreach ($labels as $label) {
                $column = array_search($label, $normalized, true);
                if ($column !== false) break;
            }
            if ($column === false) throw new \RuntimeException('Thiếu cột '.implode(' hoặc ', $labels).' trong file Excel.');
            $mapped[$key] = $column;
        }
        return $mapped;
    }

    private function parseProductInfoRows(array $sheetRows, int $headerIndex, PhanLoaiParser $parser, string $channel): array
    {
        $rows = [];
        $ignored = [];
        $date = now()->subDay();

        $productInfoColumn = array_search('product_info', array_map(fn ($value) => $this->normalizeHeader((string) $value), $sheetRows[$headerIndex]['values'] ?? []), true);
        $productInfoColumn = $productInfoColumn !== false ? $productInfoColumn : 'A';

        foreach (array_slice($sheetRows, $headerIndex + 1) as $sheetRow) {
            $text = trim((string) ($sheetRow['values'][$productInfoColumn] ?? ''));
            if ($text === '') continue;

            $items = $this->splitProductInfoItems($text);
            $accepted = 0;

            foreach ($items as $item) {
                $productName = $this->productInfoProductName($item);
                $variation = $this->productInfoField($item, 'Variation Name');
                $quantity = $this->parseLocalizedNumber($this->productInfoField($item, 'Quantity'));

                if ($productName === '' || $quantity <= 0) {
                    continue;
                }

                $split = $parser->parse($variation);

                $rows[] = [
                    'dong_excel' => $sheetRow['row'],
                    'ngay_hoan_thanh' => $date->toDateString(),
                    'thoi_gian_tao_goc' => $date->format('Y-m-d H:i:s'),
                    'ten_san_pham' => $productName,
                    'kenh_ban' => $channel,
                    'phan_loai_goc' => $variation,
                    'mau' => $split['mau'],
                    'size' => $split['size'],
                    'so_luong' => $quantity,
                    'thanh_tien' => '',
                ];
                $accepted++;
            }

            if ($accepted === 0) $ignored[] = $sheetRow['row'];
        }

        return [$rows, $ignored];
    }

    private function splitProductInfoItems(string $text): array
    {
        $text = str_replace(["_x000D_", "\r"], "\n", $text);

        return array_values(array_filter(array_map('trim', preg_split('/(?:^|\n)\s*\[\d+\]\s*/u', $text) ?: [])));
    }

    private function productInfoProductName(string $item): string
    {
        foreach (['Parent SKU Reference No.', 'SKU Reference No.'] as $label) {
            $value = $this->productInfoField($item, $label);
            if ($value !== '') return $value;
        }

        $productName = $this->productInfoField($item, 'Product Name');
        if (preg_match('/\(([^()]*)\)\s*$/u', $productName, $matches)) {
            $code = trim($matches[1]);
            if ($code !== '') return $code;
        }

        return $productName;
    }

    private function productInfoField(string $item, string $label): string
    {
        $labels = 'Product Name|Variation Name|Price|Quantity|SKU Reference No\.|Parent SKU Reference No\.';

        if (! preg_match('/'.preg_quote($label, '/').'\s*:\s*(.*?)(?=;\s*(?:'.$labels.')\s*:|;\s*$|$)/su', $item, $matches)) {
            return '';
        }

        return trim($matches[1]);
    }

    private function parseLocalizedNumber(string $value): float
    {
        $value = preg_replace('/[^\d,.\-]/u', '', trim($value)) ?? '';
        if ($value === '') return 0;

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace('.', '', str_replace(',', '.', $value))
                : str_replace(',', '', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    private function mapColumns(array $header): array
    {
        $aliases = [
            'product' => ['product name', 'seller sku'],
            'variation' => ['variation'],
            'quantity' => ['quantity'],
            'subtotal' => ['sku subtotal after discount', 'sku seller discount'],
            'created' => ['created time'],
        ];
        $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);
        $mapped = [];
        foreach ($aliases as $key => $labels) {
            $column = false;
            foreach ($labels as $label) {
                $column = array_search($label, $normalized, true);
                if ($column !== false) break;
            }
            if ($column === false) throw new \RuntimeException('Thiếu cột '.implode(' hoặc ', $labels).' trong file Excel.');
            $mapped[$key] = $column;
        }
        return $mapped;
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');
    }

    private function parseDate(string $value): ?Carbon
    {
        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addSeconds((int) round((float) $value * 86400));
        }

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable) {}
        }
        return null;
    }

    private function filterOptions(): array
    {
        return [
            'products' => DonHangHoanThanh::query()
                ->select('ten_san_pham')
                ->whereNotNull('ten_san_pham')
                ->distinct()
                ->orderBy('ten_san_pham')
                ->pluck('ten_san_pham'),
            'colors' => DonHangHoanThanhChiTiet::query()
                ->select('mau')
                ->whereNotNull('mau')
                ->where('mau', '<>', '')
                ->distinct()
                ->orderBy('mau')
                ->pluck('mau'),
            'sizes' => DonHangHoanThanhChiTiet::query()
                ->select('size')
                ->whereNotNull('size')
                ->where('size', '<>', '')
                ->distinct()
                ->orderBy('size')
                ->pluck('size'),
        ];
    }
}
