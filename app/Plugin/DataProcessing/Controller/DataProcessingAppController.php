<?php

class DataProcessingAppController extends AppController {
	public $uses = array('BatchProcess');
	public $BackupRunning = false;
	public function beforeFilter() {
		parent::beforeFilter();
		$this->backupRunning = $this->isBackupRunning();
	}
	
	private function isBackupRunning(){
		$data = $this->BatchProcess->find('first',array('conditions'=>array('name'=>'Backup Database','NOT' =>array('status' => array('3','-1')))));
		return (count($data)>0 && $data ? TRUE:FALSE);
	}
	
	public function beforeRender() {
		parent::beforeRender();
		if($this->backupRunning) $this->Utility->alert('A backup process is currently running.', array('type' => 'info'));
		$this->set('isBackupRunning', $this->backupRunning);
	}
}