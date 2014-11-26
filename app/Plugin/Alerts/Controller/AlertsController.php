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

App::uses('HttpSocket', 'Network/Http');

class AlertsController extends AlertsAppController {

	public $uses = array(
		'Alerts.Alert',
		'Alerts.AlertLog',
		'SystemProcess'
	);
	public $modules = array(
		'Alert' => array('plugin' => 'Alerts')
	);
	public $components = array(
		'Option',
		'Email',
		'Auth'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
	}

	public function process() {
		$this->Navigation->addCrumb('Alerts', array('action' => 'Alert'));
		$this->Navigation->addCrumb('Processes');
		$userId = $this->Auth->user('id');
		$alertProcess = $this->SystemProcess->getAlertProcess();

		$process = array();
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
					if ($this->is_running($systemProcessId)) {
						$this->kill($systemProcessId);

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

		$checkProcess = $this->SystemProcess->getAlertProcess();
		if (empty($checkProcess)) {
			$this->SystemProcess->create();
			$newProcessArr = array(
				'SystemProcess' => array(
					'name' => 'Alert Process'
				)
			);

			$this->SystemProcess->save($newProcessArr);
		}else{
			$processRunning = $this->is_running($checkProcess['SystemProcess']['process_id']);
			if($checkProcess['SystemProcess']['status'] == 'Active' && !$processRunning){
				$updateData = array(
					'SystemProcess' => array(
						'id' => $checkProcess['SystemProcess']['id'],
						'status' => 'Inactive'
					)
				);
				$this->SystemProcess->save($updateData);
			}
		}

		$newProcess = $this->SystemProcess->getAlertProcess();
		$process = $newProcess['SystemProcess'];

		//pr($process);
		$this->set(compact('process'));
		$this->render('process');
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

	private function is_running($PID) {
		$this->autoRender = false;
		exec("ps $PID", $ProcessState);
		return(count($ProcessState) >= 2);
	}

	private function kill($PID) {
		$this->autoRender = false;
		exec("kill -KILL " . $PID);
		return true;
	}

}

?>
