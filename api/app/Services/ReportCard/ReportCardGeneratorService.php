<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ports ExcelReportBehavior (rendering engine) to Laravel.
 *
 * CakePHP Hash helpers are replaced with private hashGet / hashExtract /
 * hashCombine / hashFlatten helpers that support the {n} wildcard.
 */
class ReportCardGeneratorService
{
    private ReportCardVariableService $variableService;

    // State carried per-generate call (mirrors ExcelReportBehavior private state)
    private int   $currentWorksheetIndex = 0;
    private ?Worksheet $currentWorksheet = null;
    private int   $lastRow    = 0;
    private string $lastColumn = 'A';

    private array $advancedTypes = [
        'row'      => 'repeatRows',
        'column'   => 'repeatColumns',
        'table'    => 'table',
        'match'    => 'match',
        'dropdown' => 'dropdown',
        'image'    => 'image',
    ];

    public function __construct(ReportCardVariableService $variableService)
    {
        $this->variableService = $variableService;
    }

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Full report-card generation pipeline for one student.
     * $params array keys: report_card_id, institution_class_id, student_id,
     *                      institution_id, education_grade_id, academic_period_id
     */
    public function generate(array $params): void
    {
        $this->currentWorksheetIndex = 0;
        $this->currentWorksheet      = null;
        $this->lastRow    = 0;
        $this->lastColumn = 'A';

        // 1. Load binary template from report_cards table
        $reportCard = DB::table('report_cards')->where('id', $params['report_card_id'])->first();
        if (!$reportCard || empty($reportCard->excel_template)) {
            throw new \RuntimeException("No excel_template for report_card_id={$params['report_card_id']}");
        }

        $exportDir = storage_path('app/export/customexcel');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        // Write template to temp file
        $tmpTemplate = tempnam($exportDir, 'rc_template_') . '.xlsx';
        file_put_contents($tmpTemplate, $reportCard->excel_template);

        // 2. Fix image transparency (PhpSpreadsheet 2.x supports getAlpha())
        Artisan::call('reportcards:fix-transparency', ['filePath' => $tmpTemplate]);

        // 3. Load spreadsheet
        $spreadsheet = $this->loadTemplate($tmpTemplate);

        // 4. Get all 73 variable values
        $vars = $this->variableService->getAll($params);

        // 5. Generate (fill placeholders)
        $extra = ['vars' => $vars, 'params' => $params];
        $this->generateExcel($spreadsheet, $extra);

        // 6. Save XLSX
        $tmpOutput = tempnam($exportDir, 'rc_output_');
        $this->saveExcel($spreadsheet, $tmpOutput);

        // 7. Save PDF (base64-encoded, stored as text)
        $pdfContent = $this->savePdf($spreadsheet, $tmpOutput, $params);

        // 8. Update DB records
        $this->updateReportCardRecord($params, $tmpOutput, $pdfContent);

        // Cleanup
        @unlink($tmpTemplate);
        @unlink($tmpOutput);

        $spreadsheet->disconnectWorksheets();
        gc_collect_cycles();
    }

    // -------------------------------------------------------------------------
    // Template loading
    // -------------------------------------------------------------------------

    public function loadTemplate(string $filePath): Spreadsheet
    {
        $inputFileType = IOFactory::identify($filePath);
        $reader        = IOFactory::createReader($inputFileType);
        return $reader->load($filePath);
    }

    // -------------------------------------------------------------------------
    // Excel generation (processWorksheet chain)
    // -------------------------------------------------------------------------

