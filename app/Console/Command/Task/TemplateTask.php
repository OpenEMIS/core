<?php
App::uses('AppTask', 'Console/Command/Task');

class TemplateTask extends AppTask {
    public $tasks = array('Common');
	// to specify languages/font set not supported properly under mpdf's SetAutoFont
	public $languageFont=array('chi'=>'sun-extA',   
							);
							
################# Start HTML ################
    public function setNewLine($num=1) {
        $html = "";
        for ($i=0; $i<$num; $i++) {
            $html .= "<br>";
        }
        return $html;
    }
################# End HTML ################

	public function mapLanguageToFont($language){
		if (isset($this->languageFont[$language])){
			return $this->languageFont[$language];
		}
		return "";
	}
	
	public function checkValidity($string){
		if (!mb_check_encoding($string, "UTF-8")) {
			$string = mb_convert_encoding($string, 'UTF-8', 'HTML-ENTITIES');
		}
		return $string;
	}
	
	public function translate($string,$language){
		$fontFamily = $this->mapLanguageToFont($language);
		$translatedString=__(trim($string));
		if (!empty($fontFamily)) {
			$translatedString='<span style="font-family:'. $this->mapLanguageToFont($language) .';">'. $translatedString .'</span>';
		}
		return $translatedString;
	}
	
################# Start List ################
    public function setHeader($headerText, $headerSize="h2",$language="") {
        return "<{$headerSize}>".$this->translate($headerText,$language)."</{$headerSize}>";
    }

    public function generateList($values, $level=1,$language="",$options=array()) {
        $html = "";
        if ($level == 1) {
            $html .= "<ul>";
            foreach ($values as $value) {
				$value=$this->checkValidity($value);
				if (isset($options['translate_children']) && ($options['translate_children']==true)){
						$value = $this->translate($value,$language);
				}
                $html .= "<li>{$value}</li>"; 
            }
            $html .= "</ul>";
        } else {
            foreach ($values as $value) {
                #pr($value);
                $header = $value['Heading'];
                $html .= $this->setHeader($header,"h3",$language);
                $html .= "<ul>";
                foreach ($value['Values'] as $value) {
					$value=$this->checkValidity($value);
					if (isset($options['translate_children']) && ($options['translate_children']==true)){
						$value = $this->translate($value,$language);
					}
					$html .= "<li>{$value}</li>"; 
                }
                 $html .= "</ul>";
            }
        }
        return $html;
    }

################## End List ################

################ Start Table ###############

    public function addLogo($url) {
        $html = "<img src=\"{$url}\">";
        return $html;
    }

    public function generateTable($values,$language="") {
        $html = "";
        $html .= $this->setTableStart();
        $html .= $this->createHeaderRow($values,$language);
        $html .= $this->setContent($values,$language);
        $html .= $this->setTableEnd();

        return $html;
    }

    public function createHeaderRow(Array $data,$language="") {
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
                $header .= "<td>". $this->translate($heading,$language)."</td>"; 
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

    public function setContent(Array $data,$language="") {
        $html = "";

        $cols = $data['Cols'];
        $rows = $data['Rows'];

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