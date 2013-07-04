<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class IndicatorCsvWriter {

    const FILE_EXT = '.csv';
    private $filename;
    private $header;

    public function __construct($header=array('Indicator', 'Subgroup', 'Area', 'Time Period', ' Data Value', 'Classification')){
        $this->header = $header;
//        $this->filename = tempnam(sys_get_temp_dir(), 'csv');
    }

    public function generate($resultSet){
        $filename = $this->createFilenameFromIndicator(reset($resultSet));
        $this->downloadHeader($filename);
        echo $this->processData($resultSet);
//        $this->output();
//        exit;
    }

    public function downloadHeader($filename=""){
        if(empty($filename)) $filename = "download".IndicatorCsvWriter::FILE_EXT;
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

    public function processData($resultSet){
        if (count($resultSet) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, $this->header);
        foreach ($resultSet as $series) {
            $subgroupString = $this->toStringSubgroups($series->getSubgroups());
            foreach($series->getObservations() as $observation){
                $value = floatval($observation->getValue());
                $value = round($value,3);
                fputcsv($df, array($series->getIndicator(),$subgroupString,$series->getArea(), $observation->getTimeperiod(), $value, $observation->getClassifications()));
            }
        }
        fclose($df);
        return ob_get_clean();
    }

    public function toStringSubgroups($subgroups){
        $subgroupNames = array();
        foreach($subgroups as $subgroup){
            array_push($subgroupNames, $subgroup->getName());
        }

        return implode(' - ', $subgroupNames);
    }

    public function createFilenameFromIndicator($firstSeries){
        $filename = $firstSeries->getIndicator();
        return str_ireplace(' ', '_', $filename).IndicatorCsvWriter::FILE_EXT;

    }

}