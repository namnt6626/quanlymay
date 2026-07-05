<?php

namespace App\Services\DonHangHoanThanh;

class PhanLoaiParser
{
    public function parse(?string $variation): array
    {
        $original = trim((string) $variation);
        if ($original === '') {
            return ['mau' => null, 'size' => null];
        }

        $parts = array_values(array_filter(array_map('trim', preg_split('/[,;|]+/u', $original) ?: [])));
        $size = null;
        $colorParts = [];

        foreach ($parts as $part) {
            if ($size === null && preg_match('/(?:^|\s|\()SIZE\s*[-:]?\s*(XXXL|3XL|XXL|2XL|XL|L|M|S|XS|F|FREE)(?:\s|\)|$)/iu', $part, $matches)) {
                $size = strtoupper($matches[1]);
                $remaining = trim((string) preg_replace('/(?:^|\s|\()SIZE\s*[-:]?\s*'.preg_quote($matches[1], '/').'(?:\s|\)|$)/iu', ' ', $part));
                if ($remaining !== '') $colorParts[] = $remaining;
                continue;
            }

            if ($size === null && preg_match('/^\s*(XXXL|3XL|XXL|2XL|XL|L|M|S|XS|F|FREE)(?:\s*\([^)]*\))?\s*$/iu', $part, $matches)) {
                $size = strtoupper($matches[1]);
                continue;
            }

            $colorParts[] = $part;
        }

        return [
            'mau' => ($color = trim(implode(', ', $colorParts))) !== '' ? $color : null,
            'size' => $size,
        ];
    }
}
