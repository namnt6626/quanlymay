<?php

namespace App\Http\Requests\DonHangHoanThanh;

use Illuminate\Foundation\Http\FormRequest;

class CommitImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('rows_payload');

        if (is_string($payload) && trim($payload) !== '') {
            $rows = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($rows)) {
                foreach ($rows as &$row) {
                    if (is_array($row) && ($row['thanh_tien'] ?? '') === '') {
                        $row['thanh_tien'] = 0;
                    }
                }
                unset($row);

                $this->merge(['rows' => $rows]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:5000'],
            'rows.*.dong_excel' => ['required', 'integer', 'min:1'],
            'rows.*.ngay_hoan_thanh' => ['required', 'date'],
            'rows.*.thoi_gian_tao_goc' => ['nullable', 'date'],
            'rows.*.ten_san_pham' => ['required', 'string', 'max:500'],
            'rows.*.kenh_ban' => ['required', 'in:Tiktok,Shopee'],
            'rows.*.phan_loai_goc' => ['nullable', 'string', 'max:500'],
            'rows.*.mau' => ['nullable', 'string', 'max:255'],
            'rows.*.size' => ['nullable', 'string', 'max:100'],
            'rows.*.so_luong' => ['required', 'numeric', 'gt:0'],
            'rows.*.thanh_tien' => ['required', 'numeric', 'min:0'],
        ];
    }
}
