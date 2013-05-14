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
		$this->bodyTitle = 'Settings';
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		$this->Navigation->addCrumb('Data Processing', array('controller' => $this->controller, 'action' => 'reports'));
	}
	
	public function index() {
		$this->redirect(array('action' => 'reports'));
	}
	
	public function reports() {
		$this->Navigation->addCrumb('Reports');
		
		$tmp = array();
        $q = array();
		if($this->request->is('post')){
			$this->Report->processRequest($this->data['Reports']);	
            $this->runJob(array('batch', 'run', $this->Session->read('configItem.language')));
			$this->redirect(array('action'=>'processes'));
		}
		$data = $this->Report->find('all');
		$QR = $this->Report->getQueuedRunningReport();

		foreach($QR as $arrV){
			$q[] = $arrV['Filename'];
		}
		foreach($data as $k => $val){
			if(isset($tmp['Reports'][$val['Report']['module']][$val['Report']['name']])){
				 $tmp['Reports'][$val['Report']['module']][$val['Report']['name']]['file_kinds'][$val['Report']['id']] = $val['Report']['file_type'];
			}else{
				$val['Report']['file_kinds'][$val['Report']['id']] = $val['Report']['file_type'];
				$tmp['Reports'][$val['Report']['module']][$val['Report']['name']] =  $val['Report'];
			}
		}
		//pr($tmp);die;
		$this->set('data',$tmp);
		$this->set('queued',$q);
	}
	
	public function indicators() {
		$this->Navigation->addCrumb('Export Indicators');
		
		if($this->request->is('post')) {
			$userId = $this->Auth->user('id');
			$format = $this->data['DataProcessing']['export_format'];
			//$indicatorIds = $this->data['BatchIndicator'];
			$processName = 'Export Indicators (' . $this->BatchIndicator->exportOptions[$format] . ')';
			$processId = $this->BatchProcess->createProcess($processName, $userId);
			$params = array('indicator', 'run', $processId, $format);
			$this->runJob($params);
			$this->redirect(array('action'=>'processes'));
		}
		$list = $this->BatchIndicator->find('all', array(
			'fields' => array('BatchIndicator.id', 'BatchIndicator.name', 'BatchIndicator.enabled'),
			'order' => array('BatchIndicator.enabled DESC', 'BatchIndicator.id')
		));
		
		$this->set('list', $list);
		$this->set('exportOptions', $this->BatchIndicator->exportOptions);
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
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params)); 
		
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			//$WshShell = new COM("WScript.Shell");
			//$oExec = $WshShell->Run("C:\wamp\bin\php\phpVERSIONNUMBER\php-win.exe -f C:/wamp/www/path/to/backgroundProcess.php", 0, false);
			$handle = pclose(popen("start /B ". $cmd, "r"));
			if ($handle === FALSE) {
				die("Unable to execute $cmd");
			}
			pclose($handle);
		} else {
			//exec("/var/www/html/dev.openemis.org/demo/app/Console/cake.php -app /var/www/html/dev.openemis.org/demo/app/ batch run > /dev/null &");
			//echo $r = shell_exec($cmd." > /dev/null &");
			//echo $PID = shell_exec("nohup $cmd > /dev/null & echo $!");
			
			$nohup = 'nohup %s > %stmp/logs/processes.log &';
			$shellCmd = sprintf($nohup, $cmd, APP);
			$this->log($shellCmd, 'debug');
			echo $PID = shell_exec($shellCmd);
		}
		//*NUX
		//exec("/var/www/html/dev.openemis.org/demo/app/Console/cake.php -app /var/www/html/dev.openemis.org/demo/app/ batch run > /dev/null &");
		//WINDOWS
		//$WshShell = new COM("WScript.Shell");
		//$oExec = $WshShell->Run("C:\wamp\bin\php\phpVERSIONNUMBER\php-win.exe -f C:/wamp/www/path/to/backgroundProcess.php", 0, false);
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
}
