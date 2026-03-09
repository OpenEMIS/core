<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StudentAttendanceArchiveExport
{
    private array $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headings = [
            'Student',
            'Academic Period',
            'Institution Class',
            'Education Grade',
            'Date',
            'Period',
            'Comment',
            'Absence Type',
            'Student Absence Reason',
            'Subject',
        ];

        $sheet->fromArray([$headings], null, 'A1');

        $row = 2;
        foreach ($this->params as $record) {
            $sheet->fromArray(array_values((array) $record), null, 'A' . $row);
            $row++;
        }

        $customText = 'Report Generated: ' . date('Y-m-d H:i:s');
        $sheet->setCellValue('A' . ($row + 1), $customText);

        return $spreadsheet;
    }
}