    public function generateExcel(Spreadsheet $spreadsheet, array &$extra): void
    {
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $this->processWorksheet($spreadsheet, $worksheet, $extra);
        }
        $spreadsheet->setActiveSheetIndex(0);
    }

    private function processWorksheet(Spreadsheet $spreadsheet, Worksheet $worksheet, array &$extra): void
    {
        if ($this->currentWorksheet !== $worksheet) {
            $this->currentWorksheetIndex++;
            $this->currentWorksheet = $worksheet;
        }

        $extra['placeholders'] = [];
        $this->processBasicPlaceholder($spreadsheet, $worksheet, $extra);

        if (!empty($extra['placeholders'])) {
            $this->processAdvancedPlaceholder($spreadsheet, $worksheet, $extra);
        }
    }

    private function processBasicPlaceholder(Spreadsheet $spreadsheet, Worksheet $worksheet, array &$extra): void
    {
        $cellCollection = $worksheet->getCellCollection();
        $cells          = $cellCollection->getCoordinates();

        foreach ($cells as $cellCoordinate) {
            $objCell  = $worksheet->getCell($cellCoordinate);
            $rawValue = $objCell->getValue();
            $cellValue = is_object($rawValue) && method_exists($rawValue, 'getPlainText')
                ? $rawValue->getPlainText()
                : (string) $rawValue;

            if (strlen($cellValue) > 0 && str_contains($cellValue, '${')) {
                $this->checkLastRow($objCell->getRow());

                if ($this->isBasicType($cellValue)) {
                    $this->processStringPlaceholder($spreadsheet, $worksheet, $objCell, $cellValue, $extra);
                } else {
                    $columnIndex = Coordinate::columnIndexFromString($objCell->getColumn());
                    $rowValue    = $objCell->getRow();
                    $extra['placeholders'][$columnIndex][$rowValue] = $cellValue;
                }
            }
        }
    }

    private function processAdvancedPlaceholder(Spreadsheet $spreadsheet, Worksheet $worksheet, array &$extra): void
    {
        ksort($extra['placeholders']);

        while (!empty($extra['placeholders'])) {
            $columnIndex = key($extra['placeholders']);
            $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
            $rowsObj     = current($extra['placeholders']);
            $rowValue    = key($rowsObj);
            $cellValue   = current($rowsObj);

            $cellCoordinate = $columnValue . $rowValue;
            $objCell        = $worksheet->getCell($cellCoordinate);

            foreach ($this->advancedTypes as $function => $keyword) {
                $token = $this->getAdvancedTypeKeyword($keyword);
                if (str_contains($cellValue, $token)) {
                    $methodName = match ($function) {
                        'table' => 'processTable',
                        'row'   => 'processRepeatRows',
                        'column' => 'processRepeatColumns',
                        'match' => 'processMatch',
                        'dropdown' => 'processDropdown',
                        'image' => 'processImage',
                        default => $function,
                    };

                    if (method_exists($this, $methodName)) {
                        $jsonArray = $this->convertPlaceholderToArray($cellValue);
                        if (!empty($jsonArray)) {
                            $placeHolderAttr = $this->extractPlaceholderAttr($jsonArray, $keyword, $extra);
                            $cellAttr        = $this->extractCellAttr($worksheet, $objCell);
                            $attr            = array_merge($placeHolderAttr, $cellAttr);
                            $this->$methodName($spreadsheet, $worksheet, $objCell, $attr, $extra);
                        }
                    }
                    break;
                }
            }

            unset($extra['placeholders'][$columnIndex][$rowValue]);
            if (empty($extra['placeholders'][$columnIndex])) {
                unset($extra['placeholders'][$columnIndex]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Basic placeholder (string)
    // -------------------------------------------------------------------------

    private function processStringPlaceholder(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, string $search, array &$extra): void
    {
        $format = '${%s}';
        $vars   = $extra['vars'] ?? [];
        $placeHolderAttr = $this->convertPlaceholderToArray($search);

        if (empty($placeHolderAttr)) {
            // Basic type without formatting
            $strArray = explode('${', $search);
            array_shift($strArray);

            foreach ($strArray as $str) {
                $pos = strpos($str, '}');
                if ($pos !== false) {
                    $placeholder = substr($str, 0, $pos);
                    $replace     = sprintf($format, $placeholder);
                    $value       = $this->hashGet($vars, $placeholder);
                    $search      = ($value !== null)
                        ? str_replace($replace, $value, $search)
                        : '';
                }
            }

            $cellCoordinate = $objCell->getCoordinate();
            $worksheet->getCell($cellCoordinate)->setValue($search);
        } else {
            // Basic type with formatting
            $cellCoordinate = $objCell->getCoordinate();
            $cellAttr       = $this->extractCellAttr($worksheet, $objCell);
            $placeholder    = $placeHolderAttr['displayValue'];
            $flattenVar     = $this->hashFlatten($vars);
            $value          = $flattenVar[$placeholder] ?? '';
            $attr           = array_merge($placeHolderAttr, $cellAttr);
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $value, $attr, $extra);
        }
    }

    // -------------------------------------------------------------------------
    // renderCell
    // -------------------------------------------------------------------------

    public function renderCell(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, string $cellCoordinate, $cellValue, array $attr, array &$extra): void
    {
        $type        = $attr['type']        ?? null;
        $format      = $attr['format']      ?? null;
        $cellStyle   = $attr['style']       ?? $worksheet->getCell($cellCoordinate)->getStyle($cellCoordinate);
        $columnWidth = $attr['columnWidth'] ?? -1;

        $targetCell        = $worksheet->getCell($cellCoordinate);
        $targetColumnValue = $targetCell->getColumn();
        $targetRowValue    = $targetCell->getRow();

        $this->checkLastColumn($targetColumnValue);
        $this->checkLastRow($targetRowValue);

        try {
            switch ($type) {
                case 'number':
                    if ($format !== null && is_numeric($cellValue)) {
                        $cellStyle->getNumberFormat()
                            ->setFormatCode('0.' . str_repeat('0', (int) $format));
                    }
                    break;

                case 'date':
                    if ($format !== null && !empty($cellValue)) {
                        if ($cellValue instanceof \DateTimeInterface) {
                            $cellValue = $cellValue->format($format);
                        } else {
                            throw new \Exception("Invalid date for $cellCoordinate");
                        }
                    }
                    break;

                case 'time':
                    if ($format !== null && !empty($cellValue)) {
                        if ($cellValue instanceof \DateTimeInterface) {
                            $cellValue = $cellValue->format($format);
                        } else {
                            throw new \Exception("Invalid time for $cellCoordinate");
                        }
                    }
                    break;
            }
        } catch (\Throwable $e) {
            Log::warning("renderCell error at $cellCoordinate: " . $e->getMessage());
            $cellValue = '';
        }

        if (is_array($cellValue)) {
            $cellValue = $cellValue[0] ?? '';
        }

        $worksheet->setCellValue($cellCoordinate, $cellValue);
        $worksheet->duplicateStyle($cellStyle, $cellCoordinate);

        if ($columnWidth >= 0) {
            $worksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
            $worksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);
        }
    }

    // -------------------------------------------------------------------------
    // Advanced types
    // -------------------------------------------------------------------------

    private function processRepeatRows(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        $rowValue     = $attr['rowValue'];
        $columnIndex  = $attr['columnIndex'];
        $columnValue  = $attr['columnValue'];
        $nestedRow    = $attr['children'] ?? [];
        $mergeColumns = $attr['mergeColumns'];
        $mergeColIdx  = $columnIndex + ($mergeColumns - 1);

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                if ($rowValue != $attr['rowValue']) {
                    $worksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }
                $cellCoordinate = $columnValue . $rowValue;
                $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                $nestedRowValue = null;
                if (!empty($nestedRow)) {
                    $nestedRowValue = $this->nestedRow($nestedRow, $key, $rowValue, $columnIndex, $mergeColumns, $spreadsheet, $worksheet, $objCell, $attr, $extra);
                }

                $mergeRowValue = $nestedRowValue ?? $rowValue;
                $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $mergeRowValue, $worksheet, $attr);
                $rowValue = $mergeRowValue + 1;
            }
        } else {
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
            $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
            if (!empty($nestedRow)) {
                $this->nestedRow($nestedRow, -1, $rowValue, $columnIndex, $mergeColumns, $spreadsheet, $worksheet, $objCell, $attr, $extra);
            }
        }
    }

    private function nestedRow(array $nestedRow, $parentKey, int $parentRowValue, int $parentColumnIndex, int $parentMergeColumns, Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): int
    {
        $nestedAttr       = $this->extractPlaceholderAttr($nestedRow, $this->advancedTypes['row'], $extra);
        $filter           = $nestedAttr['filter'] ?? null;
        $secondNestedRow  = $nestedAttr['children'] ?? [];

        $nestedRowValue    = $parentRowValue;
        $nestedColumnIndex = $parentColumnIndex + ($parentMergeColumns - 1) + 1;
        $nestedColumnValue = Coordinate::stringFromColumnIndex($nestedColumnIndex);
        $nestedMergeColumns = $nestedAttr['mergeColumns'];
        $mergeColIdx       = $nestedColumnIndex + ($nestedMergeColumns - 1);

        $attr['columnWidth'] = $worksheet->getColumnDimension($nestedColumnValue)->getWidth();

        if (!is_null($filter)) {
            [$placeholderPrefix, $placeholderSuffix] = $this->splitDisplayValue($nestedAttr['displayValue']);
            $filterStr        = $this->formatFilter($filter);
            $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $filterStr . '.';
            $placeholder      = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
            $placeholderId    = sprintf($placeholderFormat . 'id', $parentKey);
            $nestedData       = $this->hashCombine($extra['vars'], $placeholderId, $placeholder);
        } else {
            $nestedData = $nestedAttr['data'];
        }

        if (!empty($nestedData)) {
            foreach ($nestedData as $nestedKey => $nestedValue) {
                if ($nestedRowValue != $parentRowValue) {
                    $worksheet->insertNewRowBefore($nestedRowValue);
                    $this->updatePlaceholderCoordinate(null, $nestedRowValue, $extra);
                }
                $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
                $this->renderCell($spreadsheet, $worksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

                $secondNestedRowValue = null;
                if (!empty($secondNestedRow)) {
                    $secondNestedRowValue = $this->nestedRow($secondNestedRow, $nestedKey, $nestedRowValue, $nestedColumnIndex, $nestedMergeColumns, $spreadsheet, $worksheet, $objCell, $attr, $extra);
                }

                $mergeRowValue = $secondNestedRowValue ?? $nestedRowValue;
                $this->mergeRange($nestedColumnIndex, $nestedRowValue, $mergeColIdx, $mergeRowValue, $worksheet, $attr);
                $nestedRowValue = $mergeRowValue + 1;
            }
            $nestedRowValue--;
        } else {
            $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $nestedCellCoordinate, '', $attr, $extra);
            $this->mergeRange($nestedColumnIndex, $nestedRowValue, $mergeColIdx, $nestedRowValue, $worksheet, $attr);
            if (!empty($secondNestedRow)) {
                $this->nestedRow($secondNestedRow, -1, $nestedRowValue, $nestedColumnIndex, $nestedMergeColumns, $spreadsheet, $worksheet, $objCell, $attr, $extra);
            }
        }

        return $nestedRowValue;
    }

    private function processRepeatColumns(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        $rowValue     = $attr['rowValue'];
        $columnIndex  = $attr['columnIndex'];
        $mergeColumns = $attr['mergeColumns'];
        $nestedColumn = $attr['children'] ?? [];

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                if ($columnIndex != $attr['columnIndex']) {
                    $worksheet->insertNewColumnBefore($columnValue);
                    $this->updatePlaceholderCoordinate($columnValue, null, $extra);
                }

                $cellCoordinate = $columnValue . $rowValue;
                $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                $nestedColumnIndex = null;
                if (!empty($nestedColumn)) {
                    $nestedAttr         = $this->extractPlaceholderAttr($nestedColumn, $this->advancedTypes['column'], $extra);
                    $nestedMergeColumns = $nestedAttr['mergeColumns'];
                    $nestedRowValue     = $rowValue + 1;
                    $nestedColumnIndex  = $columnIndex;

                    if (!empty($nestedAttr['data'])) {
                        foreach ($nestedAttr['data'] as $nestedValue) {
                            $nestedColumnValue = Coordinate::stringFromColumnIndex($nestedColumnIndex);
                            if ($nestedColumnIndex != $columnIndex) {
                                $worksheet->insertNewColumnBefore($nestedColumnValue);
                                $this->updatePlaceholderCoordinate($nestedColumnValue, null, $extra);
                            }
                            $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
                            $this->renderCell($spreadsheet, $worksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);
                            $nestedMergeColIdx = $nestedColumnIndex + ($nestedMergeColumns - 1);
                            $this->mergeRange($nestedColumnIndex, $nestedRowValue, $nestedMergeColIdx, $nestedRowValue, $worksheet, $attr);
                            $nestedColumnIndex = $nestedMergeColIdx + 1;
                        }
                        $nestedColumnIndex--;
                    } else {
                        $nestedColumnValue    = Coordinate::stringFromColumnIndex($nestedColumnIndex);
                        $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
                        $this->renderCell($spreadsheet, $worksheet, $objCell, $nestedCellCoordinate, '', $attr, $extra);
                        $nestedMergeColIdx = $nestedColumnIndex + ($nestedMergeColumns - 1);
                        $this->mergeRange($nestedColumnIndex, $nestedRowValue, $nestedMergeColIdx, $nestedRowValue, $worksheet, $attr);
                        $nestedColumnIndex = $nestedMergeColIdx;
                    }
                }

                $mergeColIdx = $nestedColumnIndex ?? $columnIndex + ($mergeColumns - 1);
                $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
                $columnIndex = $mergeColIdx + 1;
            }
        } else {
            $columnValue    = $attr['columnValue'];
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
            $mergeColIdx = $columnIndex + ($mergeColumns - 1);
            $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
        }
    }

    private function processTable(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        $rowValue       = $attr['rowValue'];
        $columnIndex    = $attr['columnIndex'];
        $source         = $attr['source'];
        $displayColumns = $attr['displayColumns'];
        $showHeaders    = $attr['showHeaders'];
        $insertRows     = $attr['insertRows'];

        if ($showHeaders) {
            foreach ($displayColumns as $key => $column) {
                $header      = ucwords(str_replace('_', ' ', $key));
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                $cellCoordinate = $columnValue . $rowValue;
                $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $header, $attr, $extra);
                $columnIndex++;
            }
            $rowValue++;
        }

        $sourceVars = $extra['vars'][$source] ?? null;
        if (!empty($sourceVars)) {
            foreach ($sourceVars as $vars) {
                $columnIndex = $attr['columnIndex'];
                if ($insertRows && $rowValue != $attr['rowValue']) {
                    $worksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }
                foreach ($displayColumns as $column) {
                    $value = null;
                    if (isset($column['displayValue'])) {
                        $field = $this->splitDisplayValue($column['displayValue'])[1];
                        $value = $this->hashGet($vars, $field);
                    }
                    $attr['type']   = $column['type']   ?? null;
                    $attr['format'] = $column['format'] ?? null;
                    $columnValue    = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $columnValue . $rowValue;
                    $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $value, $attr, $extra);
                    $columnIndex++;
                }
                $rowValue++;
            }
        } else {
            $columnValue    = $attr['columnValue'];
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
        }
    }

    private function processMatch(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        [$attr['placeholderPrefix'], $attr['placeholderSuffix']] =
            $this->splitDisplayValue($attr['displayValue']);

        $rowsArray    = $attr['rows']    ?? [];
        $columnsArray = $attr['columns'] ?? [];

        $rootData = $this->getPlaceholderData($attr['displayValue'], $extra);
        if ($this->isEmptyMatchData($rootData)) {
            $cellCoordinate = $attr['columnValue'] . $attr['rowValue'];
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
            return;
        }

        if (!empty($rowsArray)) {
            $this->matchRows($spreadsheet, $worksheet, $objCell, $attr, $rowsArray, $columnsArray, $extra);
        } else {
            $columnIndex = $attr['columnIndex'];
            $rowValue    = $attr['rowValue'];
            $this->matchColumns($spreadsheet, $worksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, null, $extra);
        }
    }

    private function isEmptyMatchData($arr): bool
    {
        if (empty($arr)) {
            return true;
        }
        foreach ($arr as $v) {
            if (!empty($v)) {
                return false;
            }
        }
        return true;
    }

    private function matchRows(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array $rowsArray, array $columnsArray, array &$extra): void
    {
        $matchFrom    = $rowsArray['matchFrom'] ?? [];
        $matchTo      = $rowsArray['matchTo']   ?? [];
        $rowData      = $this->getPlaceholderData($matchFrom, $extra);
        $nestedRow    = $rowsArray['children']  ?? [];
        $filterStr    = $this->formatFilter($matchTo);
        $attr['filterStr'] = ($attr['filterStr'] ?? '') . $filterStr;

        $columnIndex  = $attr['columnIndex'];
        $rowValue     = $attr['rowValue'];
        $mergeColumns = $attr['mergeColumns'];
        $mergeColIdx  = $columnIndex + ($mergeColumns - 1);

        if (!empty($rowData)) {
            foreach ($rowData as $key => $value) {
                $columnIndex = $attr['columnIndex'];
                if (!empty($columnsArray)) {
                    $this->matchColumns($spreadsheet, $worksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, $value, $extra);
                    $rowValue++;
                } elseif (!empty($nestedRow)) {
                    $printedFilter = sprintf($attr['filterStr'], $key);
                    $rowValue      = $this->nestedMatchRow($nestedRow, $printedFilter, $key, $rowValue, $columnIndex, $spreadsheet, $worksheet, $objCell, $attr, $extra);
                } else {
                    $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . '.' . $attr['placeholderSuffix'];
                    $placeholder       = sprintf($placeholderFormat, $value);
                    $matchData         = $this->hashExtract($extra['vars'], $placeholder);
                    $matchValue        = !empty($matchData) ? current($matchData) : '';

                    $colVal         = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $colVal . $rowValue;
                    $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                    $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
                    $rowValue++;
                }
            }
        } else {
            $cellCoordinate = $attr['columnValue'] . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
            $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
        }
    }

    private function matchColumns(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array $columnsArray, int &$columnIndex, int &$rowValue, $filterValue, array &$extra): void
    {
        $matchFrom   = $columnsArray['matchFrom'] ?? [];
        $matchTo     = $columnsArray['matchTo']   ?? [];
        $columnData  = $this->getPlaceholderData($matchFrom, $extra);

        $nestedColumnsArray = $columnsArray['children']['columns'] ?? [];
        $nestedMatchFrom    = $nestedColumnsArray['matchFrom'] ?? [];
        $nestedMatchTo      = $nestedColumnsArray['matchTo']   ?? [];
        $nestedColumnData   = !empty($nestedMatchFrom) ? $this->getPlaceholderData($nestedMatchFrom, $extra) : [];

        $filterStr = $this->formatFilter($matchTo);
        if (!empty($nestedColumnData)) {
            $filterStr .= $this->formatFilter($nestedMatchTo);
        }
        $attr['filterStr'] = ($attr['filterStr'] ?? '') . $filterStr;

        if (!empty($columnData)) {
            foreach ($columnData as $key => $value) {
                if (!empty($nestedColumnsArray)) {
                    foreach ($nestedColumnData as $nestedValue) {
                        $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . '.' . $attr['placeholderSuffix'];
                        $placeholder       = $filterValue !== null
                            ? sprintf($placeholderFormat, $filterValue, $value, $nestedValue)
                            : sprintf($placeholderFormat, $value, $nestedValue);
                        $matchData    = $this->hashExtract($extra['vars'], $placeholder);
                        $matchValue   = !empty($matchData) ? current($matchData) : '';
                        $nestedColVal = Coordinate::stringFromColumnIndex($columnIndex);
                        $cellCoord    = $nestedColVal . $rowValue;
                        $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoord, $matchValue, $attr, $extra);
                        $columnIndex++;
                    }
                } else {
                    $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . '.' . $attr['placeholderSuffix'];
                    $placeholder       = $filterValue !== null
                        ? sprintf($placeholderFormat, $filterValue, $value)
                        : sprintf($placeholderFormat, $value);
                    $matchData    = $this->hashExtract($extra['vars'], $placeholder);
                    $matchValue   = !empty($matchData) ? current($matchData) : '';
                    $colVal       = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoord    = $colVal . $rowValue;
                    $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoord, $matchValue, $attr, $extra);
                    $columnIndex++;
                }
            }
        } else {
            $cellCoordinate = $attr['columnValue'] . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
        }
    }

    private function nestedMatchRow(array $nestedRow, string $matchFilter, $parentKey, int $rowValue, int $columnIndex, Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): int
    {
        if (!isset($nestedRow['rows'])) {
            return $rowValue;
        }

        $nestedAttr    = $nestedRow['rows'];
        $nestedFilter  = $nestedAttr['filter']    ?? null;
        $nestedMatchFrom = $nestedAttr['matchFrom'] ?? [];
        $nestedMatchTo = $nestedAttr['matchTo']    ?? [];
        $nestedMergeBy = $nestedAttr['mergeBy']    ?? [];
        $secondNestedRow = $nestedAttr['children']  ?? [];
        $mergeColumns  = $attr['mergeColumns'];
        $mergeColIdx   = $columnIndex + ($mergeColumns - 1);

        $variableMatchFilter = $matchFilter . $this->formatFilter($nestedMatchTo);
        $nestedData = [];

        if (!empty($nestedMatchFrom)) {
            if (!is_null($nestedFilter)) {
                [$placeholderPrefix, $placeholderSuffix] = $this->splitDisplayValue($nestedMatchFrom);
                $nestedDataFilter = $this->formatFilter($nestedFilter);
                $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $nestedDataFilter . '.';
                $placeholder  = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
                $placeholderId = sprintf($placeholderFormat . 'id', $parentKey);
                $nestedData   = $this->hashCombine($extra['vars'], $placeholderId, $placeholder);
            } else {
                $nestedData = $this->getPlaceholderData($nestedMatchFrom, $extra);
            }
        }

        if (!empty($nestedData)) {
            foreach ($nestedData as $nestedKey => $nestedValue) {
                $printedMatchFilter = sprintf($variableMatchFilter, $nestedValue);
                if (!empty($secondNestedRow)) {
                    $rowValue = $this->nestedMatchRow($secondNestedRow, $printedMatchFilter, $nestedKey, $rowValue, $columnIndex, $spreadsheet, $worksheet, $objCell, $attr, $extra);
                } else {
                    $mergeRowCount = 0;
                    if (!empty($nestedMergeBy)) {
                        $mergeRowCount = $this->countMergeData($nestedMergeBy, $nestedKey, $mergeRowCount, $extra);
                    }
                    $placeholder = $this->formatPlaceholder($attr['placeholderPrefix']) . $printedMatchFilter . '.' . $attr['placeholderSuffix'];
                    $matchData   = $this->hashExtract($extra['vars'], $placeholder);
                    $matchValue  = !empty($matchData) ? current($matchData) : '';
                    $colVal      = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoord   = $colVal . $rowValue;
                    $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoord, $matchValue, $attr, $extra);
                    $mergeRowValue = ($mergeRowCount > 1) ? $rowValue + ($mergeRowCount - 1) : $rowValue;
                    $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $mergeRowValue, $worksheet, $attr);
                    $rowValue = $mergeRowValue + 1;
                }
            }
        } else {
            $colVal    = Coordinate::stringFromColumnIndex($columnIndex);
            $cellCoord = $colVal . $rowValue;
            $this->renderCell($spreadsheet, $worksheet, $objCell, $cellCoord, '', $attr, $extra);
            $this->mergeRange($columnIndex, $rowValue, $mergeColIdx, $rowValue, $worksheet, $attr);
            $rowValue++;
        }

        return $rowValue;
    }

    private function countMergeData(array $mergeAttr, $parentKey, int $mergeCount, array &$extra): int
    {
        $mergeFrom    = $mergeAttr['mergeFrom'] ?? [];
        $filter       = $mergeAttr['filter']    ?? null;
        $nestedMergeBy = $mergeAttr['mergeBy'] ?? [];
        $data = [];

        if (!empty($mergeFrom)) {
            if (!is_null($filter)) {
                [$placeholderPrefix, $placeholderSuffix] = $this->splitDisplayValue($mergeFrom);
                $formattedFilter   = $this->formatFilter($filter);
                $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $formattedFilter . '.';
                $placeholder  = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
                $placeholderId = sprintf($placeholderFormat . 'id', $parentKey);
                $data = $this->hashCombine($extra['vars'], $placeholderId, $placeholder);
            } else {
                $data = $this->getPlaceholderData($mergeFrom, $extra);
            }
        }

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!empty($nestedMergeBy)) {
                    $mergeCount = $this->countMergeData($nestedMergeBy, $key, $mergeCount, $extra);
                } else {
                    $mergeCount++;
                }
            }
        } else {
            $mergeCount++;
        }
        return $mergeCount;
    }

    private function processDropdown(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        $matchFrom = $attr['rows'] ?? [];
        $rowData   = $this->getPlaceholderData($matchFrom, $extra);

        if (!empty($rowData)) {
            $columnValue = $attr['columnValue'];
            $rowValue    = $attr['rowValue'];
            foreach ($rowData as $value) {
                $cellCoordinate = $columnValue . $rowValue;
                $this->renderDropdown($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
                $rowValue++;
            }
        } else {
            $cellCoordinate = $attr['coordinate'];
            $this->renderDropdown($spreadsheet, $worksheet, $objCell, $cellCoordinate, '', $attr, $extra);
        }
    }

    private function renderDropdown(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, string $cellCoordinate, string $cellValue, array $attr, array &$extra): void
    {
        $defaults = [
            'source'       => '',
            'promptTitle'  => 'Select from list',
            'prompt'       => 'Please select a value from the dropdown list.',
            'errorTitle'   => 'Input error',
            'error'        => 'Value is not in list.',
        ];
        $dropdownAttr = array_merge($defaults, $attr['dropdown'] ?? []);

        $validation = $worksheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setPromptTitle($dropdownAttr['promptTitle'])
            ->setPrompt($dropdownAttr['prompt'])
            ->setErrorTitle($dropdownAttr['errorTitle'])
            ->setError($dropdownAttr['error']);

        if (is_array($dropdownAttr['source'])) {
            $list  = implode(',', $dropdownAttr['source']);
            $validation->setFormula1('"' . $list . '"');
        } else {
            [$sheetName, $coordinate] = explode('.', $dropdownAttr['source']);
            $refSheet  = $spreadsheet->getSheetByName($sheetName);
            $refCell   = $refSheet->getCell($coordinate);
            $colVal    = $refCell->getColumn();
            $rowVal    = $refCell->getRow();
            $highestRow = $refSheet->getHighestRow($colVal);
            $validation->setFormula1("'$sheetName'!\$$colVal\$$rowVal:\$$colVal\$$highestRow");
        }

        $worksheet->getCell($cellCoordinate)->setValue($cellValue);
    }

    private function processImage(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, array $attr, array &$extra): void
    {
        $columnValue    = $attr['columnValue'];
        $rowValue       = $attr['rowValue'];
        $cellCoordinate = $columnValue . $rowValue;

        $attr['imageWidth']      = $attr['imageWidth']      ?? 50;
        $attr['imageMarginLeft'] = $attr['imageMarginLeft'] ?? 0;
        $attr['imageMarginTop']  = $attr['imageMarginTop']  ?? 0;

        $data         = $this->hashExtract($extra['vars'], $attr['displayValue']);
        $imageContent = !empty($data) ? current($data) : null;

        $tempImagePath = null;

        if ($attr['displayValue'] === 'Institutions.logo_content' && $imageContent) {
            if (is_resource($imageContent)) {
                $institutionId = current($this->hashExtract($extra['vars'], 'Institutions.id'));
                $mimeType      = mime_content_type($imageContent);
                $ext           = last(explode('/', $mimeType));
                $attr['mime_type'] = $mimeType;
                $tempImagePath = sys_get_temp_dir() . "/temp_logo_{$institutionId}.{$ext}";
                if (!file_exists($tempImagePath)) {
                    file_put_contents($tempImagePath, stream_get_contents($imageContent));
                    $extra['temp_logo'] = $tempImagePath;
                }
            } else {
                $tempImagePath = base_path('plugins/ReportCard/webroot/img/openemis_logo.png');
                $attr['mime_type'] = 'image/png';
            }
        }

        if ($tempImagePath) {
            $this->renderImage($spreadsheet, $worksheet, $objCell, $cellCoordinate, $tempImagePath, $attr, $extra);
        }

        $worksheet->getCell($cellCoordinate)->setValue('');
    }

    public function renderImage(Spreadsheet $spreadsheet, Worksheet $worksheet, $objCell, string $cellCoordinate, ?string $imagePath, array $attr, array &$extra): void
    {
        $imageWidth      = (int) ($attr['imageWidth']      ?? 120);
        $imageMarginLeft = (int) ($attr['imageMarginLeft'] ?? 0);
        $imageMarginTop  = (int) ($attr['imageMarginTop']  ?? 0);

        if (!$imagePath || !is_file($imagePath) || !is_readable($imagePath)) {
            Log::warning('renderImage: missing or unreadable image: ' . (string) $imagePath);
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath($imagePath);
        $drawing->setCoordinates($cellCoordinate);
        $drawing->setOffsetX($imageMarginLeft);
        $drawing->setOffsetY($imageMarginTop);
        $drawing->setWidth($imageWidth);
        $drawing->setWorksheet($worksheet);
    }

    // -------------------------------------------------------------------------
    // Save methods
    // -------------------------------------------------------------------------

    public function saveExcel(Spreadsheet $spreadsheet, string $filePath): void
    {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);
    }

    public function savePdf(Spreadsheet $spreadsheet, string $xlsxPath, array $params): ?string
    {
        try {
            // Re-load to ensure clean state for PDF rendering
            $pdfSpreadsheet = IOFactory::load($xlsxPath);

            $pdfWriter  = IOFactory::createWriter($pdfSpreadsheet, 'Mpdf');
            $pdfTmpPath = tempnam(sys_get_temp_dir(), 'rc_pdf_') . '.pdf';
            $pdfWriter->save($pdfTmpPath);

            $pdfContent = base64_encode(file_get_contents($pdfTmpPath));
            @unlink($pdfTmpPath);

            $pdfSpreadsheet->disconnectWorksheets();
            return $pdfContent;
        } catch (\Throwable $e) {
            Log::warning("savePdf failed: " . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Update DB record after generation
    // -------------------------------------------------------------------------

    public function updateReportCardRecord(array $params, string $filePath, ?string $pdfContent): void
    {
        $now = date('Y-m-d H:i:s');

        // Build filename from institution / report card / student data
        $isrc = DB::table('institution_students_report_cards as isrc')
            ->join('institutions as i', 'i.id', '=', 'isrc.institution_id')
            ->join('report_cards as rc', 'rc.id', '=', 'isrc.report_card_id')
            ->join('security_users as su', 'su.id', '=', 'isrc.student_id')
            ->where([
                'isrc.report_card_id'     => $params['report_card_id'],
                'isrc.student_id'         => $params['student_id'],
                'isrc.academic_period_id' => $params['academic_period_id'],
                'isrc.education_grade_id' => $params['education_grade_id'],
                'isrc.institution_id'     => $params['institution_id'],
            ])
            ->select([
                'i.code as institution_code',
                'rc.code as report_card_code',
                'su.openemis_no',
                'su.first_name',
                'su.last_name',
            ])
            ->first();

        if (!$isrc) {
            Log::error("updateReportCardRecord: institution_students_report_cards not found", $params);
            return;
        }

        $studentName = trim("{$isrc->first_name} {$isrc->last_name}");
        $fileName    = "{$isrc->institution_code}_{$isrc->report_card_code}_{$isrc->openemis_no}_{$studentName}.xlsx";
        $fileContent = file_exists($filePath) ? file_get_contents($filePath) : null;

        // Update institution_students_report_cards
        DB::table('institution_students_report_cards')
            ->where([
                'report_card_id'     => $params['report_card_id'],
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
                'education_grade_id' => $params['education_grade_id'],
                'institution_id'     => $params['institution_id'],
            ])
            ->update([
                'status'           => 3, // GENERATED
                'completed_on'     => $now,
                'file_name'        => $fileName,
                'file_content'     => $fileContent,
                'file_content_pdf' => $pdfContent,
                'modified'         => $now,
            ]);

        // Update report_card_processes → COMPLETED (status=3)
        DB::table('report_card_processes')
            ->where([
                'report_card_id'       => $params['report_card_id'],
                'institution_class_id' => $params['institution_class_id'],
                'student_id'           => $params['student_id'],
            ])
            ->update([
                'status'   => 3, // COMPLETED
                'modified' => $now,
            ]);
    }

    // -------------------------------------------------------------------------
    // Helper: placeholder attribute extraction
    // -------------------------------------------------------------------------

    private function convertPlaceholderToArray(string $str): array
    {
        $pos  = strpos($str, '$');
        $json = substr($str, $pos + 1);
        return json_decode($json, true) ?? [];
    }

    private function extractPlaceholderAttr(array $jsonArray, string $keyword, array &$extra): array
    {
        $settings = $jsonArray[$keyword] ?? [];
        $displayValue = $settings['displayValue'] ?? null;
        $attr = [
            'displayValue'   => $displayValue,
            'type'           => $settings['type']           ?? null,
            'format'         => $settings['format']         ?? null,
            'children'       => $settings['children']       ?? [],
            'rows'           => $settings['rows']           ?? [],
            'columns'        => $settings['columns']        ?? [],
            'filter'         => $settings['filter']         ?? null,
            'displayColumns' => $settings['displayColumns'] ?? [],
            'source'         => $settings['source']         ?? null,
            'showHeaders'    => $settings['showHeaders']    ?? false,
            'insertRows'     => $settings['insertRows']     ?? false,
            'mergeColumns'   => $settings['mergeColumns']   ?? 1,
            'imageWidth'     => $settings['imageWidth']     ?? null,
            'imageMarginLeft' => $settings['imageMarginLeft'] ?? null,
            'imageMarginTop'  => $settings['imageMarginTop']  ?? null,
            'dropdown'       => [],
        ];

        foreach (['source', 'promptTitle', 'prompt', 'errorTitle', 'error'] as $k) {
            if (isset($settings[$k])) {
                $attr['dropdown'][$k] = $settings[$k];
            }
        }

        $attr['data'] = $this->getPlaceholderData($displayValue, $extra);
        return $attr;
    }

    private function extractCellAttr(Worksheet $worksheet, $objCell): array
    {
        $columnValue = $objCell->getColumn();
        $coordinate  = $objCell->getCoordinate();
        return [
            'columnValue' => $columnValue,
            'columnIndex' => Coordinate::columnIndexFromString($columnValue),
            'columnWidth' => $worksheet->getColumnDimension($columnValue)->getWidth(),
            'rowValue'    => $objCell->getRow(),
            'coordinate'  => $coordinate,
            'style'       => $objCell->getStyle($coordinate),
        ];
    }

    private function getPlaceholderData(?string $placeholder, array &$extra): array
    {
        if ($placeholder === null) {
            return [];
        }
        $placeholderArray = explode('.', $placeholder);

        if (end($placeholderArray) === 'i') {
            array_pop($placeholderArray);
            $placeholder  = implode('.', $placeholderArray);
            $formatted    = $this->formatPlaceholder($placeholder);
            $placeholderData = $this->hashExtract($extra['vars'], $formatted);
            $count = 1;
            foreach ($placeholderData as $key => $value) {
                $placeholderData[$key] = $count++;
            }
            return $placeholderData;
        }

        $formatted        = $this->formatPlaceholder($placeholder);
        $placeholderId    = $this->splitDisplayValue($placeholder)[0] . '.id';
        $formattedId      = $this->formatPlaceholder($placeholderId);

        $idData    = $this->hashExtract($extra['vars'], $formattedId);
        $valueData = $this->hashExtract($extra['vars'], $formatted);

        if (!empty($idData) && count($idData) === count($valueData)) {
            return $this->hashCombine($extra['vars'], $formattedId, $formatted);
        }

        return $this->hashExtract($extra['vars'], $formatted);
    }

    private function isBasicType(string $str): bool
    {
        foreach ($this->advancedTypes as $function => $keyword) {
            $token = $this->getAdvancedTypeKeyword($keyword);
            if (str_contains($str, $token)) {
                return false;
            }
        }
        return true;
    }

    private function getAdvancedTypeKeyword(string $keyword): string
    {
        return sprintf('${"%s":', $keyword);
    }

    private function updatePlaceholderCoordinate(?string $affectedColumnValue, ?int $affectedRowValue, array &$extra): void
    {
        if (!is_null($affectedColumnValue)) {
            $affectedColumnIndex = Coordinate::columnIndexFromString($affectedColumnValue);
            $placeholders = [];
            foreach ($extra['placeholders'] as $columnIndex => $rowsObj) {
                if ($columnIndex >= $affectedColumnIndex) {
                    $placeholders[$columnIndex + 1] = $rowsObj;
                } else {
                    $placeholders[$columnIndex] = $rowsObj;
                }
            }
            $extra['placeholders'] = $placeholders;
        } elseif (!is_null($affectedRowValue)) {
            $placeholders = [];
            foreach ($extra['placeholders'] as $columnIndex => $rowsObj) {
                foreach ($rowsObj as $rowIndex => $obj) {
                    if ($rowIndex >= $affectedRowValue) {
                        $placeholders[$columnIndex][$rowIndex + 1] = $obj;
                    } else {
                        $placeholders[$columnIndex][$rowIndex] = $obj;
                    }
                }
            }
            $extra['placeholders'] = $placeholders;
        }
    }

    private function mergeRange(int $fromColumn, int $fromRow, int $toColumn, int $toRow, Worksheet $worksheet, array $attr): void
    {
        if ($fromColumn !== $toColumn || $fromRow !== $toRow) {
            $fromColVal  = Coordinate::stringFromColumnIndex($fromColumn);
            $toColVal    = Coordinate::stringFromColumnIndex($toColumn);
            $mergeRange  = "{$fromColVal}{$fromRow}:{$toColVal}{$toRow}";
            $worksheet->mergeCells($mergeRange);
            $cellStyle = $attr['style'];
            $cellStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $worksheet->duplicateStyle($cellStyle, $mergeRange);
        }
    }

    private function checkLastRow(int $rowValue): void
    {
        if ($rowValue > $this->lastRow) {
            $this->lastRow = $rowValue;
        }
    }

    private function checkLastColumn(string $columnValue): void
    {
        if (Coordinate::columnIndexFromString($columnValue) > Coordinate::columnIndexFromString($this->lastColumn)) {
            $this->lastColumn = $columnValue;
        }
    }

    // -------------------------------------------------------------------------
    // Path / display value helpers
    // -------------------------------------------------------------------------

    private function formatPlaceholder(string $str, int $offset = 1, int $length = 0, array $replacement = ['{n}']): string
    {
        $parts = explode('.', $str);
        array_splice($parts, $offset, $length, $replacement);
        return implode('.', $parts);
    }

    private function splitDisplayValue(string $displayValue): array
    {
        $parts  = explode('.', $displayValue);
        $prefix = array_shift($parts);
        $suffix = implode('.', $parts);
        return [$prefix, $suffix];
    }

    private function formatFilter(string $filterStr): string
    {
        $parts = explode('.', $filterStr);
        return count($parts) === 2
            ? "[{$parts[1]}=%s]"
            : "[{$filterStr}=%s]";
    }

    // -------------------------------------------------------------------------
    // CakePHP Hash compatibility helpers
    // -------------------------------------------------------------------------

    private function hashGet(array $data, string $path): mixed
    {
        $parts = explode('.', $path, 2);
        $key   = $parts[0];
        $rest  = $parts[1] ?? null;

        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];
        if ($rest === null) {
            return $value;
        }

        return is_array($value) ? $this->hashGet($value, $rest) : null;
    }

    /**
     * Hash::extract equivalent — supports {n} wildcard for numeric keys.
     */
    private function hashExtract(array $data, string $path): array
    {
        $parts = explode('.', $path, 2);
        $key   = $parts[0];
        $rest  = $parts[1] ?? null;

        if ($key === '{n}') {
            $result = [];
            foreach ($data as $k => $v) {
                if (is_numeric($k)) {
                    if ($rest !== null) {
                        $extracted = is_array($v) ? $this->hashExtract($v, $rest) : [];
                        $result    = array_merge($result, $extracted);
                    } else {
                        $result[] = $v;
                    }
                }
            }
            return $result;
        }

        if (!isset($data[$key])) {
            return [];
        }

        $value = $data[$key];
        if ($rest === null) {
            return is_array($value) ? array_values($value) : [$value];
        }

        return is_array($value) ? $this->hashExtract($value, $rest) : [];
    }

    /**
     * Hash::combine equivalent.
     */
    private function hashCombine(array $data, string $keyPath, string $valuePath): array
    {
        $keys   = $this->hashExtract($data, $keyPath);
        $values = $this->hashExtract($data, $valuePath);

        if (empty($keys) || count($keys) !== count($values)) {
            return [];
        }

        return array_combine($keys, $values);
    }

    /**
     * Hash::flatten equivalent.
     */
    private function hashFlatten(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result += $this->hashFlatten($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
