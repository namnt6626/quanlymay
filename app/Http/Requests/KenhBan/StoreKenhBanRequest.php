<?php

namespace App\Http\Requests\KenhBan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKenhBanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_kenh' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dm_kenh_ban', 'ma_kenh')->whereNull('deleted_at'),
            ],
            'ten_kenh' => [
                'required',
                'string',
                'max:150',
                Rule::unique('dm_kenh_ban', 'ten_kenh')->whereNull('deleted_at'),
            ],
            'trang_thai' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ma_kenh' => 'Mã kênh',
            'ten_kenh' => 'Tên kênh',
            'trang_thai' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'ma_kenh.required' => 'Mã kênh là bắt buộc.',
            'ma_kenh.unique' => 'Mã kênh đã tồn tại trong hệ thống.',
            'ten_kenh.required' => 'Tên kênh là bắt buộc.',
            'ten_kenh.unique' => 'Tên kênh đã tồn tại trong hệ thống.',
            'trang_thai.required' => 'Trạng thái là bắt buộc.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }
}
