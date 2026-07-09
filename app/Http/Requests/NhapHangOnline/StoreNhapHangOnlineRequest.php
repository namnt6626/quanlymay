<?php

namespace App\Http\Requests\NhapHangOnline;

use Illuminate\Foundation\Http\FormRequest;

class StoreNhapHangOnlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $details = collect($this->input('chi_tiets', []))->map(function ($row) {
            if (! is_array($row)) return $row;
            $row['so_luong'] = $this->number($row['so_luong'] ?? null);
            $row['don_gia'] = $this->money($row['don_gia'] ?? null);
            return $row;
        })->all();

        $this->merge(['chi_tiets' => $details]);
    }

    public function rules(): array
    {
        return [
            'ngay_nhap' => ['required', 'date'],
            'chi_tiets' => ['required', 'array', 'min:1'],
            'chi_tiets.*.ten_san_pham' => ['required', 'string', 'max:500'],
            'chi_tiets.*.mau' => ['nullable', 'string', 'max:255'],
            'chi_tiets.*.size' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.so_luong' => ['required', 'numeric', 'gt:0'],
            'chi_tiets.*.don_gia' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'chi_tiets.required' => 'Cần có ít nhất một dòng nhập hàng.',
            'chi_tiets.*.ten_san_pham.required' => 'Tên sản phẩm là bắt buộc.',
            'chi_tiets.*.so_luong.gt' => 'Số lượng phải lớn hơn 0.',
            'chi_tiets.*.don_gia.min' => 'Đơn giá không được âm.',
        ];
    }

    private function number(mixed $value): mixed
    {
        $text = preg_replace('/\s+/', '', trim((string) $value));
        if ($text === '') return null;
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $text = strrpos($text, ',') > strrpos($text, '.')
                ? str_replace(',', '.', str_replace('.', '', $text))
                : str_replace(',', '', $text);
        } elseif (str_contains($text, ',')) {
            $text = str_replace(',', '.', $text);
        }
        return preg_replace('/[^\d.\-]/', '', $text);
    }

    private function money(mixed $value): mixed
    {
        $text = preg_replace('/[^\d,.-]/u', '', trim((string) $value));
        if ($text === '') return null;
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $text)) return str_replace([',', '.'], '', $text);
        return $this->number($text);
    }
}
