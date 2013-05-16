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

App::uses('AppModel', 'Model');
App::uses('Sanitize', 'Utility');

class BatchProcess extends DataProcessingAppModel {
	public $status = array(
		'error' => -1,
		'new' => 1,
		'processing' => 2,
		'completed' => 3,
		'aborted' => 4
	);
	
	public function createProcess($name, $createdBy, $attr=array()) {
		$obj = array(
			'name' => $name,
			'start_date' => date('Y-m-d h:i:s'),
			'status' => $this->status['new'],
			'created_user_id' => $createdBy
		);
		$obj = array_merge($obj, $attr);
		
		$this->create();
		$process = $this->save(array($this->alias => $obj));
		return $process[$this->alias]['id'];
	}
	
	public function start($processId) {
		$obj = array(
			'id' => $processId,
			'status' => $this->status['processing']
		);
		$this->save(array($this->alias => $obj));
	}
	
	public function error($processId, $log) {
		$obj = array(
			'id' => $processId,
			'file_name' => $log,
			'status' => $this->status['error']
		);
		$this->save(array($this->alias => $obj));
	}
	
	public function completed($processId, $log) {
		$fields = array(
			'file_name' => "'" . Sanitize::escape($log) . "'",
			'finish_date' => "'" . date('Y-m-d h:i:s') . "'",
			'status' => $this->status['completed']
		);
		$conditions = array('id' => $processId, 'BatchProcess.status <>' => $this->status['error']);
		$this->updateAll($fields, $conditions);
	}
	
	public function check($processId) {
		$obj = $this->find('first', array('conditions' => array('BatchProcess.id' => $processId)));
		
		$result = $obj 
			   && $obj[$this->alias]['status'] !== $this->status['error']
			   && $obj[$this->alias]['status'] !== $this->status['aborted'];
		return $result;
	}
}
