<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonHangHoanThanh\CommitImportRequest;
use App\Http\Requests\DonHangHoanThanh\PreviewImportRequest;
use App\Http\Requests\DonHangHoanThanh\StoreDonHangHoanThanhRequest;
use App\Http\Requests\DonHangHoanThanh\UpdateDonHangHoanThanhRequest;
use App\Models\DonHangHoanThanh;
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
            'q' => trim((string) $request->input('q')),
            'tu_ngay' => trim((string) $request->input('tu_ngay')),
            'den_ngay' => trim((string) $request->input('den_ngay')),
            'kenh_ban' => in_array($request->input('kenh_ban'), ['Tiktok', 'Shopee'], true) ? $request->input('kenh_ban') : '',
            'nguon' => in_array($request->input('nguon'), ['excel', 'thu_cong'], true) ? $request->input('nguon') : '',
            'per_page' => paginationPerPage(),
        ];

        $orders = DonHangHoanThanh::query()
            ->with('chiTiets')
            ->withSum('chiTiets as tong_so_luong', 'so_luong')
            ->withSum('chiTiets as tong_thanh_tien', 'thanh_tien')
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $keyword = $filters['q'];
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('ten_san_pham', 'like', "%{$keyword}%")
                        ->orWhereHas('chiTiets', fn (Builder $detail) => $detail->where('mau', 'like', "%{$keyword}%")->orWhere('size', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['tu_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan_thanh', '>=', $filters['tu_ngay']))
            ->when($filters['den_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan_thanh', '<=', $filters['den_ngay']))
            ->when($filters['kenh_ban'] !== '', fn (Builder $query) => $query->where('kenh_ban', $filters['kenh_ban']))
            ->when($filters['nguon'] !== '', fn (Builder $query) => $query->whereHas('chiTiets', fn (Builder $detail) => $detail->where('nguon', $filters['nguon'])))
            ->orderByDesc('ngay_hoan_thanh')->orderByDesc('id')
            ->paginate($filters['per_page'])->withQueryString();

        return view('content.san-xuat.don-hang-hoan-thanh.index', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('content.san-xuat.don-hang-hoan-thanh.create');
    }

    public function store(StoreDonHangHoanThanhRequest $request): RedirectResponse
    {
        $this->service->saveManual($request->validated());
        return redirect()->route('don-hang-hoan-thanh.index')->with('success', 'Thêm đơn hàng hoàn thành thành công.');
    }

    public function edit(DonHangHoanThanh $donHangHoanThanh): View
    {
        $donHangHoanThanh->load('chiTiets');
        return view('content.san-xuat.don-hang-hoan-thanh.edit', ['order' => $donHangHoanThanh]);
    }

    public function update(UpdateDonHangHoanThanhRequest $request, DonHangHoanThanh $donHangHoanThanh): RedirectResponse
    {
        $this->service->saveManual($request->validated(), $donHangHoanThanh);
        return redirect()->route('don-hang-hoan-thanh.index')->with('success', 'Cập nhật đơn hàng hoàn thành thành công.');
    }

    public function destroy(DonHangHoanThanh $donHangHoanThanh): RedirectResponse
    {
        $donHangHoanThanh->delete();
        return back()->with('success', 'Xóa đơn hàng hoàn thành thành công.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:don_hang_hoan_thanh,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một đơn hàng hoàn thành để xóa.',
            'ids.min' => 'Vui lòng chọn ít nhất một đơn hàng hoàn thành để xóa.',
        ])['ids'];

        $orders = DonHangHoanThanh::query()->whereIn('id', $ids)->get();

        DB::transaction(fn () => $orders->each->delete());

        return redirect()
            ->route('don-hang-hoan-thanh.index', $request->query())
            ->with('success', 'Đã xóa '.$orders->count().' đơn hàng hoàn thành.');
    }

    public function importForm(): View
    {
        return view('content.san-xuat.don-hang-hoan-thanh.import');
    }

    public function preview(PreviewImportRequest $request, XlsxReader $reader, PhanLoaiParser $parser): View
    {
        $channel = $request->validated('kenh_ban');
        $sheetRows = $reader->rows($request->file('file_excel')->getRealPath());
        $headerIndex = collect($sheetRows)->search(fn (array $row) => $this->isHeader($row['values']));
        if ($headerIndex === false) return back()->withErrors(['file_excel' => 'Không tìm thấy dòng tiêu đề đúng định dạng trong file.']);

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

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable) {}
        }
        return null;
    }
}
