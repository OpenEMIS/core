<?php

namespace Report\Utility;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Loads only the header row and a page of data rows from an xlsx worksheet.
 */
class ReportViewReadFilter implements IReadFilter
{
    private int $startRow;

    private int $endRow;

    public function __construct(int $startRow, int $endRow)
    {
        $this->startRow = max(1, $startRow);
        $this->endRow = max($this->startRow, $endRow);
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        $row = (int)$row;

        return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
