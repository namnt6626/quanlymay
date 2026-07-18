<?php

namespace App\Http\Controllers\DanhMuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\KenhBan\StoreKenhBanRequest;
use App\Http\Requests\KenhBan\UpdateKenhBanRequest;
use App\Models\DmKenhBan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KenhBanController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $kenhBans = DmKenhBan::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_kenh', 'like', "%{$keyword}%")
                        ->orWhere('ten_kenh', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        return view('content.danh-muc.kenh-ban.index', compact('kenhBans', 'keyword'));
    }

    public function create(): View
    {
        return view('content.danh-muc.kenh-ban.create');
    }

    public function store(StoreKenhBanRequest $request): RedirectResponse
    {
        DmKenhBan::create($request->validated());

        return redirect()
            ->route('kenh-ban.index')
            ->with('success', 'Thêm kênh bán thành công.');
    }

    public function edit(DmKenhBan $kenhBan): View
    {
        return view('content.danh-muc.kenh-ban.edit', compact('kenhBan'));
    }

    public function update(UpdateKenhBanRequest $request, DmKenhBan $kenhBan): RedirectResponse
    {
        $kenhBan->update($request->validated());

        return redirect()
            ->route('kenh-ban.index')
            ->with('success', 'Cập nhật kênh bán thành công.');
    }

    public function destroy(DmKenhBan $kenhBan): RedirectResponse
    {
        $kenhBan->delete();

        return redirect()
            ->route('kenh-ban.index')
            ->with('success', 'Xóa kênh bán thành công.');
    }
}
