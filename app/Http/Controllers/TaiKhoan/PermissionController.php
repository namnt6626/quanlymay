<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
  public function index(Request $request): View
  {
    $keyword = trim((string) $request->input('q'));

    $permissions = Permission::query()
      ->when($keyword !== '', function ($query) use ($keyword) {
        $query->where(function ($query) use ($keyword) {
          $query->where('ma_quyen', 'like', "%{$keyword}%")
            ->orWhere('ten_quyen', 'like', "%{$keyword}%")
            ->orWhere('module', 'like', "%{$keyword}%")
            ->orWhere('action', 'like', "%{$keyword}%");
        });
      })
      ->latest('id')
      ->paginate(10)
      ->withQueryString();

    return view('content.tai-khoan.permission.index', compact('permissions', 'keyword'));
  }

  public function create(): View
  {
    return view('content.tai-khoan.permission.create', $this->formOptions());
  }

  public function store(StorePermissionRequest $request): RedirectResponse
  {
    Permission::create($request->validated());

    return redirect()
      ->route('permission.index')
      ->with('success', 'Thêm quyền thành công.');
  }

  public function edit(Permission $permission): View
  {
    return view('content.tai-khoan.permission.edit', array_merge($this->formOptions(), compact('permission')));
  }

  public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
  {
    $permission->update($request->validated());

    return redirect()
      ->route('permission.index')
      ->with('success', 'Cập nhật quyền thành công.');
  }

  public function destroy(Permission $permission): RedirectResponse
  {
    $permission->delete();

    return redirect()
      ->route('permission.index')
      ->with('success', 'Xóa quyền thành công.');
  }

  private function formOptions(): array
  {
    return [
      'moduleOptions' => [
        'Danh mục' => 'Danh mục',
        'Cắt' => 'Cắt',
        'Phân bổ may' => 'Phân bổ may',
        'QC' => 'QC',
        'Nhập kho' => 'Nhập kho',
        'Xuất kho' => 'Xuất kho',
        'Tồn kho' => 'Tồn kho',
        'Tài khoản' => 'Tài khoản',
      ],
      'actionOptions' => [
        'VIEW' => 'VIEW',
        'CREATE' => 'CREATE',
        'EDIT' => 'EDIT',
        'DELETE' => 'DELETE',
      ],
    ];
  }
}
