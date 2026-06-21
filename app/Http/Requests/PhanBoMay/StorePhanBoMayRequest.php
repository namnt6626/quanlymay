<?php

namespace App\Http\Requests\PhanBoMay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePhanBoMayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mode = $this->input('mode', $this->input('allocation_mode', 'by_product'));

        $mode = match ($mode) {
            'theo_ma_hang' => 'by_product',
            'theo_size' => 'by_size',
            default => $mode,
        };

        $allocations = $this->input('items', $this->input('allocations', []));

        if (is_array($allocations)) {
            $allocations = array_map(function ($allocation) {
                if (! is_array($allocation)) {
                    return $allocation;
                }

                $allocation['don_hang_chi_tiet_id'] = $this->normalizeNullableInteger($allocation['don_hang_chi_tiet_id'] ?? null);
                $allocation['mat_hang_id'] = $this->normalizeNullableInteger($allocation['mat_hang_id'] ?? null);
                $allocation['mau_id'] = $this->normalizeNullableInteger($allocation['mau_id'] ?? null);
                $allocation['size_id'] = $this->normalizeNullableInteger($allocation['size_id'] ?? null);
                $allocation['so_luong_giao'] = $this->normalizeNumberInput($allocation['so_luong_giao'] ?? null);

                return $allocation;
            }, $allocations);
        }

        $this->merge([
            'mode' => $mode,
            'allocation_mode' => $mode,
            'ngay_giao' => $this->input('ngay_giao', $this->input('ngay_phan_bo')),
            'ngay_phan_bo' => $this->input('ngay_phan_bo', $this->input('ngay_giao')),
            'so_luong_giao' => $this->normalizeNumberInput($this->input('so_luong_giao')),
            'don_hang_id' => $this->normalizeNullableInteger($this->input('don_hang_id')),
            'mat_hang_id' => $this->normalizeNullableInteger($this->input('mat_hang_id')),
            'size_ids' => array_values(array_filter((array) $this->input('size_ids', []), fn ($value) => trim((string) $value) !== '')),
            'allocations' => $allocations,
            'items' => $allocations,
            'phan_bo_may_submit_token' => trim((string) $this->input('phan_bo_may_submit_token')),
        ]);
    }

    public function rules(): array
    {
        $hasAllocations = is_array($this->input('allocations')) && count($this->input('allocations')) > 0;
        $isGroupedForm = $this->has('allocation_mode') || $this->has('mat_hang_id');
        $isBySizeMode = $this->input('allocation_mode') === 'by_size';

        return [
            'mode' => ['nullable', Rule::in(['by_product', 'by_size'])],
            'allocation_mode' => ['nullable', Rule::in(['by_product', 'by_size'])],
            'don_hang_id' => ['nullable', 'integer', Rule::exists('don_hangs', 'id')->whereNull('deleted_at')],
            'mat_hang_id' => [$isGroupedForm ? 'required' : 'nullable', 'integer', Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at')],
            'size_ids' => [$isGroupedForm && $isBySizeMode ? 'required' : 'nullable', 'array'],
            'size_ids.*' => ['integer', Rule::exists('dm_size', 'id')->whereNull('deleted_at')],
            'allocations' => [$isGroupedForm ? 'required' : 'nullable', 'array'],
            'allocations.*.group_key' => ['nullable', 'string'],
            'allocations.*.don_hang_chi_tiet_id' => ['nullable', 'integer', Rule::exists('don_hang_chi_tiets', 'id')->whereNull('deleted_at')],
            'allocations.*.mat_hang_id' => ['nullable', 'integer', Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at')],
            'allocations.*.mau_id' => ['nullable', 'integer', Rule::exists('dm_mau', 'id')->whereNull('deleted_at')],
            'allocations.*.size_id' => ['nullable', 'integer', Rule::exists('dm_size', 'id')->whereNull('deleted_at')],
            'allocations.*.so_luong_giao' => ['nullable', 'numeric', 'min:0'],
            'cat_id' => [
                $isGroupedForm ? 'nullable' : 'required',
                'integer',
                Rule::exists('cat', 'id')->whereNull('deleted_at'),
            ],
            'ngay_phan_bo' => ['required', 'date'],
            'don_vi_may_id' => [
                'required',
                'integer',
                Rule::exists('dm_don_vi_may', 'id')->whereNull('deleted_at'),
            ],
            'so_luong_giao' => [$isGroupedForm ? 'nullable' : 'required', 'numeric', 'gt:0'],
            'ghi_chu' => ['nullable', 'string'],
            'phan_bo_may_submit_token' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isGroupedForm = $this->has('allocation_mode') || $this->has('mat_hang_id');

            if (! $isGroupedForm) {
                return;
            }

            $positiveRows = collect($this->input('allocations', []))
                ->filter(fn ($allocation) => is_array($allocation))
                ->filter(fn (array $allocation) => (float) ($allocation['so_luong_giao'] ?? 0) > 0);

            if ($positiveRows->isEmpty()) {
                $validator->errors()->add('allocations', 'Vui lòng nhập ít nhất một số lượng giao may.');

                return;
            }

            $positiveRows->each(function (array $allocation, int $index) use ($validator): void {
                if (empty($allocation['mat_hang_id'])) {
                    $validator->errors()->add("allocations.{$index}.mat_hang_id", 'Dòng phân bổ thiếu mã hàng.');
                }

                if (empty($allocation['mau_id'])) {
                    $validator->errors()->add("allocations.{$index}.mau_id", 'Dòng phân bổ thiếu màu.');
                }

                if (empty($allocation['size_id'])) {
                    $validator->errors()->add("allocations.{$index}.size_id", 'Dòng phân bổ thiếu size.');
                }
            });
        });
    }

    public function attributes(): array
    {
        return [
            'allocation_mode' => 'Kiểu phân bổ',
            'mode' => 'Kiểu phân bổ',
            'don_hang_id' => 'Mã đơn',
            'mat_hang_id' => 'Mã hàng',
            'size_ids' => 'Size',
            'allocations' => 'Dòng phân bổ',
            'cat_id' => 'Phiếu cắt',
            'ngay_phan_bo' => 'Ngày phân bổ',
            'don_vi_may_id' => 'Đơn vị may',
            'so_luong_giao' => 'Số lượng giao',
            'ghi_chu' => 'Ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'mat_hang_id.required' => 'Mã hàng là bắt buộc.',
            'size_ids.required' => 'Vui lòng chọn ít nhất một size.',
            'allocations.required' => 'Không tìm thấy dữ liệu cắt còn lại để phân bổ.',
            'allocations.array' => 'Dòng phân bổ không hợp lệ.',
            'allocations.*.so_luong_giao.numeric' => 'Số lượng giao phải là số.',
            'allocations.*.so_luong_giao.min' => 'Số lượng giao phải lớn hơn hoặc bằng :min.',
            'cat_id.required' => 'Phiếu cắt là bắt buộc.',
            'cat_id.integer' => 'Phiếu cắt không hợp lệ.',
            'cat_id.exists' => 'Phiếu cắt đã chọn không tồn tại.',
            'ngay_phan_bo.required' => 'Ngày phân bổ là bắt buộc.',
            'ngay_phan_bo.date' => 'Ngày phân bổ không đúng định dạng ngày.',
            'don_vi_may_id.required' => 'Đơn vị may là bắt buộc.',
            'don_vi_may_id.integer' => 'Đơn vị may không hợp lệ.',
            'don_vi_may_id.exists' => 'Đơn vị may đã chọn không tồn tại.',
            'so_luong_giao.required' => 'Số lượng giao là bắt buộc.',
            'so_luong_giao.numeric' => 'Số lượng giao phải là số.',
            'so_luong_giao.gt' => 'Số lượng giao phải lớn hơn :value.',
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

    private function normalizeNullableInteger(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
