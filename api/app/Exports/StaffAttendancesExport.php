<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffAttendancesExport
{
    private const OPENEMIS_ID_COLUMN_INDEX = 0;

    private array $exportData;

    public function __construct(array $exportData)
    {
        $this->exportData = $exportData;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $sheets = $this->exportData['sheets'] ?? [];
        if (empty($sheets)) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('No Data');
            $sheet->setCellValue('A1', 'No staff attendance data available for export.');
            return $spreadsheet;
        }

        foreach ($sheets as $index => $sheetData) {
            $sheet = $spreadsheet->createSheet($index);
            $this->buildMonthSheet($sheet, $sheetData);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buildMonthSheet(Worksheet $sheet, array $sheetData): void
    {
        $sheetName = $this->sanitizeSheetTitle($sheetData['name'] ?? 'Sheet');
        $sheet->setTitle($sheetName);

        $headers = $sheetData['headers'] ?? [];
        $rows = $sheetData['rows'] ?? [];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($row as $colIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($colIndex + 1);
                $cell = $column . $rowNumber;

                if ($colIndex === self::OPENEMIS_ID_COLUMN_INDEX && $value !== null && $value !== '') {
                    $sheet->setCellValue($cell, is_numeric($value) ? $value + 0 : $value);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }
            $rowNumber++;
        }

        if (!empty($rows)) {
            $lastDataRow = $rowNumber - 1;
            $sheet->getStyle('A2:A' . $lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('0');
        }

        $this->applyColumnWidths($sheet, $headers, $rows);

        $footerRow = $rowNumber + 1;
        $sheet->setCellValue('A' . $footerRow, 'Report Generated: ' . date('Y-m-d H:i:s'));
    }

    /**
     * Set explicit column widths so OpenEMIS ID and day headers display in full.
     *
     * @param array<int,string> $headers
     * @param array<int,array<int,mixed>> $rows
     */
    private function applyColumnWidths(Worksheet $sheet, array $headers, array $rows): void
    {
        $columnCount = count($headers);

        for ($colIndex = 0; $colIndex < $columnCount; $colIndex++) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $maxLength = mb_strlen((string) ($headers[$colIndex] ?? ''));

            foreach ($rows as $row) {
                if (!isset($row[$colIndex])) {
                    continue;
                }
                $displayValue = $row[$colIndex];
                if ($colIndex === self::OPENEMIS_ID_COLUMN_INDEX && is_numeric($displayValue)) {
                    $displayValue = (string) (int) $displayValue;
                }
                $cellLength = mb_strlen((string) $displayValue);
                if ($cellLength > $maxLength) {
                    $maxLength = $cellLength;
                }
            }

            $width = min(50, max(12, $maxLength + 2));
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $title) ?? 'Sheet';
        return mb_substr(trim($title), 0, 31);
    }
}
