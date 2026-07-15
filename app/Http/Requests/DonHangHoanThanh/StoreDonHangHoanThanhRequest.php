<?php

namespace App\Http\Requests\DonHangHoanThanh;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonHangHoanThanhRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $details = collect($this->input('chi_tiets', []))->map(function ($row) {
            if (! is_array($row)) return $row;
            $row['so_luong'] = $this->number($row['so_luong'] ?? null);
            $row['thanh_tien'] = $this->money($row['thanh_tien'] ?? null);
            return $row;
        })->all();
        $this->merge([
            'chi_tiets' => $details,
            'kenh_ban' => $this->input('kenh_ban') ?: 'Tiktok',
            'ten_kho' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'ngay_hoan_thanh' => ['required', 'date'],
            'ten_san_pham' => ['required', 'string', 'max:500'],
            'ten_kho' => ['nullable'],
            'kenh_ban' => ['required', 'in:Tiktok,Shopee,Bán sỉ'],
            'ghi_chu' => ['nullable', 'string'],
            'chi_tiets' => ['required', 'array', 'min:1'],
            'chi_tiets.*.mau' => ['nullable', 'string', 'max:255'],
            'chi_tiets.*.size' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.so_luong' => ['required', 'numeric', 'gt:0'],
            'chi_tiets.*.thanh_tien' => ['required', 'numeric', 'min:0'],
            'chi_tiets.*.nguon' => ['nullable', 'in:excel,thu_cong'],
        ];
    }

    public function messages(): array
    {
        return [
            'chi_tiets.required' => 'Cần có ít nhất một dòng màu/size.',
            'chi_tiets.*.so_luong.gt' => 'Số lượng phải lớn hơn 0.',
            'chi_tiets.*.thanh_tien.min' => 'Thành tiền không được âm.',
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
