<?php
//App::uses('Component', 'Controller');
//App::uses('UtilityComponent', 'Controller/Component');

class YearbookShell extends AppShell {
    public $tasks = array('Mpdf', 'Common', 'Template');
    public $reports, $compileData, $schoolYear = null, $schoolYearId = null, $orientationOption = "";
    public $tocSkipPrintPages = array('Page:CoverPage','Page:TOC');
	public $language ="";
	
    public $uses = array(
        'ConfigItem',
        'ConfigAttachment',
        'Reports.Report',
        'Reports.BatchReport',
        'Area',
        'AreaLevels',
        'PublicExpenditure',
        'EducationSystem',
        'EducationCertification',
        'EducationCycle',
        'EducationFieldOfStudy',
        'EducationGrade',
        'EducationLevel',
        'EducationProgramme',
        'EducationProgrammeOrientation',
        'EducationSubject',
        'InstitutionSiteProgramme',
        'InstitutionSite',
        'CensusStudent',
        'CensusTeacher',
        'CensusStaff',
        'InstitutionProvider',
        'SchoolYear'
    );

    private $yearbookId, $yearbookTemplate;
    public $folder, $filename;

    public function main() {
		if(sizeof($this->args) == 1) {
			Configure::write('Config.language', $this->args[0]);
			$this->language=$this->args[0];
		}
		
        $this->reports = array();
        $reports = $this->reports;
        $this->yearbook = $this->Report->findById(27);

        $module = str_replace(" ", "_", $this->yearbook['Report']['module']);
        $category = str_replace(" ", "_", $this->yearbook['Report']['category']);
        $reportName = str_replace(" ", "_", $this->yearbook['Report']['name']);

        $this->yearbookId = $this->yearbook['Report']['id'];
        $this->yearbookTemplate = $this->yearbook['Report']['header'];

        // $this->filename = $reportName . "." . $this->yearbook['Report']['file_type'];
		//defaults to pdf
        $this->filename = $reportName . ".pdf";
        $this->folder = WWW_ROOT ."reports/{$category}/{$module}/{$reportName}/";

        // set school year (for queries)
        $this->schoolYear = $this->getYearbookConfigItemsByName('yearbook_school_year');

        $this->out('Set School Year...');
        $this->setSchoolYear();
        $this->setOrientation();

        $this->out('Intitializing Report Setup...');
        $this->initReportSetup();

        $this->out('Setting up Yearbook Template...');
        $template = explode(",", $this->yearbookTemplate);

        $this->out("########################## Start of Config Items ###########################");
        #pr ($this->getYearbookConfigItems());
        $this->out("########################## End of Config Items ###########################");

        // Preparing the Report and start the report generation session
        foreach ($template as $item) {
            $html = "";
            if (array_key_exists($item, $this->reports)) {
                // item is a page i.e Page:xxx
                $itemElements = $this->BatchReport->findByName($item);

                // get the field template element, e.g. List:xxx, Table:xxx
                $fieldTemplate = $itemElements['BatchReport']['template'];
                $fieldTemplateArray = explode(',', $fieldTemplate);

                // output html for each element in a page
                foreach ($fieldTemplateArray as $element) {
                    $html .= $this->runPageElements($element);
                }

                // if the html has value, output html to file
                if (!empty($html) && $html != "") {

                    $settings = $this->reports[$item];
                    $this->out("########################## OUPUTING {$item} ###########################\n");
                    $this->Mpdf->init();
                    $filename = $settings['file'];

                    $this->Mpdf->AddPage($this->orientationOption);
                    $this->Mpdf->WriteHTML($html);

                    // check the total number of pages for current report
					$pageNum = count($this->Mpdf->pages);
					
                    $this->reports[$item]['pageCount'] = $pageNum;
					$this->out(" $item - written $pageNum pages ");

                    $this->Mpdf->Output($filename, 'F');
                }
            }
        }
        $this->out("########################## GENERATING TOC ###########################\n");

        $this->prepareAvailableReports();
        $this->generateTOC();
        $this->out("########################## COMPILING YEARBOOK ###########################\n");
        $this->compileYearBook();
        $this->out("########################## COMPLETED!!! ###########################\n");
    }

