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

App::uses('AppTask', 'Console/Command/Task');

class TemplateTask extends AppTask {
    public $tasks = array('Common');

################# Start HTML ################
    public function setNewLine($num=1) {
        $html = "";
        for ($i=0; $i<$num; $i++) {
            $html .= "<br>";
        }
        return $html;
    }
################# End HTML ################

################# Start List ################
    public function setHeader($headerText, $headerSize="h2") {
        return "<{$headerSize}>".$headerText."</{$headerSize}>";
    }

    public function generateList($values, $level=1) {
        $html = "";

        if ($level == 1) {
            $html .= "<ul>";
            foreach ($values as $value) {
                $html .= "<li>{$value}</li>"; 
            }
            $html .= "</ul>";
        } else {
            foreach ($values as $value) {
                pr($value);
                $header = $value['Heading'];
                $html .= $this->setHeader($header,"h3");
                $html .= "<ul>";
                foreach ($value['Values'] as $value) {
                $html .= "<li>{$value}</li>"; 
                }
                 $html .= "</ul>";
            }
                
                // foreach ($value as $val) {
                //    $html .= "<li>{$val}</li>";
                // }
        }

        return $html;
    }

################## End List ################

################ Start Table ###############

    public function addLogo($url) {
        $html = "<img src=\"{$url}\">";
        return $html;
    }

    public function generateTable($values) {
        $html = "";
        $html .= $this->setTableStart();
        $html .= $this->createHeaderRow($values);
//        $html .= $this->setColHeader($values);
        $html .= $this->setContent($values);
        // $html .= $this->setRowValues($values);
        $html .= $this->setTableEnd();

        return $html;
    }

    public function createHeaderRow(Array $data) {
        $header = "";

        /*if (isset($data['RowHeaders'])) {
            $headings = $data['RowHeaders'];
        } else if (isset($data['DRowHeaders'])) {
            $headings = $data['DRowHeaders'];
        }*/
        $headings = $data['RowHeaders'];

        if (count($headings) > 0) {
            $header .= "<tr>";
            foreach ($headings as $heading) {
                $header .= "<td>{$heading}</td>";
            }
            $header .= "</tr>";
        }
        return $header;
    }

    public function setTableStart() {
        return "<table border=\"1\" width=\"100%\" >";
    }

    public function setTableEnd($html) {
        return $html .= "</table>";
    }

    public function setContent(Array $data) {
        $html = "";

        $cols = $data['Cols'];
        $rows = $data['Rows'];
        
        /*if (isset($data['RowHeaders'])) {
            $headings = $data['RowHeaders'];
        } else if (isset($data['DRowHeaders'])) {
            $headings = $data['DRowHeaders'];
        }*/
        $headings = $data['RowHeaders'];
        $colCount = count($headings);
        $defaultColWidth = 20;
        $colWidth = (100 - $defaultColsWidth) / $colCount;

        foreach ($cols as $key => $value) {
            $html .= "<tr>";
            $html .= "<td style=\"width: {$defaultColWidth}%\">{$value['name']}</td>";

            // loop the rows
            if (array_key_exists($key, $rows)) {
                foreach ($rows[$key] as $val) {
                    // $html .= "<td align=\"right\">{$val}</td>";
                    $html .= "<td style=\"text-align: right; width: {$colWidth}%;\">{$val}</td>";
                }
            } else {
                for ($i=0; $i < $colCount-1; $i++) {
                    $html .= "<td>&nbsp;</td>";
                }
            }

            $html .= "</tr>";
        }

        return $html;
    }

    public function setColHeader(Array $values) {
        $html = "";   

        foreach ($values['Col'] as $k => $v) {
            $html .= "<tr>";
            $html .= "<td>{$v['Area']}</td>";
            $html .= "</tr>";
        }
        return $html;
    }

    public function setRowValues(Array $values) {
        $html = "";
        $html .= "<tr>";
        foreach ($values['Values'] as $k => $v) {
            echo $v;
            $html .= "<td>{$v}</td>";
        }
        $html .= "</tr>";
        return $html;
    }

################ End Table ###############

    
}