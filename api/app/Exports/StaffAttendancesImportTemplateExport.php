<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StaffAttendancesImportTemplateExport
{
    private const HEADER_COLOR = '6699CC';

    private array $templateData;

    public function __construct(array $templateData)
    {
        $this->templateData = $templateData;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // First sheet = Data
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data');

        // Second sheet = References
        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('References');

        $this->buildReferencesSheet($referenceSheet);
        $this->buildDataSheet($spreadsheet, $dataSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buildDataSheet(Spreadsheet $spreadsheet, Worksheet $sheet): void 
    {
        $sheet->setTitle('Data');

        $sheet->getRowDimension(1)->setRowHeight(75);
        $sheet->mergeCells('C1:F1');
        $sheet->setCellValue('C1', 'Import Staff Attendances Data');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('C1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $headers = [
            'Openemis ID',
            'Academic Period Code',
            'Date ( DD/MM/YYYY )',
            'Time In (HH:MM AM/PM)',
            'Time Out (HH:MM AM/PM)',
            'Comment',
        ];

        $lastColumn = chr(ord('A') + count($headers) - 1);
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue($column . '2', $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $this->applyHeaderStyle($sheet, 'A2:' . $lastColumn . '2');

        // Header row height for padding effect
        $sheet->getRowDimension(2)->setRowHeight(26);

        $this->addLogo($sheet);
        $this->addOpenemisDropdown($spreadsheet, $sheet);
        $this->addAcademicPeriodDropdown($spreadsheet, $sheet);
    }

    private function buildReferencesSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('References');

        // Logo/Header Row
        $sheet->getRowDimension(1)->setRowHeight(75);

        // Resources Title
        $sheet->mergeCells('C1:G1');
        $sheet->setCellValue('C1', 'Resources');

        $sheet->getStyle('C1')->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('C1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Main Section Headers
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('D2:G2');

        $sheet->setCellValue('A2', 'Openemis No');
        $sheet->setCellValue('D2', 'Academic Period');

        // Center align merged headers
        $sheet->getStyle('A2:G2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2:G2')->getFont()
            ->setBold(true)
            ->setSize(12);

        // Row height like screenshot
        $sheet->getRowDimension(2)->setRowHeight(24);

        // Apply borders for merged header row
        $sheet->getStyle('A2:G2')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Reference Table Headers
        $referenceHeaders = [
            'A3' => 'Institution: ' . ($this->templateData['institution_name'] ?? ''),
            'B3' => 'Name',
            'C3' => 'OpenEMIS ID',
            'D3' => 'Name',
            'E3' => 'Start Date',
            'F3' => 'End Date',
            'G3' => 'Code',
        ];

        foreach ($referenceHeaders as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Apply blue header style
        $this->applyHeaderStyle($sheet, 'A3:G3');

        // Header row height
        $sheet->getRowDimension(3)->setRowHeight(26);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(12);

        // Data Rows
        $row = 4;

        foreach ($this->templateData['reference_rows'] as $rowData) {
            $column = 'A';

            foreach ($rowData as $cellData) {
                $sheet->setCellValue($column . $row, $cellData);
                $column++;
            }

            $row++;
        }

        $this->addLogo($sheet);
    }

    private function applyHeaderStyle(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);

        $style->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $style->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB(self::HEADER_COLOR);

        $style->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    private function addLogo(Worksheet $sheet): void
    {
        $imagePath = dirname(base_path()) . DIRECTORY_SEPARATOR
            . 'plugins' . DIRECTORY_SEPARATOR . 'Import' . DIRECTORY_SEPARATOR
            . 'webroot' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'openemis_logo.jpg';

        if (!file_exists($imagePath)) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName('OpenEMIS Logo');
        $drawing->setDescription('OpenEMIS Logo');
        $drawing->setPath($imagePath);
        $drawing->setHeight(100);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
    }

    private function addOpenemisDropdown(Spreadsheet $spreadsheet, Worksheet $sheet): void
    {
        $referenceSheet = $spreadsheet->getSheetByName('References');

        $highestRow = $referenceSheet->getHighestRow();

        for ($row = 3; $row <= 500; $row++) {

            $validation = $sheet->getCell('A' . $row)->getDataValidation();

            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);

            $validation->setFormula1(
                "'References'!\$C\$4:\$C\$" . $highestRow
            );
        }
    }

    private function addAcademicPeriodDropdown(Spreadsheet $spreadsheet, Worksheet $sheet): void
    {
        $referenceSheet = $spreadsheet->getSheetByName('References');

        $highestRow = $referenceSheet->getHighestRow();

        for ($row = 3; $row <= 500; $row++) {

            $validation = $sheet->getCell('B' . $row)->getDataValidation();

            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);

            $validation->setFormula1(
                "'References'!\$G\$4:\$G\$" . $highestRow
            );
        }
    }
}
