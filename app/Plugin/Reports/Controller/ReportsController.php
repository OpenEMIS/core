<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class ReportsController extends ReportsAppController {
    public $bodyTitle = 'Reports';
    public $headerSelected = 'Reports';
    public $limit = 1000;
    

    public $uses = array(
		'BatchProcess',
        'Reports.Report',
		'Reports.BatchReport',
		'Institution',
		'InstitutionSite',
		'InstitutionSiteCustomValue',
		'InstitutionSiteProgramme',
        'CensusStudent',
        'SchoolYear'
    );
	public $standardReports = array( //parameter passed to Index
		'Institution'=>array('enable'=>true),
		'Student'=>array('enable'=>true),
		'Teacher'=>array('enable'=>true),
		'Staff'=>array('enable'=>true),
		'Consolidated'=>array('enable'=>true),
		'Indicator'=>array('enable'=>true),
		'DataQuality'=>array('enable'=>true),
		'Custom'=>array('enable'=>true));
	
    public $helpers = array('Paginator');
    public $components = array('Paginator','DateTime','Utility');
	private $pathFile = '';
    public function beforeFilter() {
        parent::beforeFilter();
        $this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index'));
		
		if(array_key_exists(ucfirst($this->action), $this->standardReports)) {
			$this->renderReport(ucfirst($this->action));
			if(isset($this->params['pass'][0])){
				$this->reportList($this->params['pass'][0]);
				$this->render('report_list');
			}else{
				$this->render('index');
			}
		}
    }
	
	public function index() {
		$this->redirect(array('controller' => $this->params['controller'], 'action' => 'Institution'));
	}
	
	public function Institution(){}
	public function Student(){}
	public function Staff(){}
	public function Teacher(){}
	public function Consolidated(){}
	public function Indicator(){}
	public function Custom(){}
	public function DataQuality(){}
	
	public function renderReport($reportType = 'Institution') {
		
		if(isset($this->params['pass'][0])){
			$this->Navigation->addCrumb($reportType.' Reports', array('controller' => 'Reports', 'action' => $this->action));
			$this->Navigation->addCrumb(' Generated Files');
		}else{
			$this->Navigation->addCrumb($reportType.' Reports');
		}
		
		
		

		if(array_key_exists($reportType, $this->standardReports)){
			if(!$this->standardReports[$reportType]['enable'] === false){
				$this->set('enabled',true);
			}else{
				$this->set('enabled',false);
			}
		}
		
		//pr($this->InstitutionSiteProgramme->find('all',array('limit'=>2)));
		$reportType = Inflector::underscore($reportType);
		$reportType = str_replace('_',' ',$reportType);
		$data = $this->Report->find('all',array('conditions'=>array('category'=>$reportType.' Reports')));
		
        $checkFileExist = array();
		$tmp = array();
		
		//arrange and sort according to grounp
		foreach($data as $k => $val){
			//$pathFile = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$val['Report']['category']).DS.$val['Report']['module'].DS.str_replace(' ','_',$val['Report']['name']).'.'.$val['Report']['file_type'];
			$module = $val['Report']['module'];
            $category = $val['Report']['category'];
            $name = $val['Report']['name'];
			$val['Report']['file_type'] = ($val['Report']['file_type']=='ind'?'csv':$val['Report']['file_type']);
			$tmp[$reportType.' Reports'][$module][$name] =  $val['Report']; 
        }
              
		$msg = (isset($_GET['processing']))?'processing':'';
        $this->set('msg',$msg);
		$this->set('data',$tmp);
        //$this->set('checkFileExist',$checkFileExist);
	}
	
    public function olap(){
//        $this->autoRender = false;

        $this->Navigation->addCrumb('OLAP Report');
        //$this->Navigation->selected('reports', strtolower($reportType));

        $selectedFields = array(
            'Area' => array('name'),
            'Institution' => array(
                'name', 'code', 'address', 'postal_code', 'contact_person', 'telephone',
                'fax', 'email', 'website', 'date_opened', 'date_closed'
            ),
            'InstitutionSector' => array('name'),
            'InstitutionProvider' => array('name'),
            'InstitutionStatus' => array('name'),
            'InstitutionSite' => array(
                'name', 'code', 'address', 'postal_code', 'contact_person', 'telephone',
                'fax', 'email', 'website', 'date_opened', 'date_closed', 'longitude', 'latitude'
            ),
            'InstitutionSiteLocality' => array('name'),
            'InstitutionSiteType' => array('name'),
            'InstitutionSiteOwnership' => array('name'),
            'InstitutionSiteStatus' => array('name')
        );
        $this->humanizeFields($selectedFields);
        $data = $selectedFields;
//        $data[get_class($this->Institution)] = $this->getTableCloumn($this->Institution, array_key_exists(get_class($this->Institution),$selectedFields)? $selectedFields[get_class($this->Institution)]: array());
//        $data[get_class($this->InstitutionSite)] = $this->getTableCloumn($this->InstitutionSite, array_key_exists(get_class($this->InstitutionSite),$selectedFields)? $selectedFields[get_class($this->InstitutionSite)]: array());
        $raw_school_years = $this->SchoolYear->find('list');
        $school_years = array();
        foreach($raw_school_years as $value){
            array_push($school_years, $value);

        }

        $this->set('data', $data);
        $this->set('school_years', $school_years);

    }

    public function olapGetObservations(){
        $this->autoRender = false;
        if($this->request->is('post')){
            $data = array('observations'=> array(), 'size' => 0);
            $fields = array();
            $models = array();
            $selectedSchoolYear = (isset($this->data['schoolYear']) && !empty($this->data['schoolYear']))? $this->data['schoolYear']: 0000;
            foreach($this->data['variables'] as $key => $value){
                array_push($fields, $value);
            }

            $rawData= $this->Institution->find('list', array(
                'joins' => array(
                    array(
                        'alias' => 'InstitutionSite',
                        'table' => 'institution_sites',
                        'type' => 'LEFT',
                        'conditions' => '`InstitutionSite.institution_id = Institution.id'
                    )
                ),
                'group' => array('Institution.id'),
                'conditions' => array('Institution.id IS NOT NULL'),
//                'limit' => 10 // for debugging
            ));
            foreach($rawData as $key => $value){
                array_push($data['observations'], $key);

            }

            $data['total'] = sizeof($data['observations']);
            return json_encode($data);
        }

    }

    public function olapGetNumberOfRecordsPerObservation( $observation = 0, $year = '' ) {
        $this->autoRender = false;
        $data = 0;
        if(!empty($observation) and !empty($year)){
            $dbo = ConnectionManager::getDataSource('default');
            $conditions = array();

            $joins = array(
                'census_students' => array(
                    'table' => 'census_students',
                    'alias' => 'CensusStudent',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'CensusStudent.school_year_id = SchoolYear.id'
                    )
                ),
                'institution_site_programmes' => array(
                    'table' => 'institution_site_programmes',
                    'alias' => 'InstitutionSiteProgramme',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteProgramme.id = CensusStudent.institution_site_programme_id'
                    )
                ),
                'institution_sites' => array(
                    'table' => 'institution_sites',
                    'alias' => 'InstitutionSite',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSite.id = InstitutionSiteProgramme.institution_site_id'
                    )
                ),
                'institutions' => array(
                    'table' => 'institutions',
                    'alias' => 'Institution',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'Institution.id = InstitutionSite.institution_id'
                    )
                ),
                'areas' => array(
                    'table' => 'areas',
                    'alias' => 'Area',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'Area.id = InstitutionSite.area_id',
                        'Area.id = Institution.area_id'
                    )
                ),
                'institution_sectors' => array(
                    'table' => 'institution_sectors',
                    'alias' => 'InstitutionSector',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSector.id = Institution.institution_sector_id'
                    )
                ),
                'institution_providers' => array(
                    'table' => 'institution_providers',
                    'alias' => 'InstitutionProvider',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionProvider.id = Institution.institution_provider_id'
                    )
                ),
                'institution_statuses' => array(
                    'table' => 'institution_statuses',
                    'alias' => 'InstitutionStatus',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionStatus.id = Institution.institution_status_id'
                    )
                ),
                'institution_site_localities' => array(
                    'table' => 'institution_site_localities',
                    'alias' => 'InstitutionSiteLocality',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteLocality.id = InstitutionSite.institution_site_locality_id'
                    )
                ),
                'institution_site_types' => array(
                    'table' => 'institution_site_types',
                    'alias' => 'InstitutionSiteType',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteType.id = InstitutionSite.institution_site_type_id'
                    )
                ),
                'institution_site_ownership' => array(
                    'table' => 'institution_site_ownership',
                    'alias' => 'InstitutionSiteOwnership',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteOwnership.id = InstitutionSite.institution_site_ownership_id'
                    )
                ),
                'institution_site_statuses' => array(
                    'table' => 'institution_site_statuses',
                    'alias' => 'InstitutionSiteStatus',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteStatus.id = InstitutionSite.institution_site_status_id'
                    )
                ),
                'student_categories' => array(
                    'table' => 'student_categories',
                    'alias' => 'StudentCategory',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'StudentCategory.id = CensusStudent.student_category_id'
                    )
                ),
                'education_grades' => array(
                    'table' => 'education_grades',
                    'alias' => 'EducationGrade',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'EducationGrade.id = CensusStudent.education_grade_id'
                    )
                ),
                'education_programmes' => array(
                    'table' => 'education_programmes',
                    'alias' => 'EducationProgramme',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id'
                    )
                )
            );

            $selectedJoins = array();
            foreach($joins as $value) {
                array_push($selectedJoins, $value);
            }

            $params = array(
                'fields' => array("COUNT(*) AS total"),
                'table' => 'school_years',
                'alias' => "SchoolYear",
                'limit' => null,
                'offset' => 0,
                'joins' => $selectedJoins,
                'conditions' => array("Institution.id = {$observation} AND Institution.id IS NOT NULL AND SchoolYear.name = {$year} "),
                'recursive' => 0,
                'order' => null,
                'group' => null
            );

				// build sub-query
            $query = $dbo->buildStatement(
                $params,
                $this->CensusStudent
            );

            $rawData = $dbo->query($query);
            $data = array_pop(array_pop($rawData));
            //return ($data);

        }

        return $data['total'];

    }

    public function genOlapReport(/*$observationId=0, $batch=0, $year="0000"*/){
        $this->autoRender = false;
        $data= array();
        if($this->request->is('post')){
            $selectedFields = array(
                'SchoolYear' => array('name'),
                'CensusStudent' => array('age', 'male', 'female'/*, 'institution_site_programme_id'*/),
                'EducationProgramme' => array('name'),
                'EducationGrade' => array('name'),
                'StudentCategory' => array('name'),
            );
            $fields = array();
            foreach($selectedFields as $key => $value) {
                foreach($value as $field){
                    array_push($fields, $key.".".$field);

                }
            }
            foreach($this->data['variables'] as $value){
                array_push($fields,$value);
            }

			$csvSettings = array(
				'tpl'=> implode(',',$fields),//'Indicator,SubGroup,AreaName,TimePeriod,DataValue,Classification',
                'observationId' => $this->data['observationId'],
                'year' => $this->data['year'],
				'batch'=>$this->data['batch'],
                'last_batch' => (isset($this->data['last']) && !is_null($this->data['last']))? $this->data['last']:false
            );
            $data['batch'] = $this->data['batch']+1;
            $data['processed_observations'] = $this->genCSV($csvSettings);
        }

        return json_encode($csvSettings);
    }
	
	public function download($filename){
        if($filename == '' ){
            die();
        }else{
			
			$info['basename'] = $filename;
			/* Return array
			 * Array
				(
					[basename] => 1_980_Institution_Report.csv
					[reportId] => 1
					[batchProcessId] => 980
					[name] => Institution_Report.csv
				)
			 * 
			 */
			$this->parseFilename($info);
				
			$resChck = $this->BatchProcess->find('all',array('conditions'=>array('id'=>$info['batchProcessId'],'status'=>array(1,2))));// filename that's currently being proessed
			if($resChck){
                $referrer = str_replace('?processing','',Controller::referer());
                $this->redirect($referrer.'?processing');
            }
			
			$res = $this->Report->find('first',array('conditions'=>array('id'=>$info['reportId'])));// get the path
		
            $module = $res['Report']['module'];
            $category = $res['Report']['category'];
            $name = $res['Report']['name'];
            $res['Report']['file_type'] = ($res['Report']['file_type']=='ind'?'csv':$res['Report']['file_type']);
            $xt = $res['Report']['file_type'];
			
            //$path =  WWW_ROOT.DS.$module.DS;
            //$path = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$category).DS.$module.DS;

            $this->viewClass = 'Media';
            // Download app/outside_webroot_dir/example.zip
            $params = array(
                'id'        => $filename,
                'name'      => $name,
                'download'  => true,
                'extension' => $res['Report']['file_type'],
                //'path'      => APP . 'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$category).DS.$module.DS
                'path'		=> APP.WEBROOT_DIR.DS.'reports'.DS.str_replace(' ','_',$category).DS.str_replace(' ','_',$module).DS
            );
            $this->set($params);
        }

	}

    public function generateRawQuery($settings) {
        $query = '';
        $observation = $settings['observationId'];
        $year = $settings['year'];
        //$limit = $settings['limit'];
        $offset = $settings['offset'];
        $fields = explode(',',$settings['tpl']);//array();

        $joins = array(
            'census_students' => array(
                'table' => 'census_students',
                'alias' => 'CensusStudent',
                'type' => 'LEFT',
                'conditions' => array(
                    'CensusStudent.school_year_id = SchoolYear.id'
                )
            ),
            'institution_site_programmes' => array(
                'table' => 'institution_site_programmes',
                'alias' => 'InstitutionSiteProgramme',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSiteProgramme.id = CensusStudent.institution_site_programme_id'
                )
            ),
            'institution_sites' => array(
                'table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteProgramme.institution_site_id'
                )
            ),
            'institutions' => array(
                'table' => 'institutions',
                'alias' => 'Institution',
                'type' => 'LEFT',
                'conditions' => array(
                    'Institution.id = InstitutionSite.institution_id'
                )
            ),
            'areas' => array(
                'table' => 'areas',
                'alias' => 'Area',
                'type' => 'LEFT',
                'conditions' => array(
                    'Area.id = InstitutionSite.area_id',
                    'Area.id = Institution.area_id'
                )
            ),
            'institution_sectors' => array(
                'table' => 'institution_sectors',
                'alias' => 'InstitutionSector',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSector.id = Institution.institution_sector_id'
                )
            ),
            'institution_providers' => array(
                'table' => 'institution_providers',
                'alias' => 'InstitutionProvider',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionProvider.id = Institution.institution_provider_id'
                )
            ),
            'institution_statuses' => array(
                'table' => 'institution_statuses',
                'alias' => 'InstitutionStatus',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionStatus.id = Institution.institution_status_id'
                )
            ),
            'institution_site_localities' => array(
                'table' => 'institution_site_localities',
                'alias' => 'InstitutionSiteLocality',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSiteLocality.id = InstitutionSite.institution_site_locality_id'
                )
            ),
            'institution_site_types' => array(
                'table' => 'institution_site_types',
                'alias' => 'InstitutionSiteType',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSiteType.id = InstitutionSite.institution_site_type_id'
                )
            ),
            'institution_site_ownership' => array(
                'table' => 'institution_site_ownership',
                'alias' => 'InstitutionSiteOwnership',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSiteOwnership.id = InstitutionSite.institution_site_ownership_id'
                )
            ),
            'institution_site_statuses' => array(
                'table' => 'institution_site_statuses',
                'alias' => 'InstitutionSiteStatus',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSiteStatus.id = InstitutionSite.institution_site_status_id'
                )
            ),
            'student_categories' => array(
                'table' => 'student_categories',
                'alias' => 'StudentCategory',
                'type' => 'LEFT',
                'conditions' => array(
                    'StudentCategory.id = CensusStudent.student_category_id'
                )
            ),
            'education_grades' => array(
                'table' => 'education_grades',
                'alias' => 'EducationGrade',
                'type' => 'LEFT',
                'conditions' => array(
                    'EducationGrade.id = CensusStudent.education_grade_id'
                )
            ),
            'education_programmes' => array(
                'table' => 'education_programmes',
                'alias' => 'EducationProgramme',
                'type' => 'LEFT',
                'conditions' => array(
                    'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id'
                )
            )
        );

        $dbo = ConnectionManager::getDataSource('default');
        $conditions = array();

        $selectedJoins = array();
        foreach($joins as $value) {
            array_push($selectedJoins, $value);
        }

        $params = array(
            'fields' => $fields,//array('*'),
            'table' => 'school_years',
            'alias' => "SchoolYear",
            'limit' => $this->limit,
            'offset' => $offset,
            'joins' => $selectedJoins,
            'conditions' => array("Institution.id = {$observation} AND Institution.id IS NOT NULL AND SchoolYear.name = {$year} "),
            'recursive' => 0,
            'order' => null,
            'group' => null
        );

        // build sub-query
        $query = $dbo->buildStatement(
            $params,
            $this->CensusStudent
        );
        return $query;
    }

    public function genCSV($settings){
        $dbo = ConnectionManager::getDataSource('default'); 
        $tpl = $settings['tpl'];
//        $procId = $settings['batchProcessId'];
        $arrCount = $this->olapGetNumberOfRecordsPerObservation(intval($settings['observationId']), $settings['year']);
        $recusive = ceil($arrCount['total'] / $this->limit);
        $sql ="";
        $returnData = array('processed_records' => $this->limit, 'batch'=> 0);
        $this->prepareCSV($settings);
        for($i=0;$i<$recusive;$i++){
            $offset = ($this->limit*$i);
            $settings['offset'] = $offset;
            $sql = $this->generateRawQuery($settings);//$settings['sql'];
            try{
                $rawData = $dbo->query($sql);
            } catch (Exception $e) {
//                // Update the status for the Processed item to (-1) ERROR
                $errLog = $e->getMessage();
//                $this->Common->updateStatus($procId,'-1');
//                $this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog); 
            }
            $this->formatData($rawData);
            $this->writeCSV($rawData, $settings);
            $returnData['processed_records'] = $offset+$this->limit;
            $returnData['batch'] = $i+1;
        }
//        return array($arrCount, $recusive, $this->limit, $sql);
        if(strtolower($settings['last_batch']) == 'true'){
            $this->closeCSV();
        }
        return (isset($returnData))? $returnData: $errLog;

    }

    public function prepareCSV($settings){
        $tpl = $this->humanizeCsvTitle($settings['tpl']);
        $name = 'OpenEMIS_Report_OLAP_'.$this->Auth->user('username');//$settings['name'];
        $module = 'Olap_Reports';//$settings['module'];
        $category = 'reports';//$settings['category'];

//        $arrTpl = explode(',',$tpl);
        //array_walk($arrTpl, $this->Common->translate());
//        $line = '';
        $filename = $name/*str_replace(' ', '_', $name)*/.'.csv';
        //$path =  WWW_ROOT.DS.$module.DS;
        $path = APP.WEBROOT_DIR.DS.$category.DS.$module;
        if (!is_dir($path)) {
            mkdir($path);
        }
        $path .= DS;

        $type = ($settings['batch'] == 0)?'w+':'a+';//if first run truncate the file to 0
//        $type = 'w+';
        $this->fileFP = fopen($path.$filename, $type);

        if($settings['batch'] == 0){
            fputs ($this->fileFP, $tpl."\n");

        }


    }

    public function writeCSV($data,$settings){
        $tpl = $settings['tpl'];
        $arrTpl = explode(',',$tpl);

        //if ($batch == 0){ fputs ($this->fileFP, $tpl."\n"); }
        foreach($data as $k => $arrv){
            $line = '';
            $line .= implode(',',array_values($arrv));
            $line .= "\n";
            fputs ($this->fileFP, $line);
        }
//        $line = pr($data);
//        $line .= "\n";
//        fputs ($this->fileFP, $line);
    }

    public function closeCSV(){
        $line = "\n";
        $line .= "Report generated: " . date("Y-m-d H:i:s");
        fputs ($this->fileFP, $line);
        fclose ($this->fileFP);
    }

	private function cleanContent($str){
		$str = str_replace("'", "&#39", $str);
		return $str = str_replace("'", "&#44", $str);
	}

	private function formatData(&$data){
		
		foreach($data as $k => &$arrv){
			foreach ($arrv as $key => $value) {
				if(is_array($value)){
                    foreach($value as $innerKey => $innerValue){
                        $arrv[$key."_".$innerKey] = $innerValue;
                    }
					unset($data[$k][$key]);
				}
			}
		}
	}
	
	public function adhoc() {
		$this->addCrumb('Ad Hoc Reports');
		$this->Navigation->selected('reports', 'adhoc');
		$sql = '';
		$result = array();
		if($this->request->is('post')) {
			$model = new AppModel(false, false);
			if(isset($this->data['query'])) {
				$sql = $this->data['query'];
				if(strlen($sql) > 0) {
					try {
						$result = $model->query($sql);
						$result = $model->formatToTable($result);
					} catch(Exception $ex) {
						$sql = '' . $ex->getMessage() . "\n\n" . $sql;
					}
				}
			}
		}
		$this->set('sql', $sql);
		$this->set('result', $result);
	}

    public function humanizeFields(&$selectedFields){
        $tmpArray = $selectedFields;
        $formattedData = array();

        foreach($tmpArray as $key => $values){
            foreach($values as $innerValue){
                $strValue = trim((preg_replace('/\bid\b/i', '',Inflector::humanize($innerValue))));
                if(!empty($strValue)){
                    $formattedData[$key][$innerValue] = $strValue;
                }
            }
        }

        $selectedFields = $formattedData;
    }

    public function humanizeCsvTitle($titlesString){
        $tmpArray = explode(',', $titlesString);
        $formattedArray = array();

        foreach($tmpArray as $key => $value){
            $translatedArray = explode('.', $value);
            foreach( $translatedArray as $innerKey => $innerValue){
                $strValue = trim((preg_replace('/\bname|CensusStudent\b/i', '',Inflector::humanize($innerValue))));
                $strValue = __(Inflector::humanize(Inflector::underscore($strValue)));
                $translatedArray[$innerKey] = trim($strValue);
            }
                if(sizeof($translatedArray) > 0){//!empty($strValue)){
                    array_push($formattedArray, implode(' ', $translatedArray));
                }
        }

        return implode(',',$formattedArray);
    }
	
	
	public function reportList($report_id){
		$files = array();
		$data = $this->Report->findById($report_id);
		if(count($data) > 0){
			$files = $this->getAllGenReports($data);
		}
		
		if(count($files) == 0 ){
			$this->Utility->alert($this->Utility->getMessage('REPORT_NO_FILES'), array('type' => 'info', 'dismissOnClick' => false));
		}
		$this->set('files',$files);
	}
	
	private function getAllGenReports($data){
		$files = array();
		$this->getReportFilesPath($data);
		$dir = new Folder($this->pathFile);
		$name = str_replace(' ','_',$data['Report']['name']);
		$files = $dir->find('.*'.$name.'.*');
		$filesSet = array();
		foreach($files as &$val){
			$file = new File($dir->pwd().$val);
			$info = $file->info();
			$time = $file->lastChange();
			$info['time'] = date($this->DateTime->getConfigDateFormat()." H:i:s",$time);
			$info['size'] = $this->convFileSize($info['filesize']);
			//pr($info);
			
			$this->parseFilename($info);
			
			$info['path'] = $this->pathFile;
			$filesSet[$info['extension']][$time] = $info;	
		}
		//sort the files based on time gen DESC order
		foreach($filesSet as $key => &$val){
			krsort($filesSet[$key]);
		}
		
		return $filesSet;
	}
	
	private function parseFilename(&$info){
		$arrFilename = explode("_",$info['basename'] );
		//pr(array_shift($arrFilename));
		$info['reportId'] = array_shift($arrFilename);
		$info['batchProcessId'] = array_shift($arrFilename);
		$info['name']  = implode("_",$arrFilename);
	}
	
	private function getReportFilesPath($data){
		$module = str_replace(' ','_',$data['Report']['module']);
		$category = str_replace(' ','_',$data['Report']['category']);
		$file_type = str_replace(' ','_',($data['Report']['file_type']=='ind'?'csv':$data['Report']['file_type']));
		$this->pathFile = APP.WEBROOT_DIR.DS.'reports'.DS.$category.DS.$module.DS;
	}
	
	private function convFileSize($bytes){
		if ($bytes >= 1073741824){
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }elseif ($bytes >= 1048576){
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }elseif ($bytes >= 1024){
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }elseif ($bytes > 1){
            $bytes = $bytes . ' bytes';
        }elseif ($bytes == 1){
            $bytes = $bytes . ' byte';
        }else{
            $bytes = '0 bytes';
        }
		return $bytes;
	}
	
}
