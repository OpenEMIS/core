<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('Sanitize', 'Utility');
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
//		'Indicator'=>array('enable'=>true),
		'DataQuality'=>array('enable'=>true),
		'Custom'=>array('enable'=>true));
    );

    public $customView = array( //exclude from Index view.
        'Indicator',
        'Custom'
    );
	
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
			}elseif(!in_array(ucfirst($this->action), $this->customView)){
				$this->render('index');
			}
		} else if(strrpos($this->action, 'Download')!==false) {
			if(isset($this->params['pass'][0])) {
				$file = $this->params['pass'][0];
				$this->download($file);
			} else {
				$this->redirect(array('action' => str_replace('Download', '', $this->action)));
			}
		}
    }
	
	public function index() {
		$this->redirect(array('controller' => $this->params['controller'], 'action' => 'Institution'));
	}
	
	public function Institution(){}
	public function InstitutionDownload(){}
	public function Student(){}
	public function StudentDownload(){}
	public function Staff(){}
	public function StaffDownload(){}
	public function Teacher(){}
	public function TeacherDownload(){}
	public function Consolidated(){}
	public function ConsolidatedDownload(){}
//	public function Indicator(){}
//	public function IndicatorDownload(){}
	public function Custom(){
        $path = Configure::read('xml.indicators.custom.path');
//        $path = App::pluginPath($this->name) . 'webroot' . DS . 'reports';
        $type = 'custom';
        $data = array();
        $datasource = ConnectionManager::getDataSource('default');
//        var_dump($resultSet);
//        echo Sanitize::escape("SELECT * FROM `batch_indicators` WHERE `type` = 'custom';");

        if($this->request->is('post')){
            $name = Sanitize::escape($this->request->data['name']);
            $description = Sanitize::escape($this->request->data['description']);
            $file = $this->request->data['doc_file'];
//            var_dump($this->request->data);die();
            switch (strtolower($this->request->data['mode'])){
                case 'add':
                    if($this->validateFileFormat($file)){
                        $this->set('status', $this->customSave($name, $description, $file, $type, $datasource));
                    }else{
                        $this->set('status', array('msg' => __('Only XML file are allow.'), 'type' => 0));
                    }
                    break;
                case 'edit':
                    $id = Sanitize::escape($this->request->data['id']);
//                    var_dump($file);die();
                    if($file['size'] > 0){
                        if($this->validateFileFormat($file)){
                            $this->set('status', $this->editCustomReport($id, $name, $description, $file, $datasource));
                        }else{
                            $this->set('status', array('msg' => __('Only XML file are allow.'), 'type' => 0));
                        }
                    }else{
                        $this->set('status', $this->editCustomReport($id, $name, $description, $file, $datasource));
                    }
                    break;
            }

        }
//        $this->set('msg', __('Post is disabled.'));
        try{
            // Read 1st ten custom report
            $sql = sprintf("SELECT `id`, `name`, `metadata`, `filename`, `enabled` FROM `batch_indicators` WHERE `type` = '%s';",
                Sanitize::escape($type));
            $resultSet = $datasource->query($sql);
        }catch(Exception $e){
            $resultSet = array();
        }

        foreach($resultSet as $result) {
            $tmp = array_pop($result);
            $tmp['name'] = nl2br($tmp['name']);
//            $tmp['metadata'] = nl2br($tmp['metadata']);
            array_push($data, $tmp);
        }

        $this->set('data', $data);
        $this->set('setting', array('maxFilesize' => Configure::read('xml.indicators.custom.size')));

    }

    private function customSave($name, $description, $file, $type, $datasource){
        $path = Configure::read('xml.indicators.custom.path');
        $schemasPath = Configure::read('xml.schemas.path');
        $status = array('msg' => __('Upload Unsuccessful'), 'type' => 0);
        $newFile = $this->saveFile($file['tmp_name'], $path);

        if($this->validateXml($file['tmp_name'], $schemasPath.'indicator.xsd')){

            if($newFile && get_class($newFile) == 'File'){

                try{
                    // add to reports table
                    $sql = sprintf("INSERT INTO `reports` (`name`, `description`, `file_type`, `module`, `category`, `created_user_id`, `created`) VALUES ('%s', '%s', '%s', '%s', '%s', '%d', '%s');",
                        Sanitize::escape($name),
                        Sanitize::escape($description),
                        Sanitize::escape('cus'),
                        Sanitize::escape('Custom'),
                        Sanitize::escape('Custom Reports'),
                        $this->Auth->user('id'),
                        $this->DateTime->dateAsSql(time()));
                    //                    echo $sql;
                    $datasource->query($sql);

                    // add to batch_indicators table
                    $sql = sprintf("INSERT INTO `batch_indicators` (`name`, `short_name`, `metadata`, `filename`, `type`, `report_id`, `created_user_id`, `created`) VALUES ('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s');",
                        Sanitize::escape($name),
                        Sanitize::escape($name),
                        Sanitize::escape($description),
                        Sanitize::escape("{$newFile->name()}.{$newFile->ext()}"),
                        Sanitize::escape($type),
                        $datasource->lastInsertId(),
                        $this->Auth->user('id'),
                        $this->DateTime->dateAsSql(time()));
                    //                    echo $sql;
                    $datasource->query($sql);

                }catch (Exception $e){
//                    echo '<pre>';
//                    var_dump($e);
//                    echo '</pre>';
                    $status['msg'] = __("Internal Error.");
                    $status['type'] = 0;
                }


                $status['msg'] =__('Upload Successful.');
                $status['type'] = 1;
            }else{
    //            $this->set('status', array('msg' => __('Upload Successful.'), 'type' => 1));
            }
        }else{
            $status['msg'] =__('Invalid Xml Format.');
            $status['type'] = 0;
        }

        return $status;
    }

    private function editCustomReport($id, $name, $description, $file, $datasource){
        $path = Configure::read('xml.indicators.custom.path');
        $schemasPath = Configure::read('xml.schemas.path');
        $status = array('msg' => __('Upload Unsuccessful'), 'type' => 0);

        $sql = sprintf("Select `filename`, `report_id` FROM `batch_indicators` WHERE `id` = %s;",
            Sanitize::escape($id));
        $resultSet = $datasource->query($sql);
        $filename = '';
        $reportId = '';
        $newFilename = '';

        if(count($resultSet) > 0 && count($resultSet) < 2){
            $result = array_pop($resultSet);
            $filename = $result['batch_indicators']['filename'];
            $reportId = $result['batch_indicators']['report_id'];
        }

        $tmpPathEmpty = empty($file['tmp_name']);
        $validFileFormat = false;
        $validXml = false;

        if(!$tmpPathEmpty){
            $validFileFormat = $this->validateFileFormat($file);
        }

        if(!$tmpPathEmpty && $validFileFormat){
            $validXml = $this->validateXml($file['tmp_name'],$schemasPath.'indicator.xsd');
        }

        if(!$tmpPathEmpty && $validFileFormat && !$validXml){
            $status['msg'] =__('Invalid Xml Format.');
            $status['type'] = 0;
        }elseif(!$tmpPathEmpty && !$validFileFormat){
            $status['msg'] =__('Only XML file are allow.');
            $status['type'] = 0;

        }else{
            if(!$tmpPathEmpty){
                $this->removeFile($filename, $path);
            }

            $newFile = $this->saveFile($file['tmp_name'], $path);
            $newFilename = ($newFile)? "{$newFile->name()}.{$newFile->ext()}": '';
            // add to batch_indicators table
            $sql = sprintf("UPDATE `batch_indicators` SET `name`= '%s', `short_name` = '%s', `metadata` = '%s', `filename` = '%s', `modified_user_id` = '%s', `modified` = '%s' WHERE `id` = %s;",
                Sanitize::escape($name),
                Sanitize::escape($name),
                Sanitize::escape($description),
                Sanitize::escape(empty($newFilename)?$filename:$newFilename),
                $this->Auth->user('id'),
                $this->DateTime->dateAsSql(time()),
                Sanitize::escape($id));
//                    echo $sql;
            $datasource->query($sql);

            // add to reports table
            $sql = sprintf("UPDATE `reports` SET `name` = '%s', `description` = '%s' , `modified_user_id` = '%s', `modified` = '%s' WHERE `id` = %s;",
                Sanitize::escape($name),
                Sanitize::escape($description),
                $this->Auth->user('id'),
                $this->DateTime->dateAsSql(time()),
                Sanitize::escape($reportId));
//                    echo $sql;
            $datasource->query($sql);

            $status['msg'] =__('Edit Successful.');
            $status['type'] = 1;
        }

        return $status;

    }

    private function deleteCustomReport($id, $datasource){ // not tested
        $path = Configure::read('xml.indicators.custom.path');
        $status = array('msg' => __('Upload Unsuccessful'), 'type' => 0);

        $sql = sprintf("Select `filename` FROM `batch_indicators` WHERE `id` = %s;",
            Sanitize::escape($id));
        $resultSet = $datasource->query($sql);
        $filename = '';

        if(count($resultSet) > 0 && count($resultSet) < 2){
            $result = array_pop($resultSet);
            $filename = $result['batch_indicators']['filename'];
        }


        if(!empty($filename) && $this->removeFile($filename, $path)){
            // add to batch_indicators table
            $sql = sprintf("DELETE FROM `batch_indicators` WHERE `id` = %s;",
                Sanitize::escape($id));
//                    echo $sql;
            $datasource->query($sql);

            // add to reports table
            $sql = sprintf("DELETE FROM `reports` WHERE `id` = %s;",
                Sanitize::escape($id));
//                    echo $sql;
            $datasource->query($sql);

            $status['msg'] =__('Successful delete.');
            $status['type'] = 1;
        }else{
            $status['msg'] =__('Unsuccessful deletion.');
            $status['type'] = 0;
        }
        return $status;
    }

    private function validateCustomReport(){

    }

    private function validateFileFormat($file){

        $isValid = false;
        if($file['type'] == 'text/xml'){
            $isValid = true;
        }

        return $isValid;
    }

    private function validateXml($filePath, $schemaPath){

        $isValid = false;
        $dom = new DOMDocument;

        @$dom->Load($filePath);

        if (@$dom->schemaValidate($schemaPath)) {
            $isValid = true;
        }

        return $isValid;
    }

    private function saveFile($FilePath, $path){
        $file = new File($FilePath);
        $newFilename = 'user_'.time().'.xml';
        $fullpath = $path.DS.$newFilename;

        return (fileExistsInPath($FilePath) && $file->copy($fullpath))? new File($fullpath) :false;
    }

    private function removeFile($filename, $path){
        $currentFile = new File($path.DS.$filename);
        return $currentFile->delete();
    }

	public function CustomDownload($id){
        $path = Configure::read('xml.indicators.custom.path');
        try{
            $datasource = ConnectionManager::getDataSource('default');
            // Read 1st ten custom report
            $sql = sprintf("SELECT `id`, `name`, `filename` FROM `batch_indicators` WHERE `id` = '%s' LIMIT 1;",
                Sanitize::escape($id));
            $resultSet = $datasource->query($sql);
        }catch(Exception $e){
            $resultSet = array();
        }
        $filename = $resultSet[0]['batch_indicators']['filename'];
        $name = Sanitize::clean($resultSet[0]['batch_indicators']['name']);
        $name = str_ireplace(' ', '_', $name);
        $file = new File($path.$filename);
        if($file->exists()){
            $ext = $file->ext();

            $this->viewClass = 'Media';
            // Download app/outside_webroot_dir/example.zip
            $params = array(
                'id'        => $filename,
                'name'      => $name,
                'download'  => true,
                'extension' => $ext,
                'path'      => $path
            );
            $this->set($params);
        }

//        $this->controller->autoRender= false;
//        $file = $this->model->findById($id);
//        $fileext = pathinfo($file[$this->model->alias]['file_name'], PATHINFO_EXTENSION);
//        $filename = pathinfo($file[$this->model->alias]['file_name'], PATHINFO_FILENAME);
//        $filenameOut = ($file[$this->model->alias]['name'] == '' ? $filename : $file[$this->model->alias]['name']);
//        header('Content-type: application/octet-stream');
//        header("Content-Transfer-Encoding: binary");
//        //header('Content-length: ' . $file[$this->model->alias]['blobsize']);
//        //header('Content-length: ' . mb_strlen($file[$this->model->alias]['file_content']));//$file[$this->model->alias]['blobsize']);
//        header('Content-Disposition: attachment;  filename='.str_replace(" ","_",$filenameOut).'.'.$fileext);
//        echo $file[$this->model->alias]['file_content'];
    }
	public function DataQuality(){}
	public function DataQualityDownload(){}
	
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
		$data = $this->Report->find('all',array('conditions'=>array('Report.visible' => 1, 'category'=>$reportType.' Reports'), 'order' => array('Report.order')));
		
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
	}

    public function Indicator($indicatorId = ''){
//            $this->autoRender = false;
        App::uses('IndicatorReport', 'Lib/IndicatorReport');

        $this->Navigation->addCrumb('Indicator Reports');

        $exportFormat = array(
            array(
                'value'=>'csv',
                'selected' => false
            ),
            array(
                'value'=>'sdmx',
                'selected' => false
            )
        );

        if($this->Session->check('Report.Indicator.error')) {
            $this->set('alert', $this->Session->read('Report.Indicator.error'));
            $this->Session->delete('Report.Indicator.error');
        }

        $indicatorReportObj = new IndicatorReport($this->BatchProcess);

        if($this->request->is('post')){
            $this->autoRender = false;
            $userInput = $this->sanitizeIndicatorInput();
            echo $indicatorReportObj->create($userInput['Sdmx']['indicator'], implode(',', $userInput['Sdmx']['areas']), implode(',',$userInput['Sdmx']['timeperiods']));
        }else{
            $data['indicators'] = $indicatorReportObj->getIndicatorList();
            if(empty($indicatorId) AND sizeof($data['indicators']) > 0){
                reset($data['indicators']);
                $tmpIndicator = current($data['indicators']);
                $indicatorId = $tmpIndicator['Indicator_Nid'];
            }
            $data['areas'] = $indicatorReportObj->getAreaList($indicatorId);
            $data['timeperiods'] = $indicatorReportObj->getTimePeriod($indicatorId);

            if($this->Session->check('Report.Indicator.userInput')){
                $userInput = $this->Session->read('Report.Indicator.userInput');
                $this->set('selectedAreas', $userInput['areas']);
                $this->set('selectedTimeperiods', $userInput['timeperiods']);
                foreach($exportFormat as &$format){
                    if(strcmp($format['value'], $userInput['format']) == 0){
                        $format['selected']=true;
                        break;
                    }
                }
                $this->Session->delete('Report.Indicator.userInput');
            }else{
                $this->set('selectedAreas', array());
                $this->set('selectedTimeperiods', array());

            }

            $this->set('selectedIndicator', $indicatorId);
            $this->set('indicators', $data['indicators']);
            $this->set('formats', $exportFormat);
            $this->set('areas', $data['areas']);
            $this->set('timeperiods', $data['timeperiods']);

        }
    }

    private function sanitizeIndicatorInput(){
        #init variables
        $data = array();
        $areas = array();
        $timepreiods = array();

        $data['Sdmx']['indicator'] = Sanitize::clean($this->data['Sdmx']['indicator']);
        $data['Sdmx']['format'] = Sanitize::clean($this->data['Sdmx']['format']);
        foreach($this->data['Sdmx']['areas'] as $area){
            array_push($areas, Sanitize::clean($area));
        }

        foreach($this->data['Sdmx']['timeperiods'] as $timepreiod){
            array_push($timepreiods, Sanitize::clean($timepreiod));
        }
        $data['Sdmx']['areas'] = $areas;
        $data['Sdmx']['timeperiods'] = $timepreiods;

        return $data;
    }

    public function DownloadIndicator(){
        $this->autoRender = false;
        App::uses('IndicatorReport', 'Lib/IndicatorReport');
        try{
            $indicatorReportObj = new IndicatorReport($this->BatchProcess);
            $userInput = $this->sanitizeIndicatorInput();

            echo $indicatorReportObj->create( $userInput['Sdmx']['indicator'], implode(',', $userInput['Sdmx']['areas']), implode(',',$userInput['Sdmx']['timeperiods']), $userInput['Sdmx']['format']);
        }catch(Exception $e){
            $this->Session->write('Report.Indicator.error', $e->getMessage());
            $this->Session->write('Report.Indicator.userInput', $this->data['Sdmx']);
            $this->redirect(array('controller' => 'Reports', 'action' => 'Indicator', $this->data['Sdmx']['indicator']));
        }

    }

    public function olap(){
//        $this->autoRender = false;

        $this->Navigation->addCrumb('OLAP Report');
        //$this->Navigation->selected('reports', strtolower($reportType));

        $selectedFields = array(
            'Area' => array('name'),
            'Institution' => array(
                'name', 'code'/*, 'address', 'postal_code', 'contact_person', 'telephone',
                'fax', 'email', 'website', 'date_opened', 'date_closed'*/
            ),
            'InstitutionSector' => array('name'),
            'InstitutionProvider' => array('name'),
            'InstitutionStatus' => array('name'),
            'InstitutionSite' => array(
                'name', 'code'/*, 'address', 'postal_code', 'contact_person', 'telephone',
                'fax', 'email', 'website', 'date_opened', 'date_closed', 'longitude', 'latitude'*/
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
        $raw_school_years = $this->SchoolYear->find('list', array('order'=>'SchoolYear.name asc'));
        $school_years = array();
        foreach($raw_school_years as $value){
            array_push($school_years, $value);

        }

        $this->set('hideTableColumnsLabel', $this->hideOlapTableColumnsLabel);

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
                'limit' => 50 // for debugging
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
                'EducationProgramme' => array('name'),
                'SchoolYear' => array('name'),
                'EducationGrade' => array('name'),
                'StudentCategory' => array('name'),
                'CensusStudent' => array('age', 'male', 'female'/*, 'institution_site_programme_id'*/),
            );
            $fields = array();
            foreach($this->data['variables'] as $value){
                array_push($fields,$value);
            }
            foreach($selectedFields as $key => $value) {
                foreach($value as $field){
                    array_push($fields, $key.".".$field);

                }
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
            $this->formatOlapData($rawData, $tpl);
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

    private function formatOlapData(&$data, $order=''){

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

        if(!empty($order)){
            $tmpCopy = $data;
//            $data = array();
            $order = str_ireplace('.', '_', $order);
            $arrOrder = explode(',', $order);
            $newOrderedData = array();
            foreach($tmpCopy as $key=>$record){
                foreach($arrOrder as $value){
                    if($this->array_ikey_exists($value, $record)){
                        $newOrderedData[$key][$value] = $record[$value];
                    }
                }
            }
            $data = $newOrderedData;
        }

	}

    public function array_ikey_exists($key, $arr) {
        if(stristr(implode(',', array_keys($arr)),$key)){
            return true;
        }
        return false;
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
            $tbName = array_shift(explode('.', $value));
            if(preg_match('/\b'.$tbName.'\b/i',implode(' ',$this->hideOlapTableColumnsLabel)) == 1){
                foreach( $translatedArray as $innerKey => $innerValue){
                    $strValue = trim((preg_replace('/\bname|CensusStudent\b/i', '',$innerValue)));
                    $strValue = Inflector::humanize(Inflector::underscore($strValue));
//                    $strValue = __(Inflector::humanize($strValue));
                    $translatedArray[$innerKey] = trim($strValue);
                }
            }

            if(sizeof($translatedArray) > 0){//!empty($strValue)){
                array_push($formattedArray,  __(Inflector::humanize(Inflector::underscore(implode(' ',$translatedArray)))));
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
