<?php

namespace App\Http\Requests\MatHang;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatHangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_hang' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dm_mat_hang', 'ma_hang')->whereNull('deleted_at'),
            ],
            'ten_hang' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_hang' => 'Mã hàng',
            'ten_hang' => 'Tên hàng',
            'mo_ta' => 'Mô tả',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_hang.required' => 'Mã hàng là bắt buộc.',
            'ma_hang.string' => 'Mã hàng phải là chuỗi ký tự.',
            'ma_hang.max' => 'Mã hàng không được vượt quá :max ký tự.',
            'ma_hang.unique' => 'Mã hàng đã tồn tại trong hệ thống.',
            'ten_hang.required' => 'Tên hàng là bắt buộc.',
            'ten_hang.string' => 'Tên hàng phải là chuỗi ký tự.',
            'ten_hang.max' => 'Tên hàng không được vượt quá :max ký tự.',
            'mo_ta.string' => 'Mô tả phải là chuỗi ký tự.',
            'trang_thai.required' => 'Trạng thái là bắt buộc.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
