<?php

namespace App\Services\ProfitAnalysis;

use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

class SimpleXlsxReader
{
    /**
     * @var array<int, resource>
     */
    private array $temporaryStreams = [];

    /**
     * @return array<int, array<int, string>>
     */
    public function rows(string $path, ?string $sheetName = null): array
    {
        $grouped = [];
        $this->eachRow($path, function (array $row, int $rowIndex) use (&$grouped): void {
            $grouped[$rowIndex] = $row;
        }, $sheetName);

        return $grouped;
    }

    public function eachRow(string $path, callable $callback, ?string $sheetName = null): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không thể mở file Excel.');
        }

        try {
            $sheetPath = $this->sheetPath($zip, $sheetName);
            $shared = $this->sharedStrings($zip);
            $reader = $this->sheetXmlReader($zip, $sheetPath);
            $currentRowIndex = 0;
            $currentRow = [];

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
                    continue;
                }

                [$columnIndex, $rowIndex] = $this->cellPosition((string) $reader->getAttribute('r'));
                if ($columnIndex === 0 || $rowIndex === 0) {
                    continue;
                }

                if ($currentRowIndex !== 0 && $rowIndex !== $currentRowIndex) {
                    $callback($currentRow, $currentRowIndex);
                    $currentRow = [];
                }

                $currentRowIndex = $rowIndex;
                $currentRow[$columnIndex] = trim($this->streamCellValue($reader, (string) $reader->getAttribute('t'), $shared));
            }
            $reader->close();

            if ($currentRowIndex !== 0) {
                $callback($currentRow, $currentRowIndex);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<string>
     */
    public function sheetNames(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không thể mở file Excel.');
        }

        try {
            return array_keys($this->sheets($zip));
        } finally {
            $zip->close();
        }
    }

    private function sheetPath(ZipArchive $zip, ?string $sheetName): string
    {
        $sheets = $this->sheets($zip);
        if ($sheets === []) {
            throw new RuntimeException('File Excel không có sheet.');
        }

        if ($sheetName !== null) {
            foreach ($sheets as $name => $path) {
                if (mb_strtolower($name) === mb_strtolower($sheetName)) {
                    return $path;
                }
            }
        }

        return reset($sheets);
    }

    /**
     * @return array<string, string>
     */
    private function sheets(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('File Excel thiếu workbook metadata.');
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if (! $workbook || ! $rels) {
            throw new RuntimeException('Không đọc được workbook metadata.');
        }

        $targets = [];
        foreach ($rels->xpath('//*[local-name()="Relationship"]') ?: [] as $rel) {
            $targets[(string) $rel['Id']] = $this->normalizeTarget((string) $rel['Target']);
        }

        $sheets = [];
        foreach ($workbook->xpath('//*[local-name()="sheets"]/*[local-name()="sheet"]') ?: [] as $sheet) {
            $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) ($attributes['id'] ?? '');
            $name = (string) $sheet['name'];
            if ($name !== '' && isset($targets[$relationshipId])) {
                $sheets[$name] = $targets[$relationshipId];
            }
        }

        return $sheets;
    }

    private function normalizeTarget(string $target): string
    {
        $target = ltrim($target, '/');

        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
    }

    private function sheetXmlReader(ZipArchive $zip, string $sheetPath): XMLReader
    {
        $source = $zip->getStream($sheetPath);
        if ($source === false) {
            throw new RuntimeException('File Excel không có sheet dữ liệu hợp lệ.');
        }

        $temporary = tmpfile();
        if ($temporary === false) {
            throw new RuntimeException('Không tạo được file tạm để đọc Excel.');
        }

        stream_copy_to_stream($source, $temporary);
        fclose($source);

        $metadata = stream_get_meta_data($temporary);
        $reader = new XMLReader();
        if (! $reader->open($metadata['uri'])) {
            throw new RuntimeException('Không thể đọc nội dung sheet Excel.');
        }

        $this->temporaryStreams[] = $temporary;

        return $reader;
    }

    /**
     * @return array<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if (! $document) {
            return [];
        }

        $strings = [];
        foreach ($document->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $strings[] = implode('', array_map(
                fn (SimpleXMLElement $text): string => (string) $text,
                $item->xpath('.//*[local-name()="t"]') ?: []
            ));
        }

        return $strings;
    }

    /**
     * @param array<string> $shared
     */
    private function cellValue(SimpleXMLElement $cell, array $shared): string
    {
        if ((string) $cell['t'] === 'inlineStr') {
            return implode('', array_map(
                fn (SimpleXMLElement $text): string => (string) $text,
                $cell->xpath('.//*[local-name()="t"]') ?: []
            ));
        }

        $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
        $value = (string) ($valueNodes[0] ?? '');

        if ((string) $cell['t'] === 's') {
            return $shared[(int) $value] ?? '';
        }

        return $value;
    }

    /**
     * @param array<string> $shared
     */
    private function streamCellValue(XMLReader $reader, string $type, array $shared): string
    {
        $value = '';
        $cellDepth = $reader->depth;

        if ($reader->isEmptyElement) {
            return '';
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'v') {
                $value = $reader->readString();
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't' && $type === 'inlineStr') {
                $value .= $reader->readString();
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c' && $reader->depth === $cellDepth) {
                break;
            }
        }

        if ($type === 's') {
            return $shared[(int) $value] ?? '';
        }

        return $value;
    }

    /**
     * @return array{0:int, 1:int}
     */
    private function cellPosition(string $cellReference): array
    {
        if (! preg_match('/^([A-Z]+)(\d+)$/', $cellReference, $matches)) {
            return [0, 0];
        }

        $columnIndex = 0;
        foreach (str_split($matches[1]) as $letter) {
            $columnIndex = $columnIndex * 26 + (ord($letter) - 64);
        }

        return [$columnIndex, (int) $matches[2]];
    }
}
