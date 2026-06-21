<?php

namespace App\Http\Requests\Qc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mode = $this->input('qc_mode', 'from_allocation');
        $mode = match ($mode) {
            'phan_bo' => 'from_allocation',
            'nhap_tay' => 'manual',
            default => $mode,
        };

        $allocationGroups = $this->normalizeAllocationGroups((array) $this->input('allocation_groups', []));
        $manualGroups = $this->normalizeManualGroups((array) $this->input('manual_groups', []));

        $this->merge([
            'qc_mode' => $mode,
            'allocation_groups' => $allocationGroups,
            'manual_groups' => $manualGroups,
            'qc_submit_token' => trim((string) $this->input('qc_submit_token')),
        ]);
    }

    public function rules(): array
    {
        $isFromAllocation = $this->input('qc_mode') === 'from_allocation';
        $isManual = $this->input('qc_mode') === 'manual';

        return [
            'qc_mode' => ['required', Rule::in(['from_allocation', 'manual'])],
            'ngay_qc' => ['required', 'date'],
            'ghi_chu' => ['nullable', 'string'],
            'qc_submit_token' => ['nullable', 'string', 'max:100'],

            'allocation_groups' => [$isFromAllocation ? 'required' : 'nullable', 'array'],
            'allocation_groups.*.phan_bo_may_id' => ['nullable', 'integer', Rule::exists('phan_bo_may', 'id')->whereNull('deleted_at')],
            'allocation_groups.*.sl_dat' => ['nullable', 'numeric', 'min:0'],
            'allocation_groups.*.sl_loi' => ['nullable', 'numeric', 'min:0'],
            'allocation_groups.*.sl_hong' => ['nullable', 'numeric', 'min:0'],

            'manual_groups' => [$isManual ? 'required' : 'nullable', 'array'],
            'manual_groups.*.mat_hang_id' => ['nullable', 'integer', Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at')],
            'manual_groups.*.items' => ['nullable', 'array'],
            'manual_groups.*.items.*.mau_id' => ['nullable', 'integer', Rule::exists('dm_mau', 'id')->whereNull('deleted_at')],
            'manual_groups.*.items.*.size_id' => ['nullable', 'integer', Rule::exists('dm_size', 'id')->whereNull('deleted_at')],
            'manual_groups.*.items.*.sl_dat' => ['nullable', 'numeric', 'min:0'],
            'manual_groups.*.items.*.sl_loi' => ['nullable', 'numeric', 'min:0'],
            'manual_groups.*.items.*.sl_hong' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('qc_mode') === 'from_allocation') {
                $rows = collect($this->input('allocation_groups', []))
                    ->filter(fn ($row) => is_array($row))
                    ->filter(fn (array $row): bool => $this->quantityTotal($row) > 0);

                if ($rows->isEmpty()) {
                    $validator->errors()->add('allocation_groups', 'Vui lòng nhập ít nhất một dòng QC.');
                }

                $selectedIds = collect($this->input('allocation_groups', []))
                    ->filter(fn ($row) => is_array($row) && ! empty($row['phan_bo_may_id']))
                    ->map(fn (array $row, int|string $index): array => [
                        'index' => $index,
                        'phan_bo_may_id' => (string) $row['phan_bo_may_id'],
                    ]);
                $duplicateIds = $selectedIds
                    ->pluck('phan_bo_may_id')
                    ->duplicates()
                    ->unique();

                if ($duplicateIds->isNotEmpty()) {
                    $selectedIds
                        ->filter(fn (array $row): bool => $duplicateIds->contains($row['phan_bo_may_id']))
                        ->each(function (array $row) use ($validator): void {
                            $validator->errors()->add(
                                "allocation_groups.{$row['index']}.phan_bo_may_id",
                                'Nguồn QC này đã được chọn trùng trong phiếu QC hiện tại.'
                            );
                        });
                }

                $rows->each(function (array $row, int|string $index) use ($validator): void {
                    if (empty($row['phan_bo_may_id'])) {
                        $lineNumber = ((int) $index) + 1;

                        $validator->errors()->add("allocation_groups.{$index}.phan_bo_may_id", "Dòng {$lineNumber}: Vui lòng chọn nguồn QC.");
                    }
                });

                return;
            }

            $rows = collect($this->input('manual_groups', []))
                ->filter(fn ($group) => is_array($group))
                ->flatMap(function (array $group, int|string $groupIndex): array {
                    return collect($group['items'] ?? [])
                        ->filter(fn ($item) => is_array($item))
                        ->filter(fn (array $item): bool => $this->quantityTotal($item) > 0)
                        ->map(fn (array $item, int|string $itemIndex): array => [
                            'group_index' => $groupIndex,
                            'item_index' => $itemIndex,
                            'mat_hang_id' => $group['mat_hang_id'] ?? null,
                            ...$item,
                        ])
                        ->values()
                        ->all();
                });

            if ($rows->isEmpty()) {
                $validator->errors()->add('manual_groups', 'Vui lòng nhập ít nhất một dòng QC.');
            }

            $rows->each(function (array $row) use ($validator): void {
                $prefix = "manual_groups.{$row['group_index']}.items.{$row['item_index']}";

                if (empty($row['mat_hang_id'])) {
                    $validator->errors()->add("manual_groups.{$row['group_index']}.mat_hang_id", 'Vui lòng chọn mã hàng.');
                }

                if (empty($row['mau_id'])) {
                    $validator->errors()->add("{$prefix}.mau_id", 'Dòng QC thiếu màu.');
                }

                if (empty($row['size_id'])) {
                    $validator->errors()->add("{$prefix}.size_id", 'Dòng QC thiếu size.');
                }
            });
        });
    }

    public function attributes(): array
    {
        return [
            'qc_mode' => 'Kiểu QC',
            'ngay_qc' => 'Ngày QC',
            'ghi_chu' => 'Ghi chú',
            'allocation_groups' => 'Dòng nguồn QC',
            'manual_groups' => 'Dòng QC nhập tay',
        ];
    }

    public function messages(): array
    {
        return [
            'qc_mode.required' => 'Vui lòng chọn kiểu QC.',
            'qc_mode.in' => 'Kiểu QC không hợp lệ.',
            'ngay_qc.required' => 'Ngày QC là bắt buộc.',
            'ngay_qc.date' => 'Ngày QC không đúng định dạng ngày.',
            'allocation_groups.required' => 'Vui lòng chọn ít nhất một nguồn QC.',
            'allocation_groups.*.phan_bo_may_id.integer' => 'Nguồn QC không hợp lệ.',
            'allocation_groups.*.phan_bo_may_id.exists' => 'Nguồn QC đã chọn không tồn tại.',
            'allocation_groups.*.sl_dat.numeric' => 'SL đạt phải là số.',
            'allocation_groups.*.sl_dat.min' => 'SL đạt không được nhỏ hơn :min.',
            'allocation_groups.*.sl_loi.numeric' => 'SL lỗi phải là số.',
            'allocation_groups.*.sl_loi.min' => 'SL lỗi không được nhỏ hơn :min.',
            'allocation_groups.*.sl_hong.numeric' => 'SL hỏng phải là số.',
            'allocation_groups.*.sl_hong.min' => 'SL hỏng không được nhỏ hơn :min.',
            'manual_groups.required' => 'Vui lòng thêm ít nhất một mã hàng.',
            '*.numeric' => ':attribute phải là số.',
            '*.min' => ':attribute không được nhỏ hơn :min.',
            'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
        ];
    }

    private function normalizeAllocationGroups(array $groups): array
    {
        return array_map(function ($group) {
            if (! is_array($group)) {
                return $group;
            }

            return [
                'phan_bo_may_id' => $this->normalizeNullableInteger($group['phan_bo_may_id'] ?? null),
                'sl_dat' => $this->normalizeNumberInput($group['sl_dat'] ?? null),
                'sl_loi' => $this->normalizeNumberInput($group['sl_loi'] ?? null),
                'sl_hong' => $this->normalizeNumberInput($group['sl_hong'] ?? null),
            ];
        }, $groups);
    }

    private function normalizeManualGroups(array $groups): array
    {
        return array_map(function ($group) {
            if (! is_array($group)) {
                return $group;
            }

            $items = array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }

                return [
                    'mau_id' => $this->normalizeNullableInteger($item['mau_id'] ?? null),
                    'size_id' => $this->normalizeNullableInteger($item['size_id'] ?? null),
                    'sl_dat' => $this->normalizeNumberInput($item['sl_dat'] ?? null),
                    'sl_loi' => $this->normalizeNumberInput($item['sl_loi'] ?? null),
                    'sl_hong' => $this->normalizeNumberInput($item['sl_hong'] ?? null),
                ];
            }, (array) ($group['items'] ?? []));

            return [
                'mat_hang_id' => $this->normalizeNullableInteger($group['mat_hang_id'] ?? null),
                'items' => $items,
            ];
        }, $groups);
    }

    private function normalizeNumberInput(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '0';
        }

        $value = preg_replace('/\s+/', '', trim((string) $value)) ?? '';
        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            $decimalSeparator = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } elseif ($commaCount > 0) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($dotCount > 0) {
            $parts = explode('.', $value);

            if (! ($dotCount === 1 && strlen(end($parts)) !== 3)) {
                $value = str_replace('.', '', $value);
            }
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? '0';

        if (substr_count($value, '.') > 1) {
            $segments = explode('.', $value);
            $value = array_shift($segments).'.'.implode('', $segments);
        }

        return $value === '' ? '0' : $value;
    }

    private function normalizeNullableInteger(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function quantityTotal(array $data): float
    {
        return round(
            (float) ($data['sl_dat'] ?? 0)
            + (float) ($data['sl_loi'] ?? 0)
            + (float) ($data['sl_hong'] ?? 0),
            4
        );
    }
}
