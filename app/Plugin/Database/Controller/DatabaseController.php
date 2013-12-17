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

App::uses('Inflector', 'Utility');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class DatabaseController extends DatabaseAppController {
	public $uses = array(
		'Reports.Report',
		'Reports.BatchReport',
		'Institution',
		'InstitutionSite',
		'InstitutionSiteCustomValue',
		'BatchIndicator',
		'BatchProcess',
		'SecurityUser'
	);
	
	public $components = array('DataProcessing.Indicator');
	
	private function isBackupRunning(){
		$data = $this->BatchProcess->find('first',array('conditions'=>array('name'=>'Backup Database','NOT' =>array('status' => array('3','-1')))));
		return (count($data)>0 && $data ? TRUE:FALSE);
	}
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
	}
	
	public function backup(){
		$this->Navigation->addCrumb('Backup');
		$isBackupRunning = $this->isBackupRunning();
		if($this->request->is('post')){
			$this->runJob(array('backup', 'run', CakeSession::read('Auth.User.id')));
			$this->redirect(array('action'=>'backup'));
		}
		
		if($isBackupRunning) $this->Utility->alert('A backup process is currently running.', array('type' => 'info'));
		$this->set('isBackupRunning',$isBackupRunning);
		
	}
	
	public function restore(){
		$isBackupRunning = $this->isBackupRunning();
		if($isBackupRunning) $this->Utility->alert('A backup process is currently running.', array('type' => 'info'));
		
		$this->Navigation->addCrumb('Restore');
		$path = ROOT.DS.'app'. DS .'Backups' . DS;
		
		if($this->request->is('post')){
			
			$this->runJob('backup', 'restore ');
		}
		$this->autoRender = true;
		$backupFolder = new Folder($path);
		// Get the list of files
		list($dirs, $files)     = $backupFolder->read();
		
		// Remove any un related files
		foreach ($files as $i => $file) { 
        if (!preg_match( '/\.sql/', $file))  { 
                unset($files[$i]);
            }
        }
		 $data = array();
        // Sort, explode the files to an array and list files
        sort($files, SORT_NUMERIC); 
        foreach ($files as $i => $file) { 
            $fileParts = explode(".", $file); 
            $backup_date = strtotime(str_replace("_", "", $fileParts[0]));
            $data[] ="[".$i."]: ".date("F j, Y, g:i:s a", $backup_date);
        }
		
		$this->set('data',$data);
		$this->set('isBackupRunning',$isBackupRunning);
	}
	
	public function index() {
		$this->redirect(array('action' => 'backup'));
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
	
}
