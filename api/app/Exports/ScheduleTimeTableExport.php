<?php

namespace App\Exports;

use App\Models\InstitutionScheduleTimetables;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ScheduleTimeTableExport implements FromCollection, WithHeadings
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
