<?php

namespace App\Http\Requests\Size;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_size' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dm_size', 'ma_size')->whereNull('deleted_at'),
            ],
            'ten_size' => ['required', 'string', 'max:255'],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_size' => 'Mã size',
            'ten_size' => 'Tên size',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_size.required' => 'Mã size là bắt buộc.',
            'ma_size.string' => 'Mã size phải là chuỗi ký tự.',
            'ma_size.max' => 'Mã size không được vượt quá :max ký tự.',
            'ma_size.unique' => 'Mã size đã tồn tại trong hệ thống.',
            'ten_size.required' => 'Tên size là bắt buộc.',
            'ten_size.string' => 'Tên size phải là chuỗi ký tự.',
            'ten_size.max' => 'Tên size không được vượt quá :max ký tự.',
            'trang_thai.required' => 'Trạng thái là bắt buộc.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
