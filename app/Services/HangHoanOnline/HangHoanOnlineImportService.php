<?php

namespace App\Services\HangHoanOnline;

use App\Models\HangHoanOnlineChiTiet;
use App\Services\ProfitAnalysis\SimpleXlsxReader;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class HangHoanOnlineImportService
{
    public function __construct(private readonly SimpleXlsxReader $reader) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(string $path): array
    {
        $rows = [];
        $columns = [];
        $required = ['Return Order ID', 'Order ID', 'Seller SKU', 'Product Name', 'SKU Name', 'Return Type', 'Time Requested', 'Return Quantity', 'Return Status'];

        $this->reader->eachRow($path, function (array $row, int $rowNumber) use (&$rows, &$columns, $required): void {
            if ($rowNumber === 1) {
                $columns = $this->mapHeaderColumns($row);
                foreach ($required as $column) {
                    if (! isset($columns[$column])) {
                        throw new RuntimeException('File hàng hoàn thiếu cột '.$column.'.');
                    }
                }

                return;
            }

            if ($rowNumber <= 1) {
                return;
            }

            $quantity = $this->number($row[$columns['Return Quantity']] ?? 0);
            if ($quantity <= 0) {
                return;
            }

            [$color, $size] = $this->parseSkuName((string) ($row[$columns['SKU Name']] ?? ''));
            $detail = [
                'return_order_id' => $this->text($row[$columns['Return Order ID']] ?? null),
                'order_id' => $this->text($row[$columns['Order ID']] ?? null),
                'sku_id' => $this->text($row[$columns['SKU ID'] ?? 0] ?? null),
                'seller_sku' => $this->text($row[$columns['Seller SKU']] ?? null),
                'ten_san_pham' => $this->text($row[$columns['Product Name']] ?? null),
                'mau' => $color,
                'size' => $size,
                'sku_name' => $this->text($row[$columns['SKU Name']] ?? null),
                'so_luong_hoan' => $quantity,
                'return_type' => $this->text($row[$columns['Return Type']] ?? null),
                'return_status' => $this->text($row[$columns['Return Status']] ?? null),
                'tinh_trang_hang' => 'ban_lai_duoc',
                'time_requested' => $this->parseDate($row[$columns['Time Requested']] ?? null)?->toDateTimeString(),
                'refund_time' => $this->parseDate($row[$columns['Refund Time'] ?? 0] ?? null)?->toDateTimeString(),
                'return_reason' => $this->text($row[$columns['Return Reason'] ?? 0] ?? null),
                'tracking_id' => $this->text($row[$columns['Return Logistics Tracking ID'] ?? 0] ?? null),
                'compensation_status' => $this->text($row[$columns['Compensation Status'] ?? 0] ?? null),
                'compensation_amount' => $this->number($row[$columns['Compensation Amount'] ?? 0] ?? 0),
                'buyer_note' => $this->text($row[$columns['Buyer Note'] ?? 0] ?? null),
            ];
            $detail['cong_ton'] = $this->shouldCountStock($detail);
            $detail['dedupe_key'] = $this->dedupeKey($detail);
            $rows[] = $detail;
        });

        if ($rows === []) {
            throw new RuntimeException('File hàng hoàn chưa có dòng hợp lệ.');
        }

        $existingKeys = HangHoanOnlineChiTiet::query()
            ->whereIn('dedupe_key', collect($rows)->pluck('dedupe_key')->unique()->values())
            ->pluck('dedupe_key')
            ->flip();
        $seenKeys = [];
        $displayRows = [];
        $duplicateExistingCount = 0;
        $duplicateInFileCount = 0;

        foreach ($rows as $row) {
            $key = $row['dedupe_key'];
            if (isset($existingKeys[$key])) {
                $duplicateExistingCount++;

                continue;
            }

            if (isset($seenKeys[$key])) {
                $duplicateInFileCount++;

                continue;
            }

            $seenKeys[$key] = true;
            $displayRows[] = $row;
        }

        $allRows = collect($rows);
        $newRows = collect($displayRows);
        $dates = $allRows
            ->map(fn (array $row): ?string => $row['refund_time'] ? Carbon::parse($row['refund_time'])->toDateString() : ($row['time_requested'] ? Carbon::parse($row['time_requested'])->toDateString() : null))
            ->filter()
            ->values();

        return [
            'rows' => $rows,
            'display_rows' => $displayRows,
            'summary' => [
                'row_count' => count($rows),
                'new_row_count' => count($displayRows),
                'duplicate_row_count' => $duplicateExistingCount + $duplicateInFileCount,
                'duplicate_existing_count' => $duplicateExistingCount,
                'duplicate_in_file_count' => $duplicateInFileCount,
                'return_quantity' => (float) $allRows->sum('so_luong_hoan'),
                'new_return_quantity' => (float) $newRows->sum('so_luong_hoan'),
                'stock_quantity' => (float) $newRows->where('cong_ton', true)->sum('so_luong_hoan'),
                'stock_row_count' => $newRows->where('cong_ton', true)->count(),
                'from_date' => $dates->isNotEmpty() ? $dates->min() : null,
                'to_date' => $dates->isNotEmpty() ? $dates->max() : null,
                'status_counts' => $allRows->countBy('return_status')->all(),
                'type_counts' => $allRows->countBy('return_type')->all(),
            ],
        ];
    }

    public function shouldCountStock(array $row): bool
    {
        return Str::lower(trim((string) ($row['return_status'] ?? ''))) === 'completed'
            && Str::lower(trim((string) ($row['return_type'] ?? ''))) === 'return and refund'
            && ($row['tinh_trang_hang'] ?? 'ban_lai_duoc') === 'ban_lai_duoc'
            && (float) ($row['so_luong_hoan'] ?? 0) > 0;
    }

    public function dedupeKey(array $row): string
    {
        $parts = [
            trim((string) ($row['return_order_id'] ?? '')),
            trim((string) ($row['order_id'] ?? '')),
            trim((string) ($row['sku_id'] ?? '')),
            trim((string) ($row['seller_sku'] ?? '')),
        ];

        return sha1(implode('|', $parts));
    }

    /**
     * @param array<int, string> $header
     * @return array<string, int>
     */
    private function mapHeaderColumns(array $header): array
    {
        $columns = [];
        foreach ($header as $column => $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $columns[$label] = $column;
            }
        }

        return $columns;
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @return array{0:?string, 1:?string}
     */
    private function parseSkuName(string $value): array
    {
        $parts = array_map('trim', explode(',', $value, 2));

        return [
            $parts[0] !== '' ? $parts[0] : null,
            ($parts[1] ?? '') !== '' ? $parts[1] : null,
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y H:i:s', 'Y/m/d H:i:s', 'd/m/Y', 'Y/m/d', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }

    private function number(mixed $value): float
    {
        $text = preg_replace('/[^\d,.\-]/u', '', trim((string) $value)) ?? '';
        if ($text === '' || $text === '-') {
            return 0.0;
        }
        if (preg_match('/^\-?\d{1,3}(?:[.,]\d{3})+$/', $text)) {
            return (float) str_replace([',', '.'], '', $text);
        }
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $text = strrpos($text, ',') > strrpos($text, '.')
                ? str_replace(',', '.', str_replace('.', '', $text))
                : str_replace(',', '', $text);
        } elseif (str_contains($text, ',')) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : 0.0;
    }
}
