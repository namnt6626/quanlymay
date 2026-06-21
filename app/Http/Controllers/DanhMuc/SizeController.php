<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Size\StoreSizeRequest;
use App\Http\Requests\Size\UpdateSizeRequest;
use App\Models\DmSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SizeController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $sizes = DmSize::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_size', 'like', "%{$keyword}%")
                        ->orWhere('ten_size', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('content.danh-muc.size.index', compact('sizes', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.size.create');
    }

    public function store(StoreSizeRequest $request): RedirectResponse
    {
        DmSize::create($request->validated());

        return redirect()
            ->route('size.index')
            ->with('success', 'Thêm size thành công.');
    }

    public function edit(DmSize $size): View
    {
        return view('content.danh-muc.size.edit', compact('size'));
    }

    public function update(UpdateSizeRequest $request, DmSize $size): RedirectResponse
    {
        $size->update($request->validated());

        return redirect()
            ->route('size.index')
            ->with('success', 'Cập nhật size thành công.');
    }

    public function destroy(DmSize $size): RedirectResponse
    {
        $size->delete();

        return redirect()
            ->route('size.index')
            ->with('success', 'Xóa size thành công.');
    }
}
