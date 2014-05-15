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
class DataProcessingController extends DataProcessingAppController {
	public $uses = array(
		'Reports.Report',
		'Reports.BatchReport',
		'Institution',
		'InstitutionSite',
		'InstitutionSiteCustomValue',
		'DataProcessing.BatchIndicator',
		'DataProcessing.BatchProcess',
		'SecurityUser'
	);
	
	public $components = array('DataProcessing.Indicator', 'DevInfo6.DevInfo6');
	
	private function getLogPath(){
		//return ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results/logs/';
		return ROOT.DS.'app'.DS.'webroot'.DS.'logs'.DS.'reports'.DS;
	}
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
	}
	
	public function index() {
		$this->redirect(array('action' => 'genReports'));
	}
	
	private function formatTable($data){
        $tmp = array();
		foreach($data as $k => $val){
			if(isset($tmp['Reports'][$val['Report']['module']][$val['Report']['name']])){
				 $tmp['Reports'][$val['Report']['module']][$val['Report']['name']]['file_kinds'][$val['Report']['id']] = $val['Report']['file_type'];
			}else{
				$val['Report']['file_kinds'][$val['Report']['id']] = $val['Report']['file_type'];
				$tmp['Reports'][$val['Report']['module']][$val['Report']['name']] =  $val['Report'];
			}
		}
		return $tmp;
	}
	
	private function processGenerate($data){
		$this->Report->processRequest($this->data['Reports']);
			$this->runJob(array('batch', 'run', $this->Session->read('configItem.language')));
			$this->redirect(array('action'=>'processes'));
	}

