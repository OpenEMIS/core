<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;

class StudentAttendancesExport implements FromArray, WithHeadings, WithEvents
{
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
        return $this->params;
    }


    public function headings(): array
    {
        return [
            'Openemis ID',
            'Name',
            'Attendance',
            'Date',
            'Student Statuses',
            'Class',
            'Absent Reasons',
            'Comment',
            'Modified User',
            'Modified',
            'Created User',
            'Created'
        ];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Get the total number of rows
                $rowCount = $event->sheet->getHighestRow();

                // Add custom text after the last row
                $customText = 'Report Generated: '.Date('Y-m-d H:i:s');
                $event->sheet->setCellValue('A' . ($rowCount + 2), $customText);
            },
        ];
    }
}
