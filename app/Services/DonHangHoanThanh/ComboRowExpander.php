<?php

namespace App\Services\DonHangHoanThanh;

class ComboRowExpander
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $colors
     * @return array<int, array<string, mixed>>
     */
    public function expand(array $row, array $colors): array
    {
        if ($colors === []) {
            return [$row];
        }

        $amounts = $this->allocateAmount($row['thanh_tien'] ?? null, count($colors));

        $comboCount = count($colors);
        $expandedRows = array_map(function (string $color, int $index) use ($row, $amounts, $comboCount): array {
            $row['mau'] = $color;
            $row['thanh_tien'] = $amounts[$index];
            $row['combo_count'] = $comboCount;

            return $row;
        }, $colors, array_keys($colors));

        $mergedRows = [];
        foreach ($expandedRows as $expandedRow) {
            $key = mb_strtolower(trim((string) $expandedRow['mau']));

            if (! isset($mergedRows[$key])) {
                $mergedRows[$key] = $expandedRow;

                continue;
            }

            $mergedRows[$key]['so_luong'] = (float) $mergedRows[$key]['so_luong'] + (float) $expandedRow['so_luong'];
            if (is_numeric($mergedRows[$key]['thanh_tien']) && is_numeric($expandedRow['thanh_tien'])) {
                $mergedRows[$key]['thanh_tien'] = (float) $mergedRows[$key]['thanh_tien'] + (float) $expandedRow['thanh_tien'];
            }
        }

        return array_values($mergedRows);
    }

    /**
     * @return array<int, mixed>
     */
    private function allocateAmount(mixed $amount, int $parts): array
    {
        if (! is_numeric($amount)) {
            return array_fill(0, $parts, $amount);
        }

        $totalCents = (int) round((float) $amount * 100);
        $baseCents = intdiv($totalCents, $parts);
        $remainder = $totalCents % $parts;

        return array_map(
            fn (int $index): float => ($baseCents + ($index < $remainder ? 1 : 0)) / 100,
            range(0, $parts - 1)
        );
    }
}
