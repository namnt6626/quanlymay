<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $roles = Role::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_vai_tro', 'like', "%{$keyword}%")
                        ->orWhere('ten_vai_tro', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        return view('content.tai-khoan.role.index', compact('roles', 'keyword'));
    }

    public function create(): View
    {
        return view('content.tai-khoan.role.create');
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        return redirect()
            ->route('role.index')
            ->with('success', 'Thêm vai trò thành công.');
    }

    public function edit(Role $role): View
    {
        return view('content.tai-khoan.role.edit', compact('role'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        return redirect()
            ->route('role.index')
            ->with('success', 'Cập nhật vai trò thành công.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return redirect()
            ->route('role.index')
            ->with('success', 'Xóa vai trò thành công.');
    }
}
