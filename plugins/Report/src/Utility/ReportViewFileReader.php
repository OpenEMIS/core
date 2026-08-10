<?php

namespace Report\Utility;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ReportViewFileReader
{
    public const ROW_PAGE_SIZE = 50;

    /**
     * @return array<int, array{index: int, title: string, totalRows: int}>
     */
    public static function getMultiSectionMeta(string $xlsxPath, ?string $csvPath = null): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $worksheetInfo = $reader->listWorksheetInfo($xlsxPath);
        $sections = [];

        foreach ($worksheetInfo as $index => $info) {
            $totalRows = max(0, (int)($info['totalRows'] ?? 0) - 1);
            $sections[] = [
                'index' => $index,
                'title' => (string)($info['worksheetName'] ?? (string)($index + 1)),
                'totalRows' => $totalRows,
            ];
        }

        if (count($sections) > 1 && $csvPath !== null && is_readable($csvPath)) {
            $csvCounts = self::countCsvSectionRows($csvPath);
            foreach ($sections as $index => &$section) {
                if (isset($csvCounts[$index])) {
                    $section['totalRows'] = $csvCounts[$index];
                }
            }
            unset($section);
        }

        return $sections;
    }

    /**
     * @return array{title: string, headers: array, rows: array<int, array<string, mixed>>, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    public static function readSectionPage(
        string $xlsxPath,
        ?string $csvPath,
        string $module,
        int $sectionIndex,
        string $sectionTitle,
        int $page
    ): array {
        $page = max(1, $page);
        $pageSize = self::ROW_PAGE_SIZE;

        if ($csvPath !== null && is_readable($csvPath) && self::csvHasMultipleSections($csvPath)) {
            return self::readCsvSectionPage($csvPath, $sectionIndex, $sectionTitle, $page, $pageSize);
        }

        return self::readXlsxSectionPage($xlsxPath, $module, $sectionIndex, $sectionTitle, $page, $pageSize);
    }

    /**
     * @return array{title: string, headers: array, rows: array<int, array<string, mixed>>, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    public static function readSingleSheetPage(string $path, string $module, int $page, bool $preferCsv = true): array
    {
        $page = max(1, $page);
        $pageSize = self::ROW_PAGE_SIZE;
        $csvPath = preg_replace('/\.xlsx$/i', '.csv', $path);

        if ($preferCsv && is_readable($csvPath)) {
            return self::readCsvSingleSheetPage($csvPath, $page, $pageSize);
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $worksheetInfo = $reader->listWorksheetInfo($path);
        $totalRows = max(0, (int)($worksheetInfo[0]['totalRows'] ?? 0) - 1);
        $worksheetNames = $reader->listWorksheetNames($path);
        $title = (string)($worksheetNames[0] ?? '');

        return self::readXlsxSectionPage($path, $module, 0, $title, $page, $pageSize, $totalRows);
    }

    /**
     * @return array<int, int>
     */
    private static function countCsvSectionRows(string $csvPath): array
    {
        $counts = [];
        $sectionIndex = -1;
        $referenceHeader = null;
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return $counts;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (self::isEmptyCsvRow($row)) {
                continue;
            }

            if (self::isCsvSectionHeaderRow($row, $referenceHeader)) {
                $sectionIndex++;
                $referenceHeader = $row;
                $counts[$sectionIndex] = 0;
                continue;
            }

            if ($sectionIndex < 0 || $referenceHeader === null || count($row) !== count($referenceHeader)) {
                continue;
            }

            $counts[$sectionIndex]++;
        }

        fclose($handle);

        return $counts;
    }

    private static function csvHasMultipleSections(string $csvPath): bool
    {
        $headerCount = 0;
        $referenceHeader = null;
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return false;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (self::isEmptyCsvRow($row)) {
                continue;
            }

            if (self::isCsvSectionHeaderRow($row, $referenceHeader)) {
                $headerCount++;
                $referenceHeader = $row;
                if ($headerCount > 1) {
                    fclose($handle);

                    return true;
                }
            }
        }

        fclose($handle);

        return false;
    }

    /**
     * @return array{title: string, headers: array, rows: array<int, array<string, mixed>>, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    private static function readCsvSectionPage(
        string $csvPath,
        int $sectionIndex,
        string $sectionTitle,
        int $page,
        int $pageSize
    ): array {
        $currentSection = -1;
        $headers = [];
        $referenceHeader = null;
        $rows = [];
        $totalRows = 0;
        $start = ($page - 1) * $pageSize;
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return self::emptyPageResult($sectionTitle, $page, $pageSize);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (self::isEmptyCsvRow($row)) {
                continue;
            }

            if (self::isCsvSectionHeaderRow($row, $referenceHeader)) {
                $currentSection++;
                $headers = $row;
                $referenceHeader = $row;
                if ($currentSection > $sectionIndex) {
                    break;
                }
                continue;
            }

            if ($currentSection !== $sectionIndex || count($row) !== count($headers)) {
                continue;
            }

            if ($totalRows >= $start && count($rows) < $pageSize) {
                $rows[] = array_combine($headers, $row);
            }
            $totalRows++;
        }

        fclose($handle);

        return self::buildPageResult($sectionTitle, $headers, $rows, $totalRows, $page, $pageSize);
    }

    /**
     * @return array{title: string, headers: array, rows: array<int, array<string, mixed>>, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    private static function readCsvSingleSheetPage(string $csvPath, int $page, int $pageSize): array
    {
        $headers = [];
        $rows = [];
        $totalRows = 0;
        $start = ($page - 1) * $pageSize;
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return self::emptyPageResult('', $page, $pageSize);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === []) {
                if (self::isEmptyCsvRow($row)) {
                    continue;
                }
                $headers = $row;
                continue;
            }

            if (self::isEmptyCsvRow($row) || count($row) !== count($headers)) {
                continue;
            }

            if ($totalRows >= $start && count($rows) < $pageSize) {
                $rows[] = array_combine($headers, $row);
            }
            $totalRows++;
        }

        fclose($handle);

        return self::buildPageResult('', $headers, $rows, $totalRows, $page, $pageSize);
    }

    /**
     * @return array{title: string, headers: array, rows: array<int, array<string, mixed>>, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    private static function readXlsxSectionPage(
        string $path,
        string $module,
        int $sectionIndex,
        string $sectionTitle,
        int $page,
        int $pageSize,
        ?int $totalRows = null
    ): array {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $worksheetNames = $reader->listWorksheetNames($path);
        $worksheetTitle = $worksheetNames[$sectionIndex] ?? $worksheetNames[0] ?? '';

        if ($totalRows === null) {
            $worksheetInfo = $reader->listWorksheetInfo($path);
            $totalRows = max(0, (int)($worksheetInfo[$sectionIndex]['totalRows'] ?? 0) - 1);
        }

        $startRow = 2 + (($page - 1) * $pageSize);
        $endRow = min($startRow + $pageSize - 1, $totalRows);
        $reader->setLoadSheetsOnly([$worksheetTitle]);
        $reader->setReadFilter(new ReportViewReadFilter($startRow, $endRow));

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $headers = [];

        if ($highestColumn !== '') {
            $headerMatrix = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false);
            if (!empty($headerMatrix[0])) {
                $headers = $headerMatrix[0];
            }
        }

        $rows = [];
        if ($highestColumn !== '' && $headers !== [] && $endRow >= $startRow) {
            for ($row = $startRow; $row <= $endRow; $row++) {
                $currentRow = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);
                $line = reset($currentRow);
                if ($line === false || self::isEmptyCsvRow($line)) {
                    continue;
                }
                if (count($line) !== count($headers)) {
                    continue;
                }
                $rows[] = array_combine($headers, $line);
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);

        if ($module === 'InstitutionStatistics' && $totalRows > 0) {
            $totalRows++;
        }

        return self::buildPageResult($sectionTitle ?: $worksheetTitle, $headers, $rows, $totalRows, $page, $pageSize);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, mixed>> $rows
     * @return array{title: string, headers: array, rows: array, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    private static function buildPageResult(
        string $title,
        array $headers,
        array $rows,
        int $totalRows,
        int $page,
        int $pageSize
    ): array {
        $totalPages = $pageSize > 0 ? (int)max(1, ceil($totalRows / $pageSize)) : 1;

        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'totalRows' => $totalRows,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * @return array{title: string, headers: array, rows: array, totalRows: int, page: int, pageSize: int, totalPages: int}
     */
    private static function emptyPageResult(string $title, int $page, int $pageSize): array
    {
        return self::buildPageResult($title, [], [], 0, $page, $pageSize);
    }

    /**
     * @param array<int, mixed> $row
     * @param array<int, mixed>|null $referenceHeader
     */
    private static function isCsvSectionHeaderRow(array $row, ?array $referenceHeader): bool
    {
        if ($referenceHeader === null) {
            return true;
        }

        $first = isset($row[0]) ? trim((string)$row[0], " \t\n\r\0\x0B\"") : '';
        $headerFirst = isset($referenceHeader[0]) ? trim((string)$referenceHeader[0], " \t\n\r\0\x0B\"") : '';

        return $first !== '' && $headerFirst !== '' && $first === $headerFirst;
    }

    /**
     * @param array<int, mixed> $row
     */
    private static function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && $cell !== '') {
                return false;
            }
        }

        return true;
    }
}