//    public function cusIndicators() {
//        echo 'build custom indicators';
//        $data = array();
//        $this->set('data', $data);
//    }


    public function build(){
        $this->Navigation->addCrumb('Build', '');
        $this->Navigation->addCrumb('Custom Indicators');
        $path = Configure::read('xml.indicators.custom.path');
//        $path = App::pluginPath($this->name) . 'webroot' . DS . 'reports';
        $type = 'custom';
        $data = array();
        $datasource = ConnectionManager::getDataSource('default');

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
        $this->set('controllerName', $this->name);
        $this->set('setting', array('maxFilesize' => Configure::read('xml.indicators.custom.size')));

    }

    public function BuildDelete(){
        $this->autoRender = false;

        if($this->request->is('post')) {
            $datasource = ConnectionManager::getDataSource('default');
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = Sanitize::escape($this->params->data['id']);

            $status = $this->deleteBuildReport($id, $datasource);

            if($status['type'] !== 0) {
                $result['alertOpt']['text'] = __('File was deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }

            return json_encode($result);
        }
    }

    public function BuildEdit($id=0){
        $this->Navigation->addCrumb('Build', '');
        $this->Navigation->addCrumb('Edit Custom Indicators');
        if($id == 0){
            $this->redirect(array('controller' => $this->name, 'action' => 'Custom'));
        }

        $path = Configure::read('xml.indicators.custom.path');
//        $path = App::pluginPath($this->name) . 'webroot' . DS . 'reports';
        $type = 'custom';
        $data = array();
        $datasource = ConnectionManager::getDataSource('default');
        $resultSet = array();

        if($this->request->is('post')){

            $id = Sanitize::escape($this->request->data['id']);
            $name = Sanitize::escape($this->request->data['name']);
            $description = Sanitize::escape($this->request->data['description']);
            $file = $this->request->data['doc_file'];
            $status = array();
            if($file['size'] > 0){
                if($this->validateFileFormat($file)){
                    $status = $this->editBuildReport($id, $name, $description, $file, $datasource);
                }else{
                    $status = array('msg' => __('Only XML file are allow.'), 'type' => 0);
                }
            }else{
                $status = $this->editBuildReport($id, $name, $description, $file, $datasource);
            }

            if(isset($status['type']) && $status['type'] > 0){
                $this->redirect(array('controller' => $this->name, 'action' => 'Build'));
            }

            $this->set('status', $status);
        }

        try{
            // Read 1st ten custom report
            $sql = sprintf("SELECT `id`, `name`, `metadata`, `filename`, `enabled` FROM `batch_indicators` WHERE `type` = '%s' AND `id` = '%s' LIMIT 1;",
                Sanitize::escape($type),
                Sanitize::escape($id));
            $resultSet = $datasource->query($sql);
        }catch(Exception $e){
            $this->redirect(array('controller' => $this->name, 'action' => 'Custom'));
        }

        if(count($resultSet) == 0 ){
            $this->redirect(array('controller' => $this->name, 'action' => 'Custom'));
        }

        foreach($resultSet as $result) {
            $tmp = array_pop($result);
            $tmp['id'] = nl2br($tmp['id']);
            $tmp['name'] = nl2br($tmp['name']);
            $tmp['description'] = nl2br($tmp['metadata']);
            array_push($data, $tmp);
        }

        $this->set('data', current($data));
        $this->set('controllerName', $this->name);
        $this->set('setting', array('maxFilesize' => Configure::read('xml.indicators.custom.size')));

    }

    public function BuildAdd(){
        $this->Navigation->addCrumb('Build', '');
        $this->Navigation->addCrumb('Add Custom Indicators');

        $type = 'custom';
        $data = array();
        $datasource = ConnectionManager::getDataSource('default');
        if($this->request->is('post')){

            $name = Sanitize::escape($this->request->data['name']);
            $description = Sanitize::escape($this->request->data['description']);
            $file = $this->request->data['doc_file'];
            if($this->validateFileFormat($file)){
                $status = $this->buildSave($name, $description, $file, $type, $datasource);
            }else{
                $status = array('msg' => __('Only XML file are allow.'), 'type' => 0);
            }
            $this->set('status', $status);
            if(isset($status['type']) && $status['type'] > 0){
                $this->redirect(array('controller' => $this->name, 'action' => 'Build'));
            }  
        }
        $this->set('setting', array('maxFilesize' => Configure::read('xml.indicators.custom.size')));
        $this->set('controllerName', $this->name);
//        $this->layout = 'ajax';
//        $this->set('params', $this->params->query);
//        $this->render('/Elements/custom/add');
    }

    private function buildSave($name, $description, $file, $type, $datasource){
        $path = Configure::read('xml.indicators.custom.path');
        $schemasPath = Configure::read('xml.schemas.path');
        $status = array('msg' => __('Upload Unsuccessful'), 'type' => 0);
        $newFile = $this->saveFile($file['tmp_name'], $path);

        if($this->validateXml($file['tmp_name'], $schemasPath.'indicator.xsd')){

            if($newFile && get_class($newFile) == 'File'){

                try{
                    // add to reports table
                    $sql = sprintf("INSERT INTO `reports` (`name`, `description`, `file_type`, `module`, `category`, `order`, `enabled`, `created_user_id`, `created`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s');",
                        Sanitize::escape($name),
                        Sanitize::escape($description),
                        Sanitize::escape('cus'),
                        Sanitize::escape('Custom'),
                        Sanitize::escape('Custom Reports'),
                        Sanitize::escape('0'),
                        Sanitize::escape('1'),
                        $this->Auth->user('id'),
                        $this->DateTime->dateAsSql(time()));
                    //echo $sql;
                    $datasource->query($sql);

                    // add to batch_indicators table
                    $sql = sprintf("INSERT INTO `batch_indicators` (`name`, `short_name`, `metadata`, `filename`, `type`, `unit`, `report_id`, `created_user_id`, `created`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s');",
                        Sanitize::escape($name),
                        Sanitize::escape($name),
                        Sanitize::escape($description),
                        Sanitize::escape("{$newFile->name()}.{$newFile->ext()}"),
                        Sanitize::escape($type),
                        Sanitize::escape(''),
                        $datasource->lastInsertId(),
                        $this->Auth->user('id'),
                        $this->DateTime->dateAsSql(time()));
                    //echo $sql;
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

    private function editBuildReport($id, $name, $description, $file, $datasource){
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

    private function deleteBuildReport($id, $datasource){ // not tested
        $path = Configure::read('xml.indicators.custom.path');
        $status = array('msg' => __('Upload Unsuccessful'), 'type' => 0);

        $sql = sprintf("Select `filename`, `report_id` FROM `batch_indicators` WHERE `id` = %s;",
            Sanitize::escape($id));
        $resultSet = $datasource->query($sql);
        $filename = '';

        if(count($resultSet) > 0 && count($resultSet) < 2){
            $result = array_pop($resultSet);
            $filename = $result['batch_indicators']['filename'];
            $reportId = $result['batch_indicators']['report_id'];
        }


        if(!empty($filename) && $this->removeFile($filename, $path)){
            // add to reports table
            $sql = sprintf("DELETE FROM `reports` WHERE `id` = %s;",
                Sanitize::escape($reportId));
//                    echo $sql;
            $datasource->query($sql);

            // add to batch_indicators table
            $sql = sprintf("DELETE FROM `batch_indicators` WHERE `id` = %s;",
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

    public function BuildDownload($id){
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
        if(empty($name)) $name = 'download';
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
    }

	public function genReports() {
		$this->Navigation->addCrumb('Generate');
		
		$tmp = array();
		$q = array();
		if($this->request->is('post')){
            /*foreach($this->data['Reports'] as $reportId){
                $this->customizedReport($reportId);
            }*/
            $this->processGenerate($this->data['Reports']);
		}
		$data = $this->Report->find('all',array('conditions'=>array('Report.visible' => 1, 'NOT'=>array('file_type'=>array('ind','est','cus'))), 'order' => array('Report.order')));
		$QR = $this->Report->getQueuedRunningReport();

		foreach($QR as $arrV){
			$q[] = $arrV['Filename'];
		}
		
		$tmp = $this->formatTable($data);
		
		//pr($tmp);die;
		$this->set('data',$tmp);
		$this->set('queued',$q);
	}
	
	public function genIndicators() {
		$this->Navigation->addCrumb('Generate');
		
		$tmp = array();
		$q = array();
		if($this->request->is('post')){
			$this->processGenerate($this->data['Reports']);
		}
		$data = $this->Report->find('all',array('conditions'=>array('file_type'=>'ind')));
		$QR = $this->Report->getQueuedRunningReport();

		foreach($QR as $arrV){
			$q[] = $arrV['Filename'];
		}
		
		$tmp = $this->formatTable($data);
		
		//pr($tmp);die;
		$this->set('data',$tmp);
		$this->set('queued',$q);
	}

	public function genCustoms() {
		$this->Navigation->addCrumb('Generate');

		$tmp = array();
		$q = array();
		if($this->request->is('post')){
			$this->processGenerate($this->data['Reports']);
		}
		$data = $this->Report->find('all',array('conditions'=>array('file_type'=>'cus')));
		$QR = $this->Report->getQueuedRunningReport();

		foreach($QR as $arrV){
			$q[] = $arrV['Filename'];
		}

		$tmp = $this->formatTable($data);

//		pr($tmp);die;
		$this->set('data',$tmp);
		$this->set('queued',$q);
	}
	
	public function genEstimates() {
		$this->Navigation->addCrumb('Generate');
		
		$tmp = array();
		$q = array();
		if($this->request->is('post')){
			$this->processGenerate($this->data['Reports']);
		}
		$data = $this->Report->find('all',array('conditions'=>array('file_type'=>'est')));
		$QR = $this->Report->getQueuedRunningReport();

		foreach($QR as $arrV){
			$q[] = $arrV['Filename'];
		}
		
		$tmp = $this->formatTable($data);
		
		//pr($tmp);die;
		$this->set('data',$tmp);
		$this->set('queued',$q);
	}
	
	public function export($option='DevInfo6') {
        $this->Navigation->addCrumb('Export');

        if($this->request->is('post')) {
            $userId = $this->Auth->user('id');
            $format = $this->data['DataProcessing']['export_format'];

            if(isset($this->data['BatchIndicator'])){
                $indicatorIds = implode(',',$this->data['BatchIndicator']);
            }

            switch($format){
                case 'Datawarehouse':
                    if($this->NumberOfOlapProcesses() > 0){
                        $this->Session->write('DataProcessing.olap.error', 'Unable to Export. Process exist.');
                        $this->redirect(array('action'=>'exports', $format));
                    }
                    $processName = 'Export '.$format;// (' . $format . ')';
//                    pr($this->data['Olap']);
//                    die();
                    $tables = $this->data['Olap']['census'];
                    $tables = array_merge($tables, $this->data['Olap']['lookup']);
                    $processId = $this->BatchProcess->createProcess($processName, $userId);
                    $params = array($processId, $format, implode(',', $tables));
                    break;
                case 'DevInfo7':
                case 'DevInfo6':
                default:
                    $processName = 'Export Indicators (' . $this->BatchIndicator->exportOptions[$format] . ')';
                    $processId = $this->BatchProcess->createProcess($processName, $userId);
                    $params = array('indicator', 'run', $processId, $format, $indicatorIds);
            }
//            $indicatorIds = $this->data['BatchIndicator'];
            $this->runJob($params);
            $this->redirect(array('action'=>'processes'));
        }

        $viewFile = '';
        switch(strtolower($option)){
            case 'datawarehouse':
                $this->set('olapList', $this->getOlapList());
                $viewFile = 'datawarehouse';
                break;
            case 'sdmx':
                break;
            case 'devinfo7':
                break;
            default:
                $listgroupRs = $this->BatchIndicator->find('all', array(
                    'fields' => array('DISTINCT BatchIndicator.type'),
                    'groupBy' => array('BatchIndicator.type'),
                    'order' => array('BatchIndicator.enabled DESC', 'BatchIndicator.id')
                ));
                $listgroup = array();
                foreach($listgroupRs as $row){
                    $listgroup[]= $row['BatchIndicator']['type'];
                }
                $list = array();
                foreach($listgroup as $group){
                	$tmpKey = (strtolower($group) === 'system')? 'standard':$group;
                    $list[$tmpKey] = $this->BatchIndicator->find('all', array(
                        'fields' => array('BatchIndicator.id', 'BatchIndicator.name', 'BatchIndicator.enabled', 'BatchIndicator.type'),
                        'order' => array('BatchIndicator.enabled DESC', 'BatchIndicator.id'),
                        'conditions' => array('BatchIndicator.type' => $group)
                    ));
                }
                $this->set('list', $list);
                $viewFile = 'devinfo6';
        }
        if($this->Session->check('DataProcessing.olap.error')){
            $this->set('error', $this->Session->read('DataProcessing.olap.error'));
            $this->Session->delete('DataProcessing.olap.error');
        }
        $this->set('url', array(
            "controller" => $this->request->params['controller'],
            "action" => $this->request->params['action'],
        ));
        if($this->Session->check('DataProcessing.olap.error')){
            $this->set('error', $this->Session->read('DataProcessing.olap.error'));
        }
//        FULL_BASE_URL.$this->request->base.DS.$this->request->params['controller'].DS.$this->request->params['action']
        $this->set('exportOptions', $this->BatchIndicator->exportOptions);
        $this->render($viewFile);
    }
	
	public function processes($action = '') {
		$this->Navigation->addCrumb('Processes');
		
		if($action == 'kill'){
			$this->BatchProcess->updateAll(
				array('BatchProcess.status' => 4, 'BatchProcess.modified' => "'".date('Y-m-d h:i:s')."'"),
				array('BatchProcess.status' => array(1,2))
			);
		}
		if($action == 'clear'){
			if($this->RequestHandler->isAjax()){
				$this->autoRender=false;
				$path = $this->getLogPath();
				$it = new RecursiveDirectoryIterator(
							$path);
				  $files = new RecursiveIteratorIterator($it,
							   RecursiveIteratorIterator::CHILD_FIRST);
				  foreach($files as $file){
					  if ($file->isFile()){
						  unlink($file->getRealPath());
					  }
				  }
				  $this->BatchProcess->deleteAll( array('NOT'=>array('BatchProcess.status'=>array('2'))), false);
			}
		}
        
		$data = $this->BatchProcess->find('all',array('order'=>array('id'=>'desc')));
		
		foreach ($data as $key => &$value) {
		
			if($value['BatchProcess']['reference_table'] == 'reports'){
				$reportData = $this->Report->findById($value['BatchProcess']['reference_id']);
				$value = array_merge($value,$reportData);
			}
			//check if log file exists
			$isFileExist = file_exists($value['BatchProcess']['file_name']);
			
			$value['BatchProcess']['file_exists'] =  $isFileExist;
			//var_dump($value['BatchProcess']['modified_user_id']);
			if(!is_null($value['BatchProcess']['modified_user_id']) && $value['BatchProcess']['modified'] != ''){
				$user= $this->SecurityUser->findById($value['BatchProcess']['modified_user_id'] );
				
			}elseif($value['BatchProcess']['created_user_id'] != ''){
				$user = $this->SecurityUser->findById($value['BatchProcess']['created_user_id']);
				
			}
			$value['BatchProcess']['startedBy'] = $user['SecurityUser']['username'];

                        if(array_key_exists('Report',$value) && $value['Report']['file_type'] == 'csv_custom'){
                            $value['Report']['file_type'] = 'csv';
                        }
		}
		//pr($data);
		$this->set('data',$data);
	}
	
	public function scheduler() {
		$this->Navigation->addCrumb('Scheduler');
	}
	
	public function runJob($params){
        $this->autoRender = false;

        //APP."Console/cake.php -app ".APP." batch run";die;
        if(stristr('Datawarehouse', $params[1])){
            $cmd = sprintf("%sLib/Olap/processing.php -i%s -p%s", APP, $params[0], $params[2]);
        }else{
            $cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $handle = pclose(popen("start /B ". $cmd, "r"));
            if ($handle === FALSE) {
                die("Unable to execute $cmd");
            }
            pclose($handle);
        } else {
            if(stristr('Datawarehouse',$params[1] )){
                //$nohup = 'nohup php %s < /dev/null & echo $! &';
                $nohup = 'nohup php %s > %stmp/logs/processes.log & echo $!';
            }else{
                $nohup = 'nohup %s > %stmp/logs/processes.log & echo $!';
            }
            $shellCmd = sprintf($nohup, $cmd, APP);
            //$shellCmd = sprintf($nohup, $cmd, APP);
            $this->log($shellCmd, 'debug');

            $PID = shell_exec($shellCmd);
            //$command = 'ls';
            //exec($shellCmd, $output);
            //print_r($PID);
        }
    }
	
	function is_running($PID){
		$this->autoRender =false;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

		}else{
			exec("ps $PID", $ProcessState);
			return(count($ProcessState) >= 2);
		}
	}
	
	/**
	 * Kill Application PID
	 *
	 * @param  unknown_type $PID
	 * @return boole
	 */
	function kill($PID){
		$this->autoRender =false;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			exec("Taskkill /PID $PID /F");
		}else{
			if($this->is_running($PID)){
				exec("kill -KILL $PID");
				return true;
			}else return false;
	   }
	}

	public function downloadLog($id){
		$this->viewClass = 'Media';
		$rec = $this->BatchProcess->findById($id);
		$path_parts = pathinfo($rec['BatchProcess']['file_name']);
		$id=$path_parts['filename'];
		$pathLog = $path_parts['dirname'].DS;
		$params = array(
			'id'        => $id.'.log',
			'name'      => $id,
			'download'  => true,
			'extension' => 'log',
			'path'      => $pathLog
		);
		$this->set($params);
	}
	public function getOlapList(){
        $config = Configure::read('Process.Olap.xml');
        $list = array('census'=>array(), 'lookup'=>array());

        if(file_exists($config['path'].DS.$config['filename'])){
            $olap = simplexml_load_file($config['path'].DS.$config['filename']);
            foreach($olap->xpath('//process[@type="census"]') as $row){
                $list['census'][] = (string) $row['name'];

            }
            foreach($olap->xpath('//process[@type="lookup"]') as $row){
                $list['lookup'][] = (string) $row['name'];

            }
//            pr($list);
        }

        return $list;
    }

    public function NumberOfOlapProcesses(){
        $results = $this->BatchProcess->numberOfOlapProcesses();
        return empty($results)? 0 : sizeof($results); exit;
    }

   public function customizedReport($id){
        $this->autoRender = false;
         if($id == '1036'){
            $TrainingSession = ClassRegistry::init('TrainingSession');
            $BatchReport = ClassRegistry::init('BatchReport');
            $sessions = $TrainingSession->find('all', 
            array('fields' => array(
                'TrainingCourse.id AS CourseID', 'TrainingSession.id as SessionID', 'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            ),
            'joins' => array(
                 array(
                    'table' => 'training_courses','alias' => 'TrainingCourse','type' => 'INNER',
                    'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id', 'TrainingCourse.training_status_id' => 3)
                )
            ),
            'conditions' =>  array('TrainingSession.training_status_id' => 3),
            'order' => array('TrainingCourse.title')
            ));
            $subquery = '';
            $i = 1;
            $fields = "'Staff.identification_no as OpenEmisID', 'Staff.first_name As FirstName', 'Staff.last_name As LastName', 'StaffPositionTitle.name As Position'";
            $templateFields = 'FirstName,LastName,Position';
            $joins = "
                array(
                    'table' => 'institution_site_staff','alias' => 'InsitutionSiteStaff','type' => 'LEFT',
                    'conditions' => array('Staff.id = InsitutionSiteStaff.staff_id')
                ),
                array(
                    'table' => 'staff_position_titles','alias' => 'StaffPositionTitle','type' => 'LEFT',
                    'conditions' => array('StaffPositionTitle.id = InsitutionSiteStaff.staff_position_title_id')
                )";

            foreach($sessions as $session){
                $joins .= ",
                array(
                    'table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee".$i ."','type' => 'LEFT',
                    'conditions' => array('Staff.id = TrainingSessionTrainee".$i.".identification_id', 'TrainingSessionTrainee".$i.".identification_table'=>'staff')
                )"; 
                $joins .= ",
                array(
                    'table' => 'training_sessions','alias' => 'TrainingSession".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingSession".$i.".id = TrainingSessionTrainee".$i.".training_session_id')
                )";
                $joins .= ",
                array(
                    'table' => 'training_session_results','alias' => 'TrainingSessionResult".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingSession".$i.".id = TrainingSessionResult".$i.".training_session_id')
                )";
                $joins .= ",
                array(
                    'table' => 'training_courses','alias' => 'TrainingCourse".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingCourse".$i.".id = TrainingSession".$i.".training_course_id')
                )";
                $yes = '"Yes"';
                $no = '"No"';
                $courseTitle = '"' . $session['TrainingCourse']['CourseCode'] .  '-' . $session['TrainingCourse']['CourseTitle'] . '"';
                $fields .= ",'((CASE WHEN TrainingSessionResult".$i.".training_status_id=3 THEN ".$yes." ELSE ".$no." END)) AS " .$courseTitle . "'";
                $templateFields .= ',' .  $session['TrainingCourse']['CourseCode'] .  '-' . $session['TrainingCourse']['CourseTitle'];
                $i++;
            }
            $query = '$this->autoRender = false;';
            $query .= '$this->Staff->formatResult = true;';
            $query .= '$data = $this->Staff->find(\'all\', 
            array(\'fields\' => array(' . $fields . '),
            \'joins\' => array(' . $joins. '),
            \'order\' => array(\'Staff.first_name\'),
             \'group\' => array(\'Staff.id\', \'InsitutionSiteStaff.staff_position_title_id\'),
            {cond}
            ));';
            $BatchReport->id = 1036;
            $BatchReport->saveField('template', $templateFields);
            $BatchReport->saveField('query', $query);
        }else if($id == '1037'){
            $TrainingSession = ClassRegistry::init('TrainingSession');
            $BatchReport = ClassRegistry::init('BatchReport');
            $sessions = $TrainingSession->find('all', 
            array('fields' => array(
                'TrainingCourse.id AS CourseID', 'TrainingSession.id as SessionID', 'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            ),
            'joins' => array(
                 array(
                    'table' => 'training_courses','alias' => 'TrainingCourse','type' => 'INNER',
                    'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id', 'TrainingCourse.training_status_id' => 3)
                )
            ),
            'conditions' =>  array('TrainingSession.training_status_id' => 3),
            'order' => array('TrainingCourse.title')
            ));
            $subquery = '';
            $i = 1;
            $fields = "'Teacher.first_name As FirstName', 'Teacher.last_name As LastName', 'TeacherPositionTitle.name As Position'";
            $templateFields = 'FirstName,LastName,Position';
            $joins = "
                array(
                    'table' => 'institution_site_teachers','alias' => 'InsitutionSiteTeacher','type' => 'LEFT',
                    'conditions' => array('Teacher.id = InsitutionSiteTeacher.teacher_id')
                ),
                array(
                    'table' => 'teacher_position_titles','alias' => 'TeacherPositionTitle','type' => 'LEFT',
                    'conditions' => array('TeacherPositionTitle.id = InsitutionSiteTeacher.teacher_position_title_id')
                )";

            foreach($sessions as $session){
               
                $joins .= ",
                array(
                    'table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee".$i ."','type' => 'LEFT',
                    'conditions' => array('Teacher.id = TrainingSessionTrainee".$i.".identification_id', 'TrainingSessionTrainee".$i.".identification_table'=>'teachers', 'TrainingSessionTrainee".$i.".training_session_id' => " . $session['TrainingSession']['SessionID'] . ")
                )";
                $joins .= ",
                array(
                    'table' => 'training_sessions','alias' => 'TrainingSession".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingSession".$i.".id = TrainingSessionTrainee".$i.".training_session_id')
                )";
                $joins .= ",
                array(
                    'table' => 'training_courses','alias' => 'TrainingCourse".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingCourse".$i.".id = TrainingSession".$i.".training_course_id')
                )";
                $joins .= ",
                array(
                    'table' => 'training_session_results','alias' => 'TrainingSessionResult".$i ."','type' => 'LEFT',
                    'conditions' => array('TrainingSession".$i.".id = TrainingSessionResult".$i.".training_session_id')
                )";
                $yes = '"Yes"';
                $no = '"No"';
                $courseTitle = '"' . $session['TrainingCourse']['CourseCode'] .  '-' . $session['TrainingCourse']['CourseTitle'] . '"';
                $fields .= ",'((CASE WHEN TrainingSessionResult".$i.".training_status_id=3 THEN ".$yes." ELSE ".$no." END)) AS " .$courseTitle . "'";
                $templateFields .= ',' .  $session['TrainingCourse']['CourseCode'] .  '-' . $session['TrainingCourse']['CourseTitle'];
                $i++;
            }
            $query = '$this->autoRender = false;';
            $query .= '$this->Teacher->formatResult = true;';
            $query .= '$data = $this->Teacher->find(\'all\', 
            array(\'fields\' => array(' . $fields . '),
            \'joins\' => array(' . $joins. '),
            \'order\' => array(\'Teacher.first_name\'),
            \'group\' => array(\'Teacher.id\', \'InsitutionSiteTeacher.teacher_position_title_id\'),
            {cond}
            ));';
            $BatchReport->id = 1037;
            $BatchReport->saveField('template', $templateFields);
            $BatchReport->saveField('query', $query);
        }
    }


    
}
