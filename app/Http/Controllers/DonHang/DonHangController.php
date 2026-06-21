<?php

namespace App\Http\Controllers\DonHang;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonHang\StoreDonHangRequest;
use App\Http\Requests\DonHang\UpdateDonHangRequest;
use App\Models\DonHang;
use App\Models\MatHang;
use App\Models\Mau;
use App\Models\DmSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DonHangController extends Controller
{
  public function index(Request $request): View
  {
    $keyword = trim((string) $request->input('q'));
    $ngayNhanFrom = trim((string) $request->input('ngay_nhan_from'));
    $ngayNhanTo = trim((string) $request->input('ngay_nhan_to'));
    $hanGiaoFrom = trim((string) $request->input('han_giao_from'));
    $hanGiaoTo = trim((string) $request->input('han_giao_to'));

    $donHangs = DonHang::query()
      ->withCount(['chiTiets as so_dong_chi_tiet'])
      ->withSum(['chiTiets as tong_so_luong_dat'], 'so_luong_dat')
      ->when($keyword !== '', function ($query) use ($keyword) {
        $query->where(function ($query) use ($keyword) {
          $query->where('ma_don', 'like', "%{$keyword}%")
            ->orWhere('ma_kh', 'like', "%{$keyword}%")
            ->orWhere('kenh_ban', 'like', "%{$keyword}%");
        });
      })
      ->when($ngayNhanFrom !== '', fn($query) => $query->whereDate('ngay_nhan', '>=', $ngayNhanFrom))
      ->when($ngayNhanTo !== '', fn($query) => $query->whereDate('ngay_nhan', '<=', $ngayNhanTo))
      ->when($hanGiaoFrom !== '', fn($query) => $query->whereDate('han_giao', '>=', $hanGiaoFrom))
      ->when($hanGiaoTo !== '', fn($query) => $query->whereDate('han_giao', '<=', $hanGiaoTo))
      ->latest('id')
      ->paginate(10)
      ->withQueryString();

    return view('content.don-hangs.index', compact('donHangs', 'keyword', 'ngayNhanFrom', 'ngayNhanTo', 'hanGiaoFrom', 'hanGiaoTo'));
  }

  public function create(): View
  {
    return view('content.don-hangs.create', [
      'matHangs' => MatHang::query()->orderBy('ten_hang')->get(),
      'maus' => Mau::query()->orderBy('ten_mau')->get(),
      'sizes' => DmSize::query()->orderBy('ten_size')->get(),
      'detailRows' => [[
        'mat_hang_id' => '',
        'mau_id' => '',
        'size_id' => '',
        'so_luong_dat' => '',
        'ghi_chu' => '',
      ]],
    ]);
  }

  public function store(StoreDonHangRequest $request): RedirectResponse
  {
    DB::transaction(function () use ($request): void {
      $validated = $request->validated();
      $chiTiets = $validated['chi_tiets'];
      unset($validated['chi_tiets']);

      $donHang = DonHang::create($validated);
      $donHang->chiTiets()->createMany($chiTiets);
    });

    return redirect()
      ->route('don-hangs.index')
      ->with('success', 'Thêm đơn hàng thành công.');
  }

  public function show(DonHang $donHang): View
  {
    $donHang->load(['chiTiets.matHang', 'chiTiets.mau', 'chiTiets.size']);

    return view('content.don-hangs.show', [
      'donHang' => $donHang,
      'tongSoLuongDat' => $donHang->chiTiets->sum(fn($chiTiet) => (float) $chiTiet->so_luong_dat),
    ]);
  }

  public function edit(DonHang $donHang): View
  {
    $donHang->load('chiTiets');

    return view('content.don-hangs.edit', [
      'donHang' => $donHang,
      'matHangs' => MatHang::query()->orderBy('ten_hang')->get(),
      'maus' => Mau::query()->orderBy('ten_mau')->get(),
      'sizes' => DmSize::query()->orderBy('ten_size')->get(),
      'detailRows' => $donHang->chiTiets->map(function ($chiTiet): array {
        return [
          'mat_hang_id' => $chiTiet->mat_hang_id,
          'mau_id' => $chiTiet->mau_id,
          'size_id' => $chiTiet->size_id,
          'so_luong_dat' => $chiTiet->so_luong_dat,
          'ghi_chu' => $chiTiet->ghi_chu,
        ];
      })->values()->all(),
    ]);
  }

  public function update(UpdateDonHangRequest $request, DonHang $donHang): RedirectResponse
  {
    DB::transaction(function () use ($request, $donHang): void {
      $validated = $request->validated();
      $chiTiets = $validated['chi_tiets'];
      unset($validated['chi_tiets']);

      $donHang->update($validated);
      $donHang->chiTiets()->delete();
      $donHang->chiTiets()->createMany($chiTiets);
    });

    return redirect()
      ->route('don-hangs.index')
      ->with('success', 'Cập nhật đơn hàng thành công.');
  }

  public function destroy(DonHang $donHang): RedirectResponse
  {
    DB::transaction(function () use ($donHang): void {
      $donHang->chiTiets()->delete();
      $donHang->delete();
    });

    return redirect()
      ->route('don-hangs.index')
      ->with('success', 'Xóa đơn hàng thành công.');
  }
}
