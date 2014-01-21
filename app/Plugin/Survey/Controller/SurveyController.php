<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class SurveyController extends SurveyAppController {
    public $bodyTitle = 'Survey';
    public $headerSelected = 'Survey';
    public $limit = 1000;

    public $uses = array(
		'Area',
        'AreaEducation',
		'BatchProcess',
        'Reports.Report',
		'Reports.BatchReport',
		'Institution',
		'InstitutionStatus',
		'InstitutionProvider',
		'InstitutionSector',
		'InstitutionCustomField',
		'InstitutionCustomValue',
		'InstitutionSite',
		'InstitutionSiteProgramme',
		'InstitutionSiteType',
		'InstitutionSiteStatus',
		'InstitutionSiteLocality',
		'InstitutionSiteType',
		'InstitutionSiteOwnership',
		'InstitutionSiteCustomField',
		'InstitutionSiteCustomFieldOption',
		'InstitutionSiteCustomValue',
		'InstitutionSiteProgramme',
		'EducationProgramme',
		'EducationCertification',
		'EducationGrade',
		'EducationCycle',
        'CensusStudent',
		'CensusGraduate',
		'CensusClass',
		'CensusTextbook',
		'CensusTeacher',
		'CensusStaff',
		'CensusBuilding',
		'CensusResource',
		'CensusFurniture',
		'CensusEnergy',
		'CensusRoom',
		'CensusSanitation',
		'CensusWater',
		'InfrastructureBuilding',
		'InfrastructureResource',
		'InfrastructureFurniture',
		'InfrastructureEnergy',
		'InfrastructureRoom',
		'InfrastructureSanitation',
		'InfrastructureWater',
		'InfrastructureCategory',
		'InfrastructureMaterial',
		'InfrastructureStatus',
        'SchoolYear',
		'StudentCategory'
    );
	
	public $category = array( //parameter passed to Category Dropdown
									'Institution',
									'InstitutionSite',
									'Student',
									'Teacher',
									'Staff');
	private  $listFileLimit = 10;
    public $helpers = array('Paginator');
    public $components = array('Paginator','DateTime','Utility','Survey.JSON','Survey.SurveyCategory');
	private $pathFile = '';
    
    public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('ws_login','ws_download','ws_upload');
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
		//$this->Navigation->addCrumb('Survey', array('controller' => $this->controller, 'action' => 'index'));
	}
	
	private function getPageInfo($total,$curr_page){
		$pp = $this->listFileLimit;
		$pages = ceil($tot_rows / $pp); // calc pages

		$data = array(); // start out array
		$data['si']        = ($curr_page * $pp) - $pp; // what row to start at
		$data['pages']     = $pages;                   // add the pages
		$data['curr_page'] = $curr_page;               // Whats the current page

		return $data; //return the paging data
		
	}
	/* Index Page to show the list of json files available to use */
	public function index($page = 0,$pattern ='') {
        $this->Navigation->addCrumb('New Surveys');
		
		$year = '';
		/*if (isset($this->data['Survey']['Category']) && isset($this->data['Survey']['Type']) && isset($this->data['Survey']['Year'])){
			
			$year = $this->data['Survey']['Year'];
			$category = $this->data['Survey']['Category'];
			$siteType = $this->data['Survey']['Type'];
			$files = $this->getSurveys($year.'_'.$category.'_'.$siteType,false);
			
		}*/
		
		if (isset($this->data['Survey']['Search'])){
			$pattern = $this->data['Survey']['Search'];
			$files = $this->getSurveys($pattern,false);
		}elseif($pattern != ''){
			$files = $this->getSurveys($pattern,false);
		}else{
			$files = $this->getSurveys();
		}
		
		
		$totfiles = count($files);
		$pages = ceil($totfiles / $this->listFileLimit);
		if(count($files)>0){
			$files = array_slice($files, ($page*$this->listFileLimit) ,$this->listFileLimit,TRUE);//Paginate
		}
		
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		$sitetypes = array_combine(array_values($sitetypes), array_values($sitetypes));
		
		$yearList = $this->SchoolYear->getAvailableYears();
		$yearList = array_combine(array_values($yearList), array_values($yearList));
		$yearId = $this->getAvailableYearId($yearList,$year);
		
		$nextPage = ($page+1==$pages || $totfiles <= $this->listFileLimit )?false:$page+1;
		$prevPage = ($page==0)?false:$page-1;
		$firstPage = ($page != 0)?0:false;
		$lastPage = ($pages == ($page+1) || $totfiles <= $this->listFileLimit )? false : $pages-1;
		
		$category = array_combine(array_values($this->category), array_values($this->category));
		
		
		//if(count($files) == 0 ) $this->Utility->alert($this->Utility->getMessage('SURVEY_NO_FILES'), array('type' => 'warn'));
		$this->set('firstPage',$firstPage);
		$this->set('lastPage',$lastPage);
		$this->set('nextPage',$nextPage);
		$this->set('prevPage',$prevPage);
		$this->set('data' , $files);
		$this->set('pattern' , $pattern);
		$this->set('totalfiles' , $totfiles);
		/*$this->set('sitetypes' , $sitetypes);
		$this->set('category' , $category);
		$this->set('years' , $yearList);
		$this->set('selectedYear' , $yearId);*/
		
		//SURVEY_NO_FILES
		
	}
	//============================================================================================================================================
	//================================================== EDIT & DOWNLOAD JSON SECTION ============================================================
	//============================================================================================================================================
	private function fixData(&$arrdata){
		$topCnt = 1;
		
		foreach($arrdata as $k1 => &$value){
			$secCnt = 0;
			foreach($value as $k => $v){
				if(isset($v['order'])){
					$arrdata[$k1][$k]['order'] = ''.$secCnt;
					$secCnt++;
				}
				
				if($k!='order'){
					foreach($v as $i => $j){
						if($i=='questions'){
							$qCnt = 1;
							foreach($j as $index => $val){
								if(isset($val['order'])){
									$arrdata[$k1][$k]['questions'][$index]['order'] = ''.$qCnt;
									$qCnt++;
								}
								if($val['checked'] == 0) 
								{
									unset($arrdata[$k1][$k]['questions'][$index]);
								}
								unset($arrdata[$k1][$k]['questions'][$index]['checked']);
							}
						}
					}
				}
				
				if($k=='order'){
					if($v['checked'] == 0) 
						unset($arrdata[$k1][$k]);
					else{
						$arrdata[$k1][$k]=$v['value'];
					}
				}else{
					if($v['checked'] == 0) 
						unset($arrdata[$k1][$k]);
					else
						unset($arrdata[$k1][$k]['checked']);
				}
			}
			if(sizeof($value)<2){
				unset($arrdata[$k1]);
			}else{
				if(isset($value['order'])){
					$arrdata[$k1]['order'] = ''.$topCnt;
					$topCnt++;
				}
			}
		}
		$arrdata = array_filter($arrdata);
	}

	public function edit($name = ''){
		
		$this->Navigation->addCrumb('New Surveys', array('controller' => $this->controller, 'action' => 'index'));
		$this->Navigation->addCrumb('Edit');
		
		// Read the json file
		$data = $this->parseSurveyOEX($name);
		$data = json_decode($data,true);
		$myYear = $data["Year"]["value"];
		$myCategory = $data["Category"]["value"];
		$mySiteID = $data["SiteType"]["value"];
		
		if ($this->request->is('post')) {
			if (isset($_POST['CancelButton'])) {
				$this->redirect(array('controller' => 'Survey', 'action' => 'index'));
			}else{
				$myYear = $this->request->data['year'];
				$myCategory = $this->request->data['category'];
				$mySiteID = $this->request->data['siteTypes'];
				$name = $this->request->data['filename'];
				unset($this->request->data['SaveButton']);
				unset($this->request->data['year']);
				unset($this->request->data['category']);
				unset($this->request->data['siteTypes']);
				$details = array_shift($this->request->data);
				$this->fixData($this->request->data);
				$file = $name;
				// Fill Code,Year,Category,SiteType into Json File
				$arrCat = array('Code' => array('null'=>'','type'=>'string','label'=>$myCategory .' Code','value'=>''),
								'Year' => array('null'=>'','type'=>'string','label'=>'Year','value'=>$myYear),
							    'Category' => array('null'=>'','type'=>'string','label'=>'Category','value'=>$myCategory),
								'SiteType' => array('null'=>'','type'=>'string','label'=>'SiteType','value'=>$mySiteID)
								);
				$this->request->data = array_merge($this->request->data ,$arrCat);
				$this->JSON->prepareJSONFile($file);
				$this->JSON->createJSONFile($this->request->data);
				$this->redirect(array('plugin'=>'Survey', 'action' => 'index'));
			}
		}
		
		$catID = 0;
		foreach($this->category as $id => $val){
			if($val==$myCategory){
				$catID = $id;
			}
		}
		
		if($catID>1){
			$mySiteID = 2;
		}
		
		$arr = array('catID'=> $catID, 'siteID'=>$mySiteID);
		$arrayQuestions = $this->SurveyCategory->getCategoryQuestion($arr);
		
		$this->set('questions' , $arrayQuestions);
		$this->set('data' , $data);
		$this->set('name',$name);
		$this->set('myYear' , $myYear);
		$this->set('myCategory' , $myCategory);
		$this->set('mySiteID',$mySiteID);
	}
	
	private function parseSurveyOEX($name){		
		$json = $this->JSON->getJSONFile($name);
		
		return $json;
	}
	
	private function getSurveys($pattern = 'ALL',$import = false){
		$files = array();
		$this->getReportFilesPath($import);
		$dir = new Folder($this->pathFile);
		if($pattern == 'ALL'){
			$files = $dir->find('.*\.json');
		}else{
			$files =$dir->find('.*'.$pattern.'.*');
		}
		//pr($pattern);pr($files);
		if(count($files) == 0) return array();
		$filesSet = array();
		foreach($files as &$val){
			
			$file = new File($dir->pwd().$val);
			
			$info = $file->info();
			$time = $file->lastChange();
			
			$info['time'] = date($this->DateTime->getConfigDateFormat()." H:i:s",$time);
			$info['size'] = $this->convFileSize($info['filesize']);
			
			//pr($info);
			
			//$this->parseFilename($info);
			
			$info['path'] = $this->pathFile;
			$filesSet[$time] = $info;	
		}
		//sort the files based on time gen DESC order
		foreach($filesSet as $key => &$val){
			krsort($filesSet[$key]);
		}
		return $filesSet;
	}
	
	private function getReportFilesPath($import = false){
            $importPath = ($import === true )?'response'.DS:(($import == 'archive')?'archive'.DS:'');
            $this->pathFile = APP.WEBROOT_DIR.DS.'survey'.DS.$importPath;
	}
	
	
	public function download($filename){
        if($filename == '' ){
            die();
        }else{
			$this->getReportFilesPath();
			$info['basename'] = $filename;
            $this->viewClass = 'Media';
            // Download app/outside_webroot_dir/example.zip
            $params = array(
                'id'        => $filename,
                'name'      => $filename,
                'download'  => true,
                'extension' => '',
                //'path'      => APP . 'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$category).DS.$module.DS
                'path'		=> $this->pathFile
            );
            $this->set($params);
        }
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
	//============================================================================================================================================
	//================================================== CREATE JSON FILE SECTION ================================================================
	//============================================================================================================================================
	public function sitetypechange(){
		$this->autoRender = false;
		$siteID = $this->params->query['siteId'];
		$catID = $this->params->query['catId'];
		$arr = array('catID'=> $catID, 'siteID'=>$siteID);
		$arrayQuestions = $this->SurveyCategory->getCategoryQuestion($arr);

		$this->set('questions' , $arrayQuestions);
		$this->render('add_table');
	}
	
	public function add() {
        $this->Navigation->addCrumb('New Surveys', array('controller' => 'Survey', 'action' => 'index'));
		$this->Navigation->addCrumb('Add');
		if ($this->request->is('post')) {
			if (isset($_POST['CancelButton'])) {
				$this->redirect(array('controller' => 'Survey', 'action' => 'index'));
			}else{
				unset($this->request->data['SaveButton']);
				$details = array_shift($this->request->data);
				$this->fixData($this->request->data);
				
				$arr = $this->InstitutionSiteType->find('list',array('conditions'=>array('visible'=>1,'id'=>$details['siteTypes'])));

				if($details['filename']!=''){ // If user has own custom filename to use
					$file = $details['filename'].'.json';
				}else{ // Otherwise use default filename
					if($details['category']>1){
					$file = $details['year'].'_'.$this->category[$details['category']].'.json';
					}else{
						$file = $details['year'].'_'.$this->category[$details['category']].'_'.$arr[$details['siteTypes']].'.json';
					}
				}
				
				// Fill Code,Year,Category,SiteType into Json File
				$arrCat = array('Code' => array('null'=>'','type'=>'string','label'=>$this->category[$details['category']].' Code','value'=>''),
								'Year' => array('null'=>'','type'=>'string','label'=>'Year','value'=>$details['year']),
							    'Category' => array('null'=>'','type'=>'string','label'=>'Category','value'=>$this->category[$details['category']]),
								'SiteType' => array('null'=>'','type'=>'string','label'=>'SiteType','value'=>isset($details['siteTypes'])? $details['siteTypes']:'')
								);
				$this->request->data = array_merge($this->request->data ,$arrCat);
				$this->JSON->prepareJSONFile($file);
				$this->JSON->createJSONFile($this->request->data);
				$this->redirect(array('plugin'=>'Survey', 'action' => 'index'));
			}
		}
		//Tables
		$arr = array('catID'=> '0', 'siteID'=>'2');
		$arrayQuestions = $this->SurveyCategory->getCategoryQuestion($arr);
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		
		$year = $this->SchoolYear->getAvailableYears();
		$year = array_combine(array_values($year),array_values($year));
		$this->set('category', $this->category);
		$this->set('siteTypes',  $sitetypes);
		$this->set('year', $year);
		$this->set('questions' , $arrayQuestions);
	}
	
	private function getAvailableYearId($yearList,$yearId) {
		
		if(isset($yearId)) {
			if(!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		return $yearId;
	}
	//============================================================================================================================================
	//================================================== IMPORT JSON FILE SECTION ================================================================
	//============================================================================================================================================
	public function filter($import = false){
		$this->autoRender = false;
		$files = array();
		if (isset($this->params['url']['category']) && isset($this->params['url']['siteType']) && isset($this->params['url']['schoolYear'])){
			$files = $this->getSurveys($this->params['url']['schoolYear'].'_'.$this->params['url']['category'].'_'.$this->params['url']['siteType'],$import);
		}
		
		$this->set('data' , $files);
		$this->render('index_table');
	}
	
	public function import($page = 0,$pattern =''){
		//pr($this->data);die;
		$this->Navigation->addCrumb('Completed Surveys');
		if($_FILES){
			$this->getReportFilesPath(true);
			foreach ($this->data['Upload']['file'] as $arrVal){
				if ($arrVal['error'] === UPLOAD_ERR_OK)
				copy($arrVal['tmp_name'], $this->pathFile.$arrVal['name']);
			}
		}
		/*
		 * 
		if($this->requestAction('get')){
			$this->Utility->alert('Record have been added successfully.', array('type' => 'info'));
		}
		 */
		
		$year = '';
		
		if (isset($this->data['Survey']['Search'])){
			$pattern = $this->data['Survey']['Search'];
			$files = $this->getSurveys($pattern,true);
		}elseif($pattern != ''){
			$files = $this->getSurveys($pattern,true);
		}else{
			$files = $this->getSurveys('ALL',true);
		}
		
		
		$totfiles = count($files);
		
		
		$pages = ceil($totfiles / $this->listFileLimit);
		if(count($files)>0){
			$files = array_slice($files, ($page*$this->listFileLimit),$this->listFileLimit,TRUE);//Paginate
		}
		
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		$sitetypes = array_combine(array_values($sitetypes), array_values($sitetypes));
		
		$yearList = $this->SchoolYear->getAvailableYears();
		$yearList = array_combine(array_values($yearList), array_values($yearList));
		$yearId = $this->getAvailableYearId($yearList,$year);
		
		$nextPage = ($page+1==$pages || $totfiles <= $this->listFileLimit )?false:$page+1;
		$prevPage = ($page==0)?false:$page-1;
		$firstPage = ($page != 0)?0:false;
		$lastPage = ($pages == ($page+1) || $totfiles <= $this->listFileLimit )? false : $pages-1;
		
		$category = array_combine(array_values($this->category), array_values($this->category));
		
		
		//if(count($files) == 0 ) $this->Utility->alert($this->Utility->getMessage('SURVEY_NO_FILES'), array('type' => 'warn'));
		$this->set('firstPage',$firstPage);
		$this->set('lastPage',$lastPage);
		$this->set('nextPage',$nextPage);
		$this->set('prevPage',$prevPage);
		$this->set('data' , $files);
		$this->set('pattern' , $pattern);
		$this->set('totalfiles' , $totfiles);
		/*
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		$yearList = $this->SchoolYear->getAvailableYears();
		$yearId = $this->getAvailableYearId($yearList);
		$files = $this->getSurveys('ALL',true);
		$this->set('data' , $files);
		$this->set('sitetypes' , $sitetypes);
		$this->set('category' , $this->category);
		$this->set('years' , $yearList);
		$this->set('selectedYear' , $yearId);*/
		
		
	}
	
	public function synced($page = 0,$pattern =''){
		
		$this->Navigation->addCrumb('Completed Surveys', array('controller' => $this->controller, 'action' => 'import'));
		$this->Navigation->addCrumb('Synchronized');
		
		
		$year = '';
		
		if (isset($this->data['Survey']['Search'])){
			$pattern = $this->data['Survey']['Search'];
			$files = $this->getSurveys($pattern,'archive');
		}elseif($pattern != ''){
			$files = $this->getSurveys($pattern,'archive');
		}else{
			$files = $this->getSurveys('ALL','archive');
		}
		
		
		$totfiles = count($files);
		
		
		$pages = ceil($totfiles / $this->listFileLimit);
		if(count($files)>0){
			$files = array_slice($files, ($page*$this->listFileLimit),$this->listFileLimit,TRUE);//Paginate
		}
		
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		$sitetypes = array_combine(array_values($sitetypes), array_values($sitetypes));
		
		$yearList = $this->SchoolYear->getAvailableYears();
		$yearList = array_combine(array_values($yearList), array_values($yearList));
		$yearId = $this->getAvailableYearId($yearList,$year);
		
		$nextPage = ($page+1==$pages || $totfiles <= $this->listFileLimit )?false:$page+1;
		$prevPage = ($page==0)?false:$page-1;
		$firstPage = ($page != 0)?0:false;
		$lastPage = ($pages == ($page+1) || $totfiles <= $this->listFileLimit )? false : $pages-1;
		
		$category = array_combine(array_values($this->category), array_values($this->category));
		
		
		//if(count($files) == 0 ) $this->Utility->alert($this->Utility->getMessage('SURVEY_NO_FILES'), array('type' => 'warn'));
		$this->set('firstPage',$firstPage);
		$this->set('lastPage',$lastPage);
		$this->set('nextPage',$nextPage);
		$this->set('prevPage',$prevPage);
		$this->set('data' , $files);
		$this->set('pattern' , $pattern);
		$this->set('totalfiles' , $totfiles);
		/*
		
		$sitetypes = $this->InstitutionSiteType->getSiteTypesList();
		$yearList = $this->SchoolYear->getAvailableYears();
		$yearId = $this->getAvailableYearId($yearList);
		$files = $this->getSurveys('ALL',true);
		$this->set('data' , $files);
		$this->set('sitetypes' , $sitetypes);
		$this->set('category' , $this->category);
		$this->set('years' , $yearList);
		$this->set('selectedYear' , $yearId);*/
		
		
	}
	//============================================================================================================================================
	//========================================= PROCESS JSON FILE BACK TO DATABASE SECTION =======================================================
	//============================================================================================================================================
    public function formatsavetable($arr){
        $secName = $arr['secName'];
        $topicName = $arr['topicName'];
        $code = $arr['code'];
        $arrData = $arr['arrData'];
        $objTable = ClassRegistry::init($secName);
        $schema = $objTable->schema();

        $arrCond = array();
        switch($secName){
            case 'InstitutionCustomField':
                $secName = 'InstitutionCustomValue';
                $objTable = ClassRegistry::init($secName);
                $schema = $objTable->schema();
                $institution_id = Set::flatten($this->{'Institution'}->query('SELECT `id` FROM `institutions` where `code`=\''.$code.'\''));
                $institution_id = $institution_id[key($institution_id)];
                foreach($arrData[$topicName]['InstitutionCustomField']['questions'] as $qName => $qVal){
                    $institution_custom_field_id = Set::flatten($this->{'InstitutionCustomField'}->query('SELECT `id` FROM `institution_custom_fields`
																							 			  where `name` LIKE \''.$qName.'\''));
                    $institution_custom_field_id = $institution_custom_field_id[key($institution_custom_field_id)];
                    $answer = explode(',',$qVal['value']);
                    $arrExist = '';
                    foreach($answer as $val){
                        $arrExist = $this->$secName->find('first', array('fields' => array('id'),
                            'conditions' => array('value' => $val,
                                'institution_custom_field_id' => $institution_custom_field_id,
                                'institution_id' => $institution_id
                            )));

                        if(!is_array($arrExist)){
                            $arr['InstitutionCustomValue'] = array('value'=>$val,
                                'institution_custom_field_id' => $institution_custom_field_id,
                                'institution_id' => $institution_id);
                            $objTable->saveAll($arr);
                        }
                    }
                }
                break;

            case 'InstitutionSiteCustomField':
                $secName = 'InstitutionSiteCustomValue';
                $objTable = ClassRegistry::init($secName);
                $schema = $objTable->schema();
                $institution_site_id = Set::flatten($this->{'InstitutionSite'}->query('SELECT `id` FROM `institution_sites` where `code`=\''.$code.'\''));
                if(sizeof($institution_site_id)>0){
                    $institution_site_id = $institution_site_id[key($institution_site_id)];
                }
                foreach($arrData[$topicName]['InstitutionSiteCustomField']['questions'] as $qName => $qVal){
                    $institution_site_custom_field_id = Set::flatten($this->{'InstitutionSiteCustomField'}->query('SELECT `id` FROM `institution_site_custom_fields`
																							 			  where `name` LIKE \''.$qName.'\''));
                    if(count($institution_site_custom_field_id)>0){
                        $institution_site_custom_field_id = $institution_site_custom_field_id[key($institution_site_custom_field_id)];
                        $answer = explode(',',$qVal['value']);
                        $arrExist = '';
                        foreach($answer as $val){
                            $arrExist = $this->$secName->find('first', array('fields' => array('id'),
                                'conditions' => array('value' => $val,
                                    'institution_site_custom_field_id' => $institution_site_custom_field_id,
                                    'institution_site_id' => $institution_site_id
                                )));

                            if(!is_array($arrExist)){
                                $arr['InstitutionSiteCustomValue'] = array('value'=>$val,
                                    'institution_site_custom_field_id' => $institution_site_custom_field_id,
                                    'institution_site_id' => $institution_site_id);
                                $objTable->saveAll($arr);
                            }
                        }
                    }
                }
                break;

            default:
                foreach($schema as $colname => $arrProp){
                    if(isset($data[$topicName][$secName]['questions'][$colname]['items'])){
                        $arrCond[$colname] = array_search($arrData[$topicName][$secName]['questions'][$colname]['value'],$arrData[$topicName][$secName]['questions'][$colname]['items']);
                    }elseif(isset($arrData[$topicName][$secName]['questions'][$colname])){
                        $arrCond[$colname] = $arrData[$topicName][$secName]['questions'][$colname]['value'];
                    }else{
                        if(!$arrProp['null']){
                            if($arrProp['type'] =='integer')
                                $arrCond[$colname] = "1";
                            elseif($arrProp['type'] =='datetime')
                                $arrCond[$colname] = date("Y-m-d H:i:s");
                            elseif($arrProp['type'] =='date')
                                $arrCond[$colname] =  date("Y-m-d");
                            elseif($arrProp['type'] =='string' || $arrProp['type'] =='text')
                                $arrCond[$colname] = "_";
                        }
                    }
                }

                if($secName == 'Institution'){
                    $institution_id = Set::flatten($this->{'Institution'}->query('SELECT `id` FROM `institutions` where `code`=\''.$code.'\''));
                    if(sizeof($institution_id)>0){
                        $institution_id = $institution_id[key($institution_id)];
                    }
                    $arrCond['code'] = $code;
                    if(isset($institution_id)){
                        $arrCond['id'] = $institution_id;
                        //unset($arrCond['code']);
                        $arrCond['code'] = $code;
                    }else{
                        $arrCond['code'] = $code;
                        unset($arrCond['id']);
                    }
                }

                if($secName == 'InstitutionSite'){
                    $institution_site_id = Set::flatten($this->{'InstitutionSite'}->query('SELECT `id` FROM `institution_sites` where `code`=\''.$code.'\''));
                    if(sizeof($institution_site_id)>0){
                        $institution_site_id = $institution_site_id[key($institution_site_id)];
                    }
                    $arrCond['code'] = $code;
                    if(isset($institution_site_id)){
                        $arrCond['id'] = $institution_site_id;
                        //unset($arrCond['code']);
                        $arrCond['code'] = $code;
                    }else{
                        $arrCond['code'] = $code;
                        unset($arrCond['id']);
                    }
                }

                if(!$objTable->saveAll(array($secName =>$arrCond))){
                    // Validation Errors
                    //debug($objTable->validationErrors);
                    //die;
                }
                break;
        }
    }

    public function responsefile(){
		$filename = $_GET['file'];
		$arrFiles = explode(',',$filename);
		
		if(is_array($arrFiles) && count($arrFiles) > 0){
			foreach($arrFiles as $filename){
				$this->getReportFilesPath(true);
				$this->JSON->setPath($this->pathFile);
				$p = $this->parseSurveyOEX($filename);
				$filenameArr = explode('_',rtrim($filename, ".json"));
				$code = $filenameArr[count($filenameArr)-1];
				$arrData = json_decode($p,true);
				
				$arrCond = array();
				
				foreach($arrData as $topicName => $topicVal){
					if($topicName == 'Code' || $topicName == 'Year' || $topicName == 'Category' || $topicName == 'SiteType'){ continue; }
					foreach($topicVal as $secName => $secVal){
						if(strtolower($secName) == 'order'){ continue; }
						foreach($secVal as $catName => $catVal){
							if(strtolower($catName)=='type'){
								if(strtolower($catVal)=='single'){
									// Normal Table save
									$arr = array('topicName' => $topicName, 'secName' => $secName, 'code' => $code, 'arrData' => $arrData);
									$this->formatsavetable($arr);
								}else{
									//Skip Infrastructure first
									if($secName!='Infrastructure'){
										// Census Table save
										$objTable = ClassRegistry::init($secName);
										$schema = $objTable->schema();
										$institution_site_id = Set::flatten($this->{'InstitutionSite'}->query('SELECT `id` FROM `institution_sites` where `code`=\''.$code.'\''));
										if(sizeof($institution_site_id)>0){
											$institution_site_id = $institution_site_id[key($institution_site_id)];
										}
										$arr = '';
										
										switch ($secName){
											case 'CensusStudent':
												// Declare my foreign/association tables
												$objAssociationTable = ClassRegistry::init('InstitutionSiteProgramme');
												$arrLink = '';
												if(is_array($arrData[$topicName][$secName]['value'])){
													foreach($arrData[$topicName][$secName]['value'] as $inpVal){
														$cnt=0;
														foreach($arrData[$topicName][$secName]['questions'] as $name => $val){
															$arr[$secName][$name] = $inpVal[$cnt];
															$cnt++;
														}
														$arr[$secName]['institution_site_id'] = $institution_site_id;
														// Rule Business Logic Validation here
														// -------> 1) CensusStudent requires programmes association
														
														$existID = Set::flatten($this->{$secName}->query('SELECT `census_students`.`id` 
																				FROM  `census_students`
																				WHERE `census_students`.`institution_site_id`= \''.$arr[$secName]['institution_site_id'].'\' 
																				AND	  `census_students`.`school_year_id`= \''.$arr[$secName]['school_year_id'].'\'
																				AND	  `census_students`.`education_grade_id`= \''.$arr[$secName]['education_grade_id'].'\'
																				AND	  `census_students`.`student_category_id`= \''.$arr[$secName]['student_category_id'].'\'
																				AND	  `census_students`.`age`= \''.$arr[$secName]['age'].'\'
																				AND	  `census_students`.`male`= \''.$arr[$secName]['male'].'\'
																				AND	  `census_students`.`female`= \''.$arr[$secName]['female'].'\'
																										  '));
														if(count($existID)<1){
															$objTable->saveAll($arr);
															$existID = Set::flatten($this->{$secName}->query('SELECT `institution_site_programmes`.`id` 
																				FROM  `institution_site_programmes`
																				WHERE `institution_site_programmes`.`institution_site_id`= \''.$arr[$secName]['institution_site_id'].'\' 
																				AND	  `institution_site_programmes`.`education_programme_id`= \''.$arr[$secName]['education_programme_id'].'\'
																				AND	  `institution_site_programmes`.`school_year_id`= \''.$arr[$secName]['school_year_id'].'\'
																										  '));
															if(count($existID)<1){
																$arrLink['InstitutionSiteProgramme']['institution_site_id'] = $arr[$secName]['institution_site_id'];
																$arrLink['InstitutionSiteProgramme']['education_programme_id'] = $arr[$secName]['education_programme_id'];
																$arrLink['InstitutionSiteProgramme']['school_year_id'] = $arr[$secName]['school_year_id'];
																$objAssociationTable->saveAll($arrLink);
															}
														}
													}
												}
												break;
												
											case 'CensusGraduate':
												// Declare my foreign/association tables
												$objAssociationTable = ClassRegistry::init('InstitutionSiteProgramme');
												$arrLink = '';
												if(is_array($arrData[$topicName][$secName]['value'])){
													foreach($arrData[$topicName][$secName]['value'] as $inpVal){
														$cnt=0;
														foreach($arrData[$topicName][$secName]['questions'] as $name => $val){
															$arr[$secName][$name] = $inpVal[$cnt];
															$cnt++;
														}
														$arr[$secName]['institution_site_id'] = $institution_site_id;
														// Rule Business Logic Validation here
														// -------> 1) CensusStudent requires programmes association
														
														$existID = Set::flatten($this->{$secName}->query('SELECT `census_students`.`id` 
																				FROM  `census_students`
																				WHERE `census_students`.`institution_site_id`= \''.$arr[$secName]['institution_site_id'].'\' 
																				AND	  `census_students`.`school_year_id`= \''.$arr[$secName]['school_year_id'].'\'
																				AND	  `census_students`.`education_grade_id`= \''.$arr[$secName]['education_grade_id'].'\'
																				AND	  `census_students`.`student_category_id`= \''.$arr[$secName]['student_category_id'].'\'
																				AND	  `census_students`.`age`= \''.$arr[$secName]['age'].'\'
																				AND	  `census_students`.`male`= \''.$arr[$secName]['male'].'\'
																				AND	  `census_students`.`female`= \''.$arr[$secName]['female'].'\'
																										  '));
														if(count($existID)<1){
															$objTable->saveAll($arr);
															$existID = Set::flatten($this->{$secName}->query('SELECT `institution_site_programmes`.`id` 
																				FROM  `institution_site_programmes`
																				WHERE `institution_site_programmes`.`institution_site_id`= \''.$arr[$secName]['institution_site_id'].'\' 
																				AND	  `institution_site_programmes`.`education_programme_id`= \''.$arr[$secName]['education_programme_id'].'\'
																				AND	  `institution_site_programmes`.`school_year_id`= \''.$arr[$secName]['school_year_id'].'\'
																										  '));
															if(count($existID)<1){
																$arrLink['InstitutionSiteProgramme']['institution_site_id'] = $arr[$secName]['institution_site_id'];
																$arrLink['InstitutionSiteProgramme']['education_programme_id'] = $arr[$secName]['education_programme_id'];
																$arrLink['InstitutionSiteProgramme']['school_year_id'] = $arr[$secName]['school_year_id'];
																$objAssociationTable->saveAll($arrLink);
															}
														}
													}
												}
												break;
													
											case 'CensusClass':
												// Declare my foreign/association tables
												$objAssociationTable = ClassRegistry::init('CensusClassGrade');
												$arrLink = '';
												// Insert values into the tables
												if(is_array($arrData[$topicName][$secName]['value'])){
													foreach($arrData[$topicName][$secName]['value'] as $inpVal){
														$cnt=0;
														foreach($arrData[$topicName][$secName]['questions'] as $name => $val){
															$arr[$secName][$name] = $inpVal[$cnt];
															$cnt++;
														}
														$arr[$secName]['institution_site_id'] = $institution_site_id;
														// Rule Business Logic Validation here
														// -------> 1) CensusStudent requires programmes association
														$existID = Set::flatten($this->{$secName}->query('SELECT `census_classes`.`id` 
																				FROM  `census_classes`, `census_class_grades`
																				WHERE `census_classes`.`institution_site_id`= \''.$arr[$secName]['institution_site_id'].'\' 
																				AND	  `census_classes`.`school_year_id`= \''.$arr[$secName]['school_year_id'].'\'
																				AND	  `census_classes`.`classes`= \''.$arr[$secName]['classes'].'\'
																				AND	  `census_classes`.`seats`= \''.$arr[$secName]['seats'].'\'
																				AND	  `census_classes`.`seats`= \''.$arr[$secName]['seats'].'\'
																				AND	  `census_classes`.`id` = `census_class_grades`.`census_class_id`
																				AND	  `census_class_grades`.`education_grade_id`= \''.$arr[$secName]['education_grade_id'].'\'
																										  '));
														if(count($existID)<1){
															$objTable->saveAll($arr);
															$arrLink['CensusClassGrade']['census_class_id'] = $objTable->inserted_ids[key($objTable->inserted_ids)];
															$arrLink['CensusClassGrade']['education_grade_id'] = $arr[$secName]['education_grade_id'];
															$objAssociationTable->saveAll($arrLink);
														}
													}
												}
												break;
												
											default:
												// Insert values into the tables
												if(is_array($arrData[$topicName][$secName]['value'])){
													foreach($arrData[$topicName][$secName]['value'] as $inpVal){
														$cnt=0;
														foreach($arrData[$topicName][$secName]['questions'] as $name => $val){
															$arr[$secName][$name] = $inpVal[$cnt];
															$cnt++;
														}
														$arr[$secName]['institution_site_id'] = $institution_site_id;
														// Rule Business Logic Validation here
														// -------> 1) CensusStudent requires programmes association
														//$objTable->saveAll($arr);
													}
												}
												break;
										}
									}
								}
							}
						}
					}
				}
				$dir = new Folder('survey/response', true);
				$file = new File($dir->path . DS . $filename);

				if ($file->exists()) {
					$dir = new Folder('survey/archive', true);
					$file->copy($dir->path . DS . $file->name);
					$file->delete();
				}
			}
		}
		// Clear Sessions
		$this->Session->delete('InstitutionId');
		$this->Utility->alert(sprintf(__("Files synced successfully."), 'Record'));
		$this->redirect(array('controller' => 'Survey', 'action' => 'import'));
	}
	//============================================================================================================================================
	//================================================ DELETE JSON FILE FROM THE SERVER ==========================================================
	//============================================================================================================================================
	public function delete($cat = ''){
                
		$filename = $_GET['file'];
                $arrFiles = explode(',',$filename);
                
                if(is_array($arrFiles) && count($arrFiles) > 0){
                    foreach($arrFiles as $filename){
                        $file = new File(APP.WEBROOT_DIR.DS.'survey'.'/'.($cat!=''?$cat.'/':'').$filename, false, 0777);
                        $file->delete();
                    }
                }
                if($cat == 'response') $Redirect = 'import';
                elseif ($cat == 'archive') $Redirect = 'synced';
                else $Redirect = 'index';
		$this->redirect(array('controller' => 'Survey', 'action' => $Redirect));
	}
	//============================================================================================================================================
	//================================================== WEB SERVICES SECTION ====================================================================
	//============================================================================================================================================
	public function ws_login(){
		$this->autoRender = false;
        if ($this->request->is('post')) {
	        if ($this->Auth->login()) {
	             $files = $this->getSurveys();
				 echo json_encode($files);
	        } else {
	             echo "false";
	        }
	    }
    }
	
	public function ws_download($file = ''){
		if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                $this->getReportFilesPath();
                $this->download($file);
            } else {
                echo "false";
            }
        }
	}

	public function ws_upload(){
        if ($this->request->is('post')) {
                if ($this->Auth->login()) {
                    $this->getReportFilesPath(TRUE);
                    if(isset($_FILES['myfile'])){
                        move_uploaded_file($_FILES['myfile']['tmp_name'], $this->pathFile.$_FILES['myfile']['name']);
                        echo "true";
                    }
                }else {
                echo "false";
            }
        }
	}
	
	public function ws_logout(){
		$this->autoRender = false;
		$this->Auth->logout();
		echo "logout";
	}
	
	public function paginateME(){
		$files = $this->getSurveys();
		$o = array_slice($files,0,2);
		pr($o);	
	}

	  
}
