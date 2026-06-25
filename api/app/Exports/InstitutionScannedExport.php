<?php

namespace App\Exports;

use App\Models\ScannedAttendance;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\DB;

class InstitutionScannedExport
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
            'OpenEMIS ID',
            'DateTime',
            'Latitude',
            'Longitude',
            'Access',
            'Location',
            'Modified User',
            'Modified',
            'Created User',
            'Created',
        ];

        $sheet->fromArray([$headings], null, 'A1');

        $row = 2;
        foreach ($this->params as $record) {
            $sheet->fromArray(array_values((array) $record), null, 'A' . $row);
            $row++;
        }

        $customText = 'Institution Scanned Report: ' . date('Y-m-d H:i:s');
        $sheet->setCellValue('A' . ($row + 1), $customText);

        return $spreadsheet;
    }
}
