<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonViMay\StoreDonViMayRequest;
use App\Http\Requests\DonViMay\UpdateDonViMayRequest;
use App\Models\DmDonViMay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonViMayController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $donViMays = DmDonViMay::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_don_vi', 'like', "%{$keyword}%")
                        ->orWhere('ten_don_vi', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        return view('content.danh-muc.don-vi-may.index', compact('donViMays', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.don-vi-may.create');
    }

    public function store(StoreDonViMayRequest $request): RedirectResponse
    {
        DmDonViMay::create($request->validated());

        return redirect()
            ->route('don-vi-may.index')
            ->with('success', 'Thêm đơn vị may thành công.');
    }

    public function edit(DmDonViMay $donViMay): View
    {
        return view('content.danh-muc.don-vi-may.edit', [
            'donViMay' => $donViMay,
        ]);
    }

    public function update(UpdateDonViMayRequest $request, DmDonViMay $donViMay): RedirectResponse
    {
        $donViMay->update($request->validated());

        return redirect()
            ->route('don-vi-may.index')
            ->with('success', 'Cập nhật đơn vị may thành công.');
    }

    public function destroy(DmDonViMay $donViMay): RedirectResponse
    {
        $donViMay->delete();

        return redirect()
            ->route('don-vi-may.index')
            ->with('success', 'Xóa đơn vị may thành công.');
    }
}
