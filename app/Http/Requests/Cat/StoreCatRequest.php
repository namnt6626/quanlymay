<?php

namespace App\Http\Requests\Cat;

use App\Models\Cat;
use App\Models\DonHangChiTiet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $chiTiets = $this->input('chi_tiets', []);
        $items = $this->input('items', []);

        if (is_array($chiTiets)) {
            $chiTiets = array_map(function ($chiTiet) {
                if (! is_array($chiTiet)) {
                    return $chiTiet;
                }

                $chiTiet['don_hang_chi_tiet_id'] = $this->normalizeNullableInteger($chiTiet['don_hang_chi_tiet_id'] ?? null);
                $chiTiet['so_luong_cat'] = $this->normalizeNumberInput($chiTiet['so_luong_cat'] ?? null);

                return $chiTiet;
            }, $chiTiets);
        }

        if (is_array($items)) {
            $items = array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }

                $item['mau_id'] = $this->normalizeNullableInteger($item['mau_id'] ?? null);
                $item['size_id'] = $this->normalizeNullableInteger($item['size_id'] ?? null);
                $item['so_luong_cat'] = $this->normalizeNumberInput($item['so_luong_cat'] ?? null);
                $item['vai_tieu_hao'] = $this->normalizeNumberInput($item['vai_tieu_hao'] ?? null);

                return $item;
            }, $items);
        }

        $this->merge([
            'don_hang_id' => $this->normalizeNullableInteger($this->input('don_hang_id')),
            'don_hang_chi_tiet_id' => $this->normalizeNullableInteger($this->input('don_hang_chi_tiet_id')),
            'so_luong_cat' => $this->normalizeNumberInput($this->input('so_luong_cat')),
            'dinh_muc' => $this->normalizeNumberInput($this->input('dinh_muc')),
            'ban_cat_ten' => trim((string) $this->input('ban_cat_ten')),
            'chi_tiets' => $chiTiets,
            'items' => $items,
            'cat_submit_token' => trim((string) $this->input('cat_submit_token')),
        ]);
    }

    public function rules(): array
    {
        $donHangId = $this->input('don_hang_id');
        $donHangChiTietId = $this->input('don_hang_chi_tiet_id');
        $hasFixedItems = ! $donHangId && is_array($this->input('items')) && count($this->input('items')) > 0;

        return [
            'ngay_cat' => ['required', 'date'],
            'don_hang_id' => ['nullable', 'integer', Rule::exists('don_hangs', 'id')->whereNull('deleted_at')],
            'don_hang_chi_tiet_id' => ['nullable', 'integer', Rule::exists('don_hang_chi_tiets', 'id')->whereNull('deleted_at')],
            'mat_hang_id' => [
                ($donHangId || $donHangChiTietId) ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at'),
            ],
            'mau_id' => [
                ($donHangId || $donHangChiTietId || $hasFixedItems) ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_mau', 'id')->whereNull('deleted_at'),
            ],
            'size_id' => [
                ($donHangId || $donHangChiTietId || $hasFixedItems) ? 'nullable' : 'required',
                'integer',
                Rule::exists('dm_size', 'id')->whereNull('deleted_at'),
            ],
            'ban_cat_ten' => ['required', 'string', 'max:255'],
            'don_vi_cat_id' => [
                'required',
                'integer',
                Rule::exists('dm_don_vi_cat', 'id')->whereNull('deleted_at'),
            ],
            'so_luong_cat' => [($donHangId || $hasFixedItems) ? 'nullable' : 'required', 'numeric', 'min:0'],
            'dinh_muc' => ['required', 'numeric', 'min:0'],
            'ghi_chu' => ['nullable', 'string'],
            'cat_submit_token' => ['nullable', 'string', 'max:100'],
            'chi_tiets' => [$donHangId ? 'required' : 'nullable', 'array'],
            'chi_tiets.*.don_hang_chi_tiet_id' => [$donHangId ? 'required' : 'nullable', 'integer', Rule::exists('don_hang_chi_tiets', 'id')->whereNull('deleted_at')],
            'chi_tiets.*.so_luong_cat' => ['nullable', 'numeric', 'min:0'],
            'items' => [$donHangId ? 'nullable' : 'required', 'array'],
            'items.*.mau_id' => [
                'nullable',
                'integer',
                Rule::exists('dm_mau', 'id')->where(fn ($query) => $query->whereNull('deleted_at')->where('trang_thai', true)),
            ],
            'items.*.size_id' => [
                'nullable',
                'integer',
                Rule::exists('dm_size', 'id')->where(fn ($query) => $query->whereNull('deleted_at')->where('trang_thai', true)),
            ],
            'items.*.so_luong_cat' => ['nullable', 'numeric', 'min:0'],
            'items.*.vai_tieu_hao' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $donHangId = $this->input('don_hang_id');

            if (! $donHangId) {
                $items = collect($this->input('items', []))
                    ->filter(fn ($item) => is_array($item))
                    ->filter(fn (array $item) => (float) ($item['so_luong_cat'] ?? 0) > 0);

                if ($items->isEmpty()) {
                    $validator->errors()->add('items', 'Vui lòng nhập ít nhất một dòng cắt.');

                    return;
                }

                $missingRequiredValue = $items->contains(fn (array $item) => empty($item['mau_id']) || empty($item['size_id']));

                if ($missingRequiredValue) {
                    $validator->errors()->add('items', 'Vui lòng chọn đầy đủ màu và size cho các dòng có số lượng cắt.');

                    return;
                }

                return;
            }

            $chiTiets = collect($this->input('chi_tiets', []))
                ->filter(fn ($chiTiet) => is_array($chiTiet))
                ->filter(fn (array $chiTiet) => (float) ($chiTiet['so_luong_cat'] ?? 0) > 0);

            if ($chiTiets->isEmpty()) {
                $validator->errors()->add('chi_tiets', 'Không có dòng nào cần cắt.');

                return;
            }

            $donHangChiTiets = DonHangChiTiet::query()
                ->where('don_hang_id', $donHangId)
                ->whereIn('id', $chiTiets->pluck('don_hang_chi_tiet_id')->filter()->all())
                ->get()
                ->keyBy('id');

            if ($donHangChiTiets->count() !== $chiTiets->count()) {
                $validator->errors()->add('chi_tiets', 'Chi tiết đơn hàng không hợp lệ.');

                return;
            }

            $cutTotals = Cat::query()
                ->whereNull('deleted_at')
                ->whereIn('don_hang_chi_tiet_id', $donHangChiTiets->keys())
                ->select('don_hang_chi_tiet_id', DB::raw('COALESCE(SUM(so_luong_cat), 0) as total_cut'))
                ->groupBy('don_hang_chi_tiet_id')
                ->pluck('total_cut', 'don_hang_chi_tiet_id');

            $hasCuttableRow = false;

            $chiTiets->each(function (array $chiTietData) use ($donHangChiTiets, $cutTotals, &$hasCuttableRow): void {
                $chiTiet = $donHangChiTiets->get((int) $chiTietData['don_hang_chi_tiet_id']);

                if (! $chiTiet) {
                    return;
                }

                $soLuongCat = (float) ($chiTietData['so_luong_cat'] ?? 0);
                $daCat = (float) ($cutTotals[$chiTiet->id] ?? 0);
                $canCat = (float) $chiTiet->so_luong_dat - $daCat;

                if ($soLuongCat <= 0 || $canCat <= 0) {
                    return;
                }

                $hasCuttableRow = true;
            });

            if (! $hasCuttableRow) {
                $validator->errors()->add('chi_tiets', 'Không có dòng nào cần cắt.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'ngay_cat' => 'Ngày cắt',
            'don_hang_id' => 'Mã đơn hàng',
            'don_hang_chi_tiet_id' => 'Dòng đơn hàng',
            'mat_hang_id' => 'Mặt hàng',
            'mau_id' => 'Màu sắc',
            'size_id' => 'Size',
            'ban_cat_ten' => 'Bàn cắt',
            'don_vi_cat_id' => 'Đơn vị cắt',
            'so_luong_cat' => 'Số lượng cắt',
            'dinh_muc' => 'Định mức',
            'ghi_chu' => 'Ghi chú',
            'chi_tiets' => 'Chi tiết đơn hàng',
            'chi_tiets.*.don_hang_chi_tiet_id' => 'Dòng chi tiết đơn hàng',
            'chi_tiets.*.so_luong_cat' => 'Số lượng cắt',
            'items' => 'Chi tiết cắt',
            'items.*.mau_id' => 'Màu sắc',
            'items.*.size_id' => 'Size',
            'items.*.so_luong_cat' => 'Số lượng cắt',
            'items.*.vai_tieu_hao' => 'Vải tiêu hao',
        ];
    }

    public function messages(): array
    {
        return [
            'ngay_cat.required' => 'Ngày cắt là bắt buộc.',
            'ngay_cat.date' => 'Ngày cắt không đúng định dạng ngày.',
            'don_hang_id.integer' => 'Mã đơn hàng không hợp lệ.',
            'don_hang_id.exists' => 'Mã đơn hàng đã chọn không tồn tại.',
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
            'chi_tiets.required' => 'Không có dòng nào cần cắt.',
            'chi_tiets.array' => 'Chi tiết đơn hàng không hợp lệ.',
            'chi_tiets.*.don_hang_chi_tiet_id.required' => 'Dòng chi tiết đơn hàng là bắt buộc.',
            'chi_tiets.*.don_hang_chi_tiet_id.integer' => 'Dòng chi tiết đơn hàng không hợp lệ.',
            'chi_tiets.*.don_hang_chi_tiet_id.exists' => 'Dòng chi tiết đơn hàng đã chọn không tồn tại.',
            'chi_tiets.*.so_luong_cat.numeric' => 'Số lượng cắt phải là số.',
            'chi_tiets.*.so_luong_cat.min' => 'Số lượng cắt phải lớn hơn hoặc bằng :min.',
            'items.required' => 'Vui lòng nhập ít nhất một dòng cắt.',
            'items.array' => 'Chi tiết cắt không hợp lệ.',
            'items.*.mau_id.integer' => 'Màu sắc không hợp lệ.',
            'items.*.mau_id.exists' => 'Màu sắc đã chọn không tồn tại.',
            'items.*.size_id.integer' => 'Size không hợp lệ.',
            'items.*.size_id.exists' => 'Size đã chọn không tồn tại.',
            'items.*.so_luong_cat.numeric' => 'Số lượng cắt phải là số.',
            'items.*.so_luong_cat.min' => 'Số lượng cắt phải lớn hơn hoặc bằng :min.',
            'items.*.vai_tieu_hao.numeric' => 'Vải tiêu hao phải là số.',
            'items.*.vai_tieu_hao.min' => 'Vải tiêu hao phải lớn hơn hoặc bằng :min.',
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
            $value = array_shift($segments).'.'.implode('', $segments);
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
