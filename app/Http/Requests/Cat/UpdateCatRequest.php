<?php

namespace App\Http\Requests\Cat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'don_hang_chi_tiet_id' => $this->normalizeNullableInteger($this->input('don_hang_chi_tiet_id')),
            'so_luong_cat' => $this->normalizeNumberInput($this->input('so_luong_cat')),
            'dinh_muc' => $this->normalizeNumberInput($this->input('dinh_muc')),
            'ban_cat_ten' => trim((string) $this->input('ban_cat_ten')),
        ]);
    }

    public function rules(): array
    {
        $donHangChiTietId = $this->input('don_hang_chi_tiet_id');

        return [
            'ngay_cat' => ['required', 'date'],
            'don_hang_chi_tiet_id' => ['nullable', 'integer', Rule::exists('don_hang_chi_tiets', 'id')->whereNull('deleted_at')],
            'mat_hang_id' => [
                $donHangChiTietId ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at'),
            ],
            'mau_id' => [
                $donHangChiTietId ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_mau', 'id')->whereNull('deleted_at'),
            ],
            'size_id' => [
                $donHangChiTietId ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_size', 'id')->whereNull('deleted_at'),
            ],
            'ban_cat_ten' => ['required', 'string', 'max:255'],
            'don_vi_cat_id' => [
                'required',
                'integer',
                Rule::exists('dm_don_vi_cat', 'id')->whereNull('deleted_at'),
            ],
            'so_luong_cat' => ['required', 'numeric', 'min:0'],
            'dinh_muc' => ['required', 'numeric', 'min:0'],
            'ghi_chu' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ngay_cat' => 'Ngày cắt',
            'don_hang_chi_tiet_id' => 'Dòng đơn hàng',
            'mat_hang_id' => 'Mặt hàng',
            'mau_id' => 'Màu sắc',
            'size_id' => 'Size',
            'ban_cat_ten' => 'Bàn cắt',
            'don_vi_cat_id' => 'Đơn vị cắt',
            'so_luong_cat' => 'Số lượng cắt',
            'dinh_muc' => 'Định mức',
            'ghi_chu' => 'Ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'ngay_cat.required' => 'Ngày cắt là bắt buộc.',
            'ngay_cat.date' => 'Ngày cắt không đúng định dạng ngày.',
            'don_hang_chi_tiet_id.integer' => 'Dòng đơn hàng không hợp lệ.',
            'don_hang_chi_tiet_id.exists' => 'Dòng đơn hàng đã chọn không tồn tại.',
            'mat_hang_id.required' => 'Mặt hàng là bắt buộc.',
            'mat_hang_id.integer' => 'Mặt hàng không hợp lệ.',
            'mat_hang_id.exists' => 'Mặt hàng đã chọn không tồn tại.',
            'mau_id.required' => 'Màu sắc là bắt buộc.',
            'mau_id.integer' => 'Màu sắc không hợp lệ.',
            'mau_id.exists' => 'Màu sắc đã chọn không tồn tại.',
            'size_id.required' => 'Size là bắt buộc.',
            'size_id.integer' => 'Size không hợp lệ.',
            'size_id.exists' => 'Size đã chọn không tồn tại.',
            'ban_cat_ten.required' => 'Bàn cắt là bắt buộc.',
            'ban_cat_ten.string' => 'Bàn cắt phải là chuỗi ký tự.',
            'ban_cat_ten.max' => 'Bàn cắt không được vượt quá :max ký tự.',
            'don_vi_cat_id.required' => 'Đơn vị cắt là bắt buộc.',
            'don_vi_cat_id.integer' => 'Đơn vị cắt không hợp lệ.',
            'don_vi_cat_id.exists' => 'Đơn vị cắt đã chọn không tồn tại.',
            'so_luong_cat.required' => 'Số lượng cắt là bắt buộc.',
            'so_luong_cat.numeric' => 'Số lượng cắt phải là số.',
            'so_luong_cat.min' => 'Số lượng cắt phải lớn hơn hoặc bằng :min.',
            'dinh_muc.required' => 'Định mức là bắt buộc.',
            'dinh_muc.numeric' => 'Định mức phải là số.',
            'dinh_muc.min' => 'Định mức phải lớn hơn hoặc bằng :min.',
            'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
        ];
    }

    private function normalizeNumberInput(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value) ?? $value;

        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            $decimalSeparator = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';

            $value = str_replace($thousandSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } elseif ($commaCount > 0) {
            $parts = explode(',', $value);

            if ($commaCount === 1 && strlen(end($parts)) !== 3) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($dotCount > 0) {
            $parts = explode('.', $value);

            if ($dotCount === 1 && strlen(end($parts)) !== 3) {
                // keep as decimal separator
            } else {
                $value = str_replace('.', '', $value);
            }
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? $value;

        if (substr_count($value, '.') > 1) {
            $segments = explode('.', $value);
            $value = array_shift($segments) . '.' . implode('', $segments);
        }

        return $value;
    }

    private function normalizeNullableInteger(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
