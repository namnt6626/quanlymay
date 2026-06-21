<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Http\Requests\RolePermission\UpdateRolePermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
  public function index(Request $request): View
  {
    $keyword = trim((string) $request->input('q'));

    $roles = Role::query()
      ->withCount('permissions')
      ->when($keyword !== '', function ($query) use ($keyword) {
        $query->where(function ($query) use ($keyword) {
          $query->where('ma_vai_tro', 'like', "%{$keyword}%")
            ->orWhere('ten_vai_tro', 'like', "%{$keyword}%");
        });
      })
      ->latest('id')
      ->paginate(paginationPerPage())
      ->withQueryString();

    return view('content.tai-khoan.role-permission.index', compact('roles', 'keyword'));
  }

  public function edit(Role $role): View
  {
    $moduleOrder = [
      'Danh mục',
      'Cắt',
      'Phân bổ may',
      'QC',
      'Nhập kho',
      'Xuất kho',
      'Tồn kho',
      'Tài khoản',
    ];

    $actionOrder = ['VIEW', 'CREATE', 'EDIT', 'DELETE'];

    $permissions = Permission::query()
      ->get()
      ->sortBy(function (Permission $permission) use ($moduleOrder, $actionOrder) {
        $moduleIndex = array_search($permission->module, $moduleOrder, true);
        $actionIndex = array_search($permission->action, $actionOrder, true);

        return sprintf(
          '%02d-%02d-%s',
          $moduleIndex === false ? 99 : $moduleIndex,
          $actionIndex === false ? 99 : $actionIndex,
          $permission->ma_quyen
        );
      })
      ->groupBy('module');

    $assignedPermissionIds = $role->permissions()->pluck('permissions.id')->all();

    return view('content.tai-khoan.role-permission.edit', compact('role', 'permissions', 'assignedPermissionIds'));
  }

  public function update(UpdateRolePermissionRequest $request, Role $role): RedirectResponse
  {
    $oldPermissions = $role->permissions()->pluck('ma_quyen')->values()->all();
    $role->permissions()->sync($request->validated()['permissions'] ?? []);
    $newPermissions = $role->fresh('permissions')->permissions->pluck('ma_quyen')->values()->all();

    ActivityLogger::log([
      'action' => 'ASSIGN_PERMISSION',
      'module' => 'Phân quyền',
      'model_type' => Role::class,
      'model_id' => $role->id,
      'description' => 'Cập nhật phân quyền vai trò '.$role->ma_vai_tro,
      'old_values' => ['permissions' => $oldPermissions],
      'new_values' => ['permissions' => $newPermissions],
    ]);

    return redirect()
      ->route('role-permission.index')
      ->with('success', 'Cập nhật phân quyền thành công.');
  }
}
