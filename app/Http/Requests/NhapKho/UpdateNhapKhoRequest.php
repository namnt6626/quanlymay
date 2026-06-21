<?php

namespace App\Http\Requests\NhapKho;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNhapKhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'so_luong_nhap' => $this->normalizeNumberInput($this->input('so_luong_nhap')),
        ]);
    }

    public function rules(): array
    {
        return [
            'qc_id' => [
                'required',
                'integer',
                Rule::exists('qc', 'id')->whereNull('deleted_at'),
            ],
            'ngay_nhap' => ['required', 'date'],
            'so_luong_nhap' => ['required', 'numeric', 'min:0.0001'],
            'ghi_chu' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'qc_id' => 'Nguồn QC',
            'ngay_nhap' => 'Ngày nhập',
            'so_luong_nhap' => 'Số lượng nhập kho',
            'ghi_chu' => 'Ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'qc_id.required' => 'Nguồn QC là bắt buộc.',
            'qc_id.integer' => 'Nguồn QC không hợp lệ.',
            'qc_id.exists' => 'Nguồn QC đã chọn không tồn tại.',
            'ngay_nhap.required' => 'Ngày nhập là bắt buộc.',
            'ngay_nhap.date' => 'Ngày nhập không đúng định dạng ngày.',
            'so_luong_nhap.required' => 'Số lượng nhập kho là bắt buộc.',
            'so_luong_nhap.numeric' => 'Số lượng nhập kho phải là số.',
            'so_luong_nhap.min' => 'Số lượng nhập kho phải lớn hơn 0.',
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

            if (! ($dotCount === 1 && strlen(end($parts)) !== 3)) {
                $value = str_replace('.', '', $value);
            }
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? $value;

        if (substr_count($value, '.') > 1) {
            $segments = explode('.', $value);
            $value = array_shift($segments).'.'.implode('', $segments);
        }

        return $value;
    }
}
