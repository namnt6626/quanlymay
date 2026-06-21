<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'ma_vai_tro' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'ma_vai_tro')
                    ->ignore($role?->id)
                    ->whereNull('deleted_at'),
            ],
            'ten_vai_tro' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_vai_tro' => 'Mã vai trò',
            'ten_vai_tro' => 'Tên vai trò',
            'mo_ta' => 'Mô tả',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_vai_tro.required' => 'Mã vai trò không được để trống.',
            'ma_vai_tro.string' => 'Mã vai trò phải là chuỗi ký tự.',
            'ma_vai_tro.max' => 'Mã vai trò không được vượt quá :max ký tự.',
            'ma_vai_tro.unique' => 'Mã vai trò đã tồn tại.',
            'ten_vai_tro.required' => 'Tên vai trò không được để trống.',
            'ten_vai_tro.string' => 'Tên vai trò phải là chuỗi ký tự.',
            'ten_vai_tro.max' => 'Tên vai trò không được vượt quá :max ký tự.',
            'mo_ta.string' => 'Mô tả phải là chuỗi ký tự.',
            'trang_thai.required' => 'Trạng thái không được để trống.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
