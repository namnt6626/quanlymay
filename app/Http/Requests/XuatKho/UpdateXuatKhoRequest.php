<?php

namespace App\Http\Requests\XuatKho;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateXuatKhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'so_luong_xuat' => $this->normalizeNumberInput($this->input('so_luong_xuat')),
        ]);
    }

    public function rules(): array
    {
        $phieuXuatKho = $this->route('xuat_kho');

        return [
            'so_phieu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('phieu_xuat_kho', 'so_phieu')
                    ->ignore($phieuXuatKho?->id)
                    ->whereNull('deleted_at'),
            ],
            'ngay_xuat' => ['required', 'date'],
            'kenh_ban' => ['required', 'string', 'max:255'],
            'nhap_kho_id' => [
                'required',
                'integer',
                Rule::exists('nhap_kho', 'id')->whereNull('deleted_at'),
            ],
            'so_luong_xuat' => ['required', 'numeric', 'min:0.0001'],
            'ghi_chu' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'so_phieu' => 'Số phiếu',
            'ngay_xuat' => 'Ngày xuất',
            'kenh_ban' => 'Kênh bán',
            'nhap_kho_id' => 'Nguồn nhập kho',
            'so_luong_xuat' => 'Số lượng xuất',
            'ghi_chu' => 'Ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'so_phieu.required' => 'Số phiếu là bắt buộc.',
            'so_phieu.string' => 'Số phiếu phải là chuỗi ký tự.',
            'so_phieu.max' => 'Số phiếu không được vượt quá :max ký tự.',
            'so_phieu.unique' => 'Số phiếu đã tồn tại trong hệ thống.',
            'ngay_xuat.required' => 'Ngày xuất là bắt buộc.',
            'ngay_xuat.date' => 'Ngày xuất không đúng định dạng ngày.',
            'kenh_ban.required' => 'Kênh bán là bắt buộc.',
            'kenh_ban.string' => 'Kênh bán phải là chuỗi ký tự.',
            'kenh_ban.max' => 'Kênh bán không được vượt quá :max ký tự.',
            'nhap_kho_id.required' => 'Nguồn nhập kho là bắt buộc.',
            'nhap_kho_id.integer' => 'Nguồn nhập kho không hợp lệ.',
            'nhap_kho_id.exists' => 'Nguồn nhập kho đã chọn không tồn tại.',
            'so_luong_xuat.required' => 'Số lượng xuất là bắt buộc.',
            'so_luong_xuat.numeric' => 'Số lượng xuất phải là số.',
            'so_luong_xuat.min' => 'Số lượng xuất phải lớn hơn 0.',
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
