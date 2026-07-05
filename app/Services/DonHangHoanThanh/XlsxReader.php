<?php

namespace App\Services\DonHangHoanThanh;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxReader
{
    public function rows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Không thể mở file Excel.');

        try {
            $shared = $this->sharedStrings($zip);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($xml === false) throw new RuntimeException('File Excel không có sheet dữ liệu hợp lệ.');
            $sheet = simplexml_load_string($xml);
            if (! $sheet) throw new RuntimeException('Không thể đọc nội dung sheet Excel.');

            $result = [];
            foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                $values = [];
                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $column = preg_replace('/\d+/', '', (string) $cell['r']);
                    $valueNodes = $cell->xpath('./*[local-name()="v"]');
                    $value = (string) ($valueNodes[0] ?? '');
                    if ((string) $cell['t'] === 's') $value = $shared[(int) $value] ?? '';
                    if ((string) $cell['t'] === 'inlineStr') {
                        $texts = $cell->xpath('.//*[local-name()="t"]') ?: [];
                        $value = implode('', array_map(fn ($text) => (string) $text, $texts));
                    }
                    $values[$column] = trim($value);
                }
                $result[] = ['row' => (int) $row['r'], 'values' => $values];
            }
            return $result;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $document = simplexml_load_string($xml);
        if (! $document) return [];
        $strings = [];
        foreach ($document->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $strings[] = implode('', array_map(fn (SimpleXMLElement $text) => (string) $text, $item->xpath('.//*[local-name()="t"]') ?: []));
        }
        return $strings;
    }
}
