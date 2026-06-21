<?php

namespace App\Http\Requests\DonViCat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonViCatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_don_vi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dm_don_vi_cat', 'ma_don_vi')->whereNull('deleted_at'),
            ],
            'ten_don_vi' => ['required', 'string', 'max:255'],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_don_vi' => 'Mã đơn vị',
            'ten_don_vi' => 'Tên đơn vị',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_don_vi.required' => 'Mã đơn vị là bắt buộc.',
            'ma_don_vi.string' => 'Mã đơn vị phải là chuỗi ký tự.',
            'ma_don_vi.max' => 'Mã đơn vị không được vượt quá :max ký tự.',
            'ma_don_vi.unique' => 'Mã đơn vị đã tồn tại trong hệ thống.',
            'ten_don_vi.required' => 'Tên đơn vị là bắt buộc.',
            'ten_don_vi.string' => 'Tên đơn vị phải là chuỗi ký tự.',
            'ten_don_vi.max' => 'Tên đơn vị không được vượt quá :max ký tự.',
            'trang_thai.required' => 'Trạng thái là bắt buộc.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
