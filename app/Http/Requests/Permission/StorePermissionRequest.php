<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'ma_quyen' => [
        'required',
        'string',
        'max:100',
        Rule::unique('permissions', 'ma_quyen')->whereNull('deleted_at'),
      ],
      'ten_quyen' => ['required', 'string', 'max:255'],
      'module' => ['required', 'string', 'max:255'],
      'action' => ['required', 'string', 'max:50'],
      'mo_ta' => ['nullable', 'string'],
      'trang_thai' => ['required', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'ma_quyen' => 'Mã quyền',
      'ten_quyen' => 'Tên quyền',
      'module' => 'Module',
      'action' => 'Hành động',
      'mo_ta' => 'Mô tả',
      'trang_thai' => 'Trạng thái',
    ];
  }

  public function messages(): array
  {
    return [
      'ma_quyen.required' => 'Mã quyền không được để trống.',
      'ma_quyen.string' => 'Mã quyền phải là chuỗi ký tự.',
      'ma_quyen.max' => 'Mã quyền không được vượt quá :max ký tự.',
      'ma_quyen.unique' => 'Mã quyền đã tồn tại.',
      'ten_quyen.required' => 'Tên quyền không được để trống.',
      'ten_quyen.string' => 'Tên quyền phải là chuỗi ký tự.',
      'ten_quyen.max' => 'Tên quyền không được vượt quá :max ký tự.',
      'module.required' => 'Module không được để trống.',
      'module.string' => 'Module phải là chuỗi ký tự.',
      'module.max' => 'Module không được vượt quá :max ký tự.',
      'action.required' => 'Hành động không được để trống.',
      'action.string' => 'Hành động phải là chuỗi ký tự.',
      'action.max' => 'Hành động không được vượt quá :max ký tự.',
      'mo_ta.string' => 'Mô tả phải là chuỗi ký tự.',
      'trang_thai.required' => 'Trạng thái không được để trống.',
      'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
    ];
  }
}