    //public function out($string){}
	
	public function initReportSetup() {

        $batch = $this->BatchReport->findAllByReportId($this->yearbookId);
        $batch = $this->Common->formatResult($batch);

        $this->out('Initializing Reports...');

        foreach ($batch as $report) {
            if ($report["name"] == "Template:Setup") {
                $templateArray = $batch[0]["query"];

                eval('$this->reports = '.$templateArray.';');
//                eval("\$this->reports = {$templateArray};");
            }
        }

        // pr ($batch);
        foreach ($batch as $item) {
            if (array_key_exists($item["name"], $this->reports)) {
                echo $item["name"]."<br>\n";
                $this->reports[$item["name"]]["id"] = $item["id"];
                $this->reports[$item["name"]]["query"] = $item["query"];
            }
        }
    }


    /**
     * Run the various type of generation based on the naming of the Item
     */
    public function runPageElements($elementName, $values=null) {
        $schoolYear = $this->schoolYear;
        $schoolYearId = $this->schoolYearId;

        $html = "";
        // TODO: check if there is a elementName in the db record

        // a list
        if (preg_match("/^List:/", $elementName)) {

            $this->out("########################## Start Generating {$elementName} ###########################\n");

            // get the template field
            $record = $this->BatchReport->findByName($elementName);
            // pr ($record);

            // if there is data in template field, ie. header
            if (!is_null($record['BatchReport']['template']) && preg_match("/^H[0-6]:/", $record['BatchReport']['template'])) {
                $header = explode(':', $record['BatchReport']['template']);
                $headerSize = $header[0];
                $headerText = $header[1];
                $html .= $this->Template->setHeader($headerText,$headerSize,$this->language);
            }

            // if there is data in query field
            if (!is_null($record['BatchReport']['query'])) {
                eval($record['BatchReport']['query']);
                $levels = $this->arrayDepth($data);
                $html .= $this->Template->generateList($data, $levels,$this->language);
            }

        }

        // a table
        elseif (preg_match("/^Table:/", $elementName)) {
            $this->out('#### Start Generating '.$elementName.' ####');
            $html = "";
            $tableHeader = "";

            // get the template field
            $record = $this->BatchReport->findByName($elementName);

            // if there is data in query field
            if (!is_null($record['BatchReport']['query'])) {
                eval($record['BatchReport']['query']);
            }

            // if there is data in template field, ie. header
            if (!is_null($record['BatchReport']['template'])) {
                $tpl = explode(",", $record['BatchReport']['template']);

                // ### Start Table
                $table = $this->Template->setTableStart();
                $this->out("START TABLE");

                for ($index=0; $index < count($tpl); $index++) {
                    $tplItem = $tpl[$index];

                    if (preg_match("/^H[0-6]:/", $tplItem)) {

                        $header = explode(':', $tplItem);
                        $headerSize = $header[0];
                        $headerText = $header[1];
                        $tableHeader .= $this->Template->setHeader($headerText,$headerSize,$this->language);
                    }


                    if (preg_match("/^ROWS:/", $tplItem)) {
                        // check before table is generated
                        $row = explode(":", $tplItem);
                        $rowHeadings = explode("|", $row[1]);

                        $data['RowHeaders'] = $rowHeadings;
                        $table .= $this->Template->createHeaderRow($data,$this->language);
                        unset($data['RowHeaders']);
                    }

                    if (preg_match("/^DROWS:/", $tplItem)) {

                        $batch = $this->BatchReport->findAllByName($tplItem);
                        $result = $this->Common->formatData($batch);
                        $itemName = $batch[0]["query"];
                        eval($itemName);

                        $data['RowHeaders'] = $values;
                        $table .= $this->Template->createHeaderRow($data,$this->language);
                        unset($data['RowsHeaders']);
                    }
                }
                $table .= $this->Template->setContent($data,$this->language);
                $this->out("SET CONTENT");
                $table .=  $this->Template->setTableEnd();
                $this->out("END TABLE");

                // ### End Table
                $html .= $tableHeader;
                $html .= $table;

            }
        }

        // a page
        elseif (preg_match("/^Page:/", $elementName)) {
            $this->out('#### Start Generating '.$elementName.' ####');
            // if it is page, then only require template field
            $record = $this->BatchReport->findByName($elementName);
            pr ($record);
        }

        elseif (preg_match("/^H[0-6]:/", $elementName)) {
            $header = explode(":", $elementName);
            $headerSize = $header[0];
            $headerText = $header[1];

            // TODO: may need to shift this (same as ConfigVar)
            if (preg_match("/^\[([^)]+)\]$/", $headerText)) {
                $name = $headerText;
                $name = trim($name, '[');
                $name = trim($name, ']');
                $headerText = $this->getYearbookConfigItemsByName($name);
            }

            $html = $this->Template->setHeader($headerText,  $headerSize,$this->language);
        }

        elseif (preg_match("/^LOGO:/", $elementName)) {
            $attachmentId = $this->getYearbookConfigItemsByName('yearbook_logo');
            $attachment = $this->ConfigAttachment->findById($attachmentId, 'ConfigAttachment.id, ConfigAttachment.file_content, ConfigAttachment.file_name');
            // pr ($attachment['ConfigAttachment']['file_content']);
            if (!empty($attachment['ConfigAttachment']['file_content'])) {
                $imgFile = file_put_contents(IMAGES.$attachment['ConfigAttachment']['file_name'], $attachment['ConfigAttachment']['file_content']);
                $imgUrl = WWW_ROOT.IMAGES_URL.$attachment['ConfigAttachment']['file_name'];
                $html .= $this->Template->addLogo($imgUrl);
            }
        }

        // elseif (preg_match("/^ConfigVar:/", $elementName)) {
        elseif (preg_match("/^\[([^)]+)\]$/", $elementName)) {
            // checking the configvar
            // allowed for the variables that is available in the config item yearbook section, e.g. yearbook_school_year, yearbook_title, etc
            // checking for [yearbook_school_year]

            $name = $elementName;
            $name = trim($name, '[');
            $name = trim($name, ']');

            $html .= $this->getYearbookConfigItemsByName($name);

        }

        elseif (preg_match("/^NEWLINE$/", $elementName)) {
            // set one newline
            $html = $this->Template->setNewLine();
        }

        elseif (preg_match("/^NEWLINE:/", $elementName)) {
            // set multiple newline
            $newLine = explode(':', $elementName);
            $newLineNum = $newLine[1];
            // setting new line
            $html = $this->Template->setNewLine($newLineNum);
        }

        // a template:setup
        elseif (preg_match("/^Template:Setup$/", $elementName)) {

        }

        else {
            echo __("There is either no such type, or error in the name. Please Check.\n");
        }

        if (isset($data)) { unset($data); }
        return $html;
    }

