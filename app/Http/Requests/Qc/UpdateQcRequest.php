<?php

namespace App\Http\Requests\Qc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $soLuongDat = $this->normalizeNumberInput($this->input('so_luong_dat'));
        $soLuongLoi = $this->normalizeNumberInput($this->input('so_luong_loi'));
        $soLuongHong = $this->normalizeNumberInput($this->input('so_luong_hong'));

        $this->merge([
            'so_luong_dat' => $soLuongDat,
            'so_luong_loi' => $soLuongLoi,
            'so_luong_hong' => $soLuongHong,
            'so_luong_qc' => round((float) $soLuongDat + (float) $soLuongLoi + (float) $soLuongHong, 4),
        ]);
    }

    public function rules(): array
    {
        return [
            'phan_bo_may_id' => [
                'nullable',
                'integer',
                Rule::exists('phan_bo_may', 'id')->whereNull('deleted_at'),
            ],
            'don_hang_chi_tiet_id' => [
                'nullable',
                'integer',
                Rule::exists('don_hang_chi_tiets', 'id')->whereNull('deleted_at'),
            ],
            'ngay_qc' => ['required', 'date'],
            'so_luong_qc' => ['required', 'numeric', 'min:0'],
            'so_luong_dat' => ['required', 'numeric', 'min:0'],
            'so_luong_loi' => ['required', 'numeric', 'min:0'],
            'so_luong_hong' => ['required', 'numeric', 'min:0'],
            'ghi_chu' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $qc = $this->route('qc');

            if ($qc?->phan_bo_may_id !== null && empty($this->input('phan_bo_may_id'))) {
                $validator->errors()->add('phan_bo_may_id', 'Phân bổ may là bắt buộc.');
            }

            $soLuongDat = (float) $this->input('so_luong_dat');
            $soLuongLoi = (float) $this->input('so_luong_loi');
            $soLuongHong = (float) $this->input('so_luong_hong');

            $calculatedTotal = round($soLuongDat + $soLuongLoi + $soLuongHong, 4);

            if ($calculatedTotal <= 0) {
                $validator->errors()->add('so_luong_qc', 'Tổng QC phải lớn hơn 0.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'phan_bo_may_id' => 'Phân bổ may',
            'don_hang_chi_tiet_id' => 'Dòng đơn hàng',
            'ngay_qc' => 'Ngày QC',
            'so_luong_qc' => 'Tổng QC',
            'so_luong_dat' => 'Số lượng đạt',
            'so_luong_loi' => 'Số lượng lỗi',
            'so_luong_hong' => 'Số lượng hỏng',
            'ghi_chu' => 'Ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'phan_bo_may_id.integer' => 'Phân bổ may không hợp lệ.',
            'phan_bo_may_id.exists' => 'Phân bổ may đã chọn không tồn tại.',
            'don_hang_chi_tiet_id.integer' => 'Dòng đơn hàng không hợp lệ.',
            'don_hang_chi_tiet_id.exists' => 'Dòng đơn hàng đã chọn không tồn tại.',
            'ngay_qc.required' => 'Ngày QC là bắt buộc.',
            'ngay_qc.date' => 'Ngày QC không đúng định dạng ngày.',
            'so_luong_qc.required' => 'Tổng QC là bắt buộc.',
            'so_luong_qc.numeric' => 'Tổng QC phải là số.',
            'so_luong_qc.min' => 'Tổng QC không được nhỏ hơn :min.',
            'so_luong_dat.required' => 'Số lượng đạt là bắt buộc.',
            'so_luong_dat.numeric' => 'Số lượng đạt phải là số.',
            'so_luong_dat.min' => 'Số lượng đạt phải lớn hơn hoặc bằng :min.',
            'so_luong_loi.required' => 'Số lượng lỗi là bắt buộc.',
            'so_luong_loi.numeric' => 'Số lượng lỗi phải là số.',
            'so_luong_loi.min' => 'Số lượng lỗi phải lớn hơn hoặc bằng :min.',
            'so_luong_hong.required' => 'Số lượng hỏng là bắt buộc.',
            'so_luong_hong.numeric' => 'Số lượng hỏng phải là số.',
            'so_luong_hong.min' => 'Số lượng hỏng phải lớn hơn hoặc bằng :min.',
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
