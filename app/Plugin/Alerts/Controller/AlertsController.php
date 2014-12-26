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

class AlertsController extends AlertsAppController {
	public $uses = array(
		'Alerts.Alert',
		'Alerts.AlertLog',
		'SecurityRole',
		'SystemProcess'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Communications', array('plugin' => 'Alerts', 'controller' => 'Alerts'));
	}

	public function index(){
		$alias = $this->Alert->alias;
		$this->Navigation->addCrumb('Alerts');
		
		$data = $this->Alert->find('all');
		
		$this->set(compact('data'));
	}

	public function view($id=0) {
		$this->Navigation->addCrumb('Alert Details');
		$this->Alert->contain(array('ModifiedUser', 'CreatedUser', 'SecurityRole'));

		if ($this->Alert->exists($id)) {
			$data = $this->Alert->findById($id);
			$this->Session->write($this->Alert->alias.'.id', $id);
			$this->set(compact('data'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}

	public function edit($id=0) {
		$this->Navigation->addCrumb('Edit Alert');
		$this->Alert->contain(array('ModifiedUser', 'CreatedUser', 'SecurityRole'));
		$data = $this->Alert->findById($id);
		
		$statusOptions = $this->Option->get('enableOptions');
		$methodOptions = $this->Option->get('alertMethod');
		$roleOptions = $this->SecurityRole->find('list', array(
			'conditions' => array(
				'SecurityRole.visible' => 1,
				'SecurityRole.security_group_id' => array('0', '-1')),
			'order' => array('SecurityRole.security_group_id', 'SecurityRole.order')
		));

		if ($this->request->is(array('post', 'put'))) {
			$alertData = $this->request->data;
			
			if ($this->Alert->save($alertData)) {
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => 'view', $id));
			} else {
				$this->request->data = $alertData;
				$this->Message->alert('general.edit.failed');
			}
		} else {
			$this->request->data = $data;
		}
		
		$this->set(compact('id', 'statusOptions', 'methodOptions', 'roleOptions'));
	}
	
	public function processActions() {
		$this->autoRender = false;
		$alertProcess = $this->SystemProcess->getAlertProcess();
		
		if ($this->request->is(array('post', 'put'))) {
			$formData = $this->request->data;
			$action = $formData['submit'];
			
			if ($action == 'Start') {
				if ($alertProcess) {
					$params = array('Alert', 'main');
					$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
					$nohup = 'nohup %s > %stmp/logs/processes.log & echo $!';
					$shellCmd = sprintf($nohup, $cmd, APP);
					$this->log($shellCmd, 'debug');
					//pr($shellCmd);
					$output = array();
					exec($shellCmd, $output);
					$processId = $output[0];
					//pr($output);

					$updateData = array(
						'SystemProcess' => array(
							'id' => $alertProcess['SystemProcess']['id'],
							'process_id' => $processId,
							'start_date' => date('Y-m-d') . ' 23:59:59',
							'status' => 'Active',
							'end_date' => NULL
						)
					);
					$this->SystemProcess->save($updateData);
				}
			} else if ($action == 'Stop') {
				if ($alertProcess) {
					$systemProcessId = $alertProcess['SystemProcess']['process_id'];
					if ($this->SystemProcess->is_running($systemProcessId)) {
						$this->SystemProcess->kill($systemProcessId);

						$updateData = array(
							'SystemProcess' => array(
								'id' => $alertProcess['SystemProcess']['id'],
								'end_date' => date('Y-m-d H:i:s'),
								'status' => 'Inactive'
							)
						);
						$this->SystemProcess->save($updateData);
					} else {
						$updateData = array(
							'SystemProcess' => array(
								'id' => $alertProcess['SystemProcess']['id'],
								'status' => 'Inactive'
							)
						);
						$this->SystemProcess->save($updateData);
					}
				} else {
					$this->SystemProcess->create();
					$newProcessArr = array(
						'SystemProcess' => array(
							'name' => 'Alert Process'
						)
					);

					$this->SystemProcess->save($newProcessArr);
				}
			}
		}
		
		$this->redirect(array('plugin' => null, 'controller'=>'Config', 'action'=>'index', 'system_processes'));
	}

}

?>