    public function runHeader($header) {
        // if there is data in template field, ie. header
        if (!is_null($record['BatchReport']['template']) && preg_match("/^H[0-6]:/", $record['BatchReport']['template'])) {
            $header = explode(':', $record['BatchReport']['template']);
            $headerSize = $header[0];
            $headerText = $header[1];
            return $html = $this->Template->setHeader($headerText, $headerSize,$this->language);
        }
    }


    /**
     * Generating Table of Contents (TOC)
     */
    public function generateTOC() {
        $this->Mpdf->init();
        $tocSetup = $this->reports['Page:TOC'];
        $filename = $tocSetup['file'];
        $html = "<h1>".$this->Template->translate($tocSetup['title'],$this->language)."</h1>";

        // get the list from the Report
        $reportYearbook = $this->BatchReport->findByName("Template:Setup", array('BatchReport.report_id'));
        $reportYearbookData = $this->Report->findById($reportYearbook['BatchReport']['report_id'], array('Report.header'));

        $tocData = explode(",", $reportYearbookData['Report']['header']);

        // remove any default files to be skipped
        $tocData = array_diff($tocData,$this->tocSkipPrintPages);

        foreach ($tocData as $key => $page) {
            $tocData[$key] = str_replace("Page:", "", $page);
        }

        $levels = $this->arrayDepth($tocData);
        $html .= $this->Template->generateList($tocData, $levels,$this->language,array('translate_children'=>true));

        $this->Mpdf->AddPage($this->orientationOption);
        $this->Mpdf->WriteHTML($html);

        // check the total number of pages for TOC report
        //$pageNum = $this->Mpdf->getDocPageNum();
		$pageNum = $this->Mpdf->getPageCount();
		
        $this->reports['Page:TOC']['pageCount'] = $pageNum;

        $this->Mpdf->Output($filename, 'F');
    }


