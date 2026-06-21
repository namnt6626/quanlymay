<?php

namespace App\Http\Requests\RolePermission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'permissions' => ['nullable', 'array'],
      'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
    ];
  }

  public function attributes(): array
  {
    return [
      'permissions' => 'Quyền',
      'permissions.*' => 'Quyền',
    ];
  }

  public function messages(): array
  {
    return [
      'permissions.array' => 'Danh sách quyền không hợp lệ.',
      'permissions.*.integer' => 'Quyền không hợp lệ.',
      'permissions.*.distinct' => 'Danh sách quyền bị trùng.',
      'permissions.*.exists' => 'Quyền không tồn tại.',
    ];
  }
}
