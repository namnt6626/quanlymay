<?php

namespace App\Http\Requests\Mau;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_mau' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dm_mau', 'ma_mau')->whereNull('deleted_at'),
            ],
            'ten_mau' => ['required', 'string', 'max:255'],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_mau' => 'Mã màu',
            'ten_mau' => 'Tên màu',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_mau.required' => 'Mã màu là bắt buộc.',
            'ma_mau.string' => 'Mã màu phải là chuỗi ký tự.',
            'ma_mau.max' => 'Mã màu không được vượt quá :max ký tự.',
            'ma_mau.unique' => 'Mã màu đã tồn tại trong hệ thống.',
            'ten_mau.required' => 'Tên màu là bắt buộc.',
            'ten_mau.string' => 'Tên màu phải là chuỗi ký tự.',
            'ten_mau.max' => 'Tên màu không được vượt quá :max ký tự.',
            'trang_thai.required' => 'Trạng thái là bắt buộc.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
