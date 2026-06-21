<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mau\StoreMauRequest;
use App\Http\Requests\Mau\UpdateMauRequest;
use App\Models\Mau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MauController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $maus = Mau::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_mau', 'like', "%{$keyword}%")
                        ->orWhere('ten_mau', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        return view('content.danh-muc.mau.index', compact('maus', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.mau.create');
    }

    public function store(StoreMauRequest $request): RedirectResponse
    {
        Mau::create($request->validated());

        return redirect()
            ->route('mau.index')
            ->with('success', 'Thêm màu sắc thành công.');
    }

    public function edit(Mau $mau): View
    {
        return view('content.danh-muc.mau.edit', compact('mau'));
    }

    public function update(UpdateMauRequest $request, Mau $mau): RedirectResponse
    {
        $mau->update($request->validated());

        return redirect()
            ->route('mau.index')
            ->with('success', 'Cập nhật màu sắc thành công.');
    }

    public function destroy(Mau $mau): RedirectResponse
    {
        $mau->delete();

        return redirect()
            ->route('mau.index')
            ->with('success', 'Xóa màu sắc thành công.');
    }
}
