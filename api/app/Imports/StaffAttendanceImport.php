<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;

class StaffAttendanceImport
{
    /**
     * Load the uploaded file and return all sheets as a nested array.
     *
     * @param  string|\Illuminate\Http\UploadedFile  $file
     * @return array
     */
    public static function toArray($file): array
    {
        $path = is_string($file) ? $file : $file->getRealPath();
        $spreadsheet = IOFactory::load($path);

        $result = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $result[] = $worksheet->toArray(null, true, true, false);
        }

        return $result;
    }
}