    /**
     * Merging reports into Yearbook
     */
    public function compileYearBook() {
        $this->Mpdf->init();
        $path = $this->folder.$this->filename;
		$this->out("compiling ".$path);
        $this->Mpdf->SetImportUse();
        $this->Mpdf->setFooter('{PAGENO}');

        $reports = $this->prepareAvailableReports();
        $reportFiles = $reports['files'];
        $reportPageCount = $reports['pageCount'];

		$this->out(print_r($reportFiles, true));
		
		$this->out(print_r($reportPageCount, true));
		
        for ($i=0; $i < count($reportFiles); $i++) {
            $src = $reportFiles[$i];
            $totalPages = $reportPageCount[$i];
            $this->Mpdf->RestartDocTemplate();
            $pageCount = $this->Mpdf->SetDocTemplate($src);
//            $this->Mpdf->AddPage($this->orientationOption);

            // set the new page to cater for the imported file
            for ($k=1; $k <= $totalPages; $k++) {
                $this->Mpdf->AddPage($this->orientationOption);
            }

            // add the new imported pages
            for ($j=0; $j < $pageCount; $j++) {
                $tpl = $this->Mpdf->ImportPage($pageCount++);
                $this->Mpdf->UseTemplate($tpl);
            }
        }

        $this->Mpdf->Output($path, 'F');

    }


    /**
     * get the depth of an array
     * @param array $array
     * @return int
     */
    public function arrayDepth(array $array) {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->arrayDepth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }

    /**
     * get all yearbook config items
     * @return mixed
     */
    public function getYearbookConfigItems() {
        $configItems = $this->ConfigItem->getYearbook();
        return $configItems;
    }

    public function setSchoolYear() {
        $schoolYear = $this->getYearbookConfigItemsByName('yearbook_school_year');
        $schoolYearId = $this->SchoolYear->getSchoolYearId($schoolYear);
        $this->schoolYear = $schoolYear;
        $this->schoolYearId = $schoolYearId;
    }

    public function setOrientation() {
        $orientationOption = $this->getYearbookConfigItemsByName('yearbook_orientation');
        // if portrait, then default as '', else if landscape then set as 'L'
        $this->orientationOption = ($orientationOption) ? 'L' : '';
    }

    /**
     * get yearbook config items based on name
     * @param $name
     * @return null
     */
    public function getYearbookConfigItemsByName($name) {
        $value = null;
        $configItems = $this->getYearbookConfigItems();
        if (array_key_exists($name, $configItems)) {
            $value = $configItems[$name];
        }
        return $value;
    }


    /**
     * gather all the indiviual reports, to prepare for the compilation
     */
    public function prepareAvailableReports() {

        foreach ($this->reports as $report) {
            $data['files'][] = $report['file'];             // get all filenames;
            $data['pageCount'][] = $report['pageCount'];    // get all page numbers of each report
        }

        // get all Page Names to prepare for TOC
        $pages = array_keys($this->reports);
        $data['toc'] = $pages;
        $this->compileData = $data;

        return $this->compileData;
    }

}
?>