<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatHang\StoreMatHangRequest;
use App\Http\Requests\MatHang\UpdateMatHangRequest;
use App\Models\MatHang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatHangController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $matHangs = MatHang::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_hang', 'like', "%{$keyword}%")
                        ->orWhere('ten_hang', 'like', "%{$keyword}%")
                        ->orWhere('mo_ta', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        return view('content.danh-muc.mat-hang.index', compact('matHangs', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.mat-hang.create');
    }

    public function store(StoreMatHangRequest $request): RedirectResponse
    {
        MatHang::create($request->validated());

        return redirect()
            ->route('mat-hang.index')
            ->with('success', 'Thêm mã hàng thành công.');
    }

    public function edit(MatHang $matHang): View
    {
        return view('content.danh-muc.mat-hang.edit', compact('matHang'));
    }

    public function update(UpdateMatHangRequest $request, MatHang $matHang): RedirectResponse
    {
        $matHang->update($request->validated());

        return redirect()
            ->route('mat-hang.index')
            ->with('success', 'Cập nhật mã hàng thành công.');
    }

    public function destroy(MatHang $matHang): RedirectResponse
    {
        $matHang->delete();

        return redirect()
            ->route('mat-hang.index')
            ->with('success', 'Xóa mã hàng thành công.');
    }
}
