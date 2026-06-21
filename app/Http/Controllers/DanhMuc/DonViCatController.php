<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonViCat\StoreDonViCatRequest;
use App\Http\Requests\DonViCat\UpdateDonViCatRequest;
use App\Models\DmDonViCat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonViCatController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $donViCats = DmDonViCat::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_don_vi', 'like', "%{$keyword}%")
                        ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('content.danh-muc.don-vi-cat.index', compact('donViCats', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.don-vi-cat.create');
    }

    public function store(StoreDonViCatRequest $request): RedirectResponse
    {
        DmDonViCat::create($request->validated());

        return redirect()
            ->route('don-vi-cat.index')
            ->with('success', 'Thêm đơn vị cắt thành công.');
    }

    public function edit(DmDonViCat $donViCat): View
    {
        return view('content.danh-muc.don-vi-cat.edit', compact('donViCat'));
    }

    public function update(UpdateDonViCatRequest $request, DmDonViCat $donViCat): RedirectResponse
    {
        $donViCat->update($request->validated());

        return redirect()
            ->route('don-vi-cat.index')
            ->with('success', 'Cập nhật đơn vị cắt thành công.');
    }

    public function destroy(DmDonViCat $donViCat): RedirectResponse
    {
        $donViCat->delete();

        return redirect()
            ->route('don-vi-cat.index')
            ->with('success', 'Xóa đơn vị cắt thành công.');
    }
}
