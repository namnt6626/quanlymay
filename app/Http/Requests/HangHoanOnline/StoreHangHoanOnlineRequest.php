<?php

namespace App\Http\Requests\HangHoanOnline;

use Illuminate\Foundation\Http\FormRequest;

class StoreHangHoanOnlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $details = collect($this->input('chi_tiets', []))->map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }

            $row['so_luong_hoan'] = $this->number($row['so_luong_hoan'] ?? null);
            $row['compensation_amount'] = $this->money($row['compensation_amount'] ?? null);

            return $row;
        })->all();

        $this->merge(['chi_tiets' => $details]);
    }

    public function rules(): array
    {
        return [
            'ngay_hoan' => ['required', 'date'],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
            'chi_tiets' => ['required', 'array', 'min:1'],
            'chi_tiets.*.return_order_id' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.order_id' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.sku_id' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.seller_sku' => ['nullable', 'string', 'max:255'],
            'chi_tiets.*.ten_san_pham' => ['required', 'string', 'max:500'],
            'chi_tiets.*.mau' => ['nullable', 'string', 'max:255'],
            'chi_tiets.*.size' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.sku_name' => ['nullable', 'string', 'max:500'],
            'chi_tiets.*.so_luong_hoan' => ['required', 'numeric', 'gt:0'],
            'chi_tiets.*.return_type' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.return_status' => ['required', 'string', 'max:100'],
            'chi_tiets.*.tinh_trang_hang' => ['required', 'in:ban_lai_duoc,loi_hong,cho_kiem'],
            'chi_tiets.*.time_requested' => ['nullable', 'date'],
            'chi_tiets.*.refund_time' => ['nullable', 'date'],
            'chi_tiets.*.return_reason' => ['nullable', 'string', 'max:500'],
            'chi_tiets.*.tracking_id' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.compensation_status' => ['nullable', 'string', 'max:100'],
            'chi_tiets.*.compensation_amount' => ['nullable', 'numeric', 'min:0'],
            'chi_tiets.*.buyer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'chi_tiets.required' => 'Cần có ít nhất một dòng hàng hoàn.',
            'chi_tiets.*.ten_san_pham.required' => 'Tên sản phẩm là bắt buộc.',
            'chi_tiets.*.so_luong_hoan.gt' => 'Số lượng hoàn phải lớn hơn 0.',
            'chi_tiets.*.return_status.required' => 'Trạng thái hoàn là bắt buộc.',
            'chi_tiets.*.tinh_trang_hang.in' => 'Tình trạng hàng không hợp lệ.',
        ];
    }

    private function number(mixed $value): mixed
    {
        $text = preg_replace('/\s+/', '', trim((string) $value));
        if ($text === '') {
            return null;
        }
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
        if ($text === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $text)) {
            return str_replace([',', '.'], '', $text);
        }

        return $this->number($text);
    }
}
