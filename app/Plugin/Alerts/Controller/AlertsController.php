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
	
	public function sendEmailByAlert(){
		//$cmd = 'nohup /Applications/MAMP/htdocs/openemis/app/Console/cake.php -app /Applications/MAMP/htdocs/openemis/app/ alert > /Applications/MAMP/htdocs/openemis/app/tmp/logs/processes.log & echo $!';
		$cmd = 'sudo php -dmemory_limit=1G /Applications/MAMP/htdocs/openemis/app/Console/cake.php -app /Applications/MAMP/htdocs/openemis/app/ batch run eng > /Applications/MAMP/htdocs/openemis/app/tmp/logs/processes.log & echo $!';
		exec($cmd, $output);
		pr($output);
		die;
		
		$this->Email->setConfig(array(
			'from' => array('kord.testing@gmail.com' => 'OpemEMIS SYSTEM')
		));
		//$Email->from(array('kord.testing@gmail.com' => 'OpemEMIS SYSTEM'));
		//pr($this->Email->showConfigs());die;
		$alertId = 2;
		$this->autoRender = false;
		$data = $this->Alert->getAlertWithRoles($alertId);
		foreach($data AS $record){
			$alert = $record['Alert'];
			$roleId = $record['AlertRole']['security_role_id'];
			//pr($alert);
			$subject = $alert['subject'];
			$message = $alert['message'];
			//pr($roleId);
			if($alert['method']  == 'Email'){
				$SecurityRole = ClassRegistry::init('SecurityRole');
				$securityUsers = $SecurityRole->getUsersByRole($roleId);
				//pr($securityUsers);
				foreach($securityUsers AS $user){
					$userEmail = $user['SecurityUser']['email'];
					$userId = $user['SecurityUser']['id'];
					
					$this->Email->setConfig(array(
						'subject' => $subject,
						'to' => $userEmail
					));
					
					//$Email->subject($subject);
					//$Email->to($userEmail);
					$this->Email->viewVars(array('message' => $message));
					//pr($this->Email->showConfigs());die;
					try{
						$success = $this->Email->send();
						if($success){
							$this->AlertLog->create();
							
							$newLog = array(
								'method' => 'Email',
								'destination' => $userEmail,
								'type' => 'Alert',
								'status' => 'Success',
								'subject' => $subject,
								'message' => $message,
								'security_user_id' => $userId
							);
							
							$this->AlertLog->save($newLog);
						}
					}catch(SocketException $e){
						 debug($e->getMessage());
					}
					
				}
			}
		}
	}
	
	public function process(){
		$this->Navigation->addCrumb('Alerts', array('action' => 'Alert'));
		$this->Navigation->addCrumb('Alert Process');
		$userId = $this->Auth->user('id');
		$alertProcess = $this->SystemProcess->getAlertProcess();
		
		$process = array();
		if ($this->request->is(array('post', 'put'))) {
			$formData = $this->request->data;
			$action = $formData['submit_button'];
			if($action == 'Start'){
				if($alertProcess){
					$params = array('Alert', 'main');
					$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
					$nohup = 'nohup %s > %stmp/logs/alert.log & echo $!';
					$shellCmd = sprintf($nohup, $cmd, APP);
					$this->log($shellCmd, 'debug');
					//pr($shellCmd);
					$output = array();
					exec($shellCmd, $output);
					$processId = $output[0];
					//pr($output);
					
					$saveData = array(
						'SystemProcess' => array(
							'id' => $alertProcess['SystemProcess']['id'],
							'process_id' => $processId,
							'start_date' => date('Y-m-d') . ' 23:59:59',
							'status' => 'Active'
						)
					);
					$this->SystemProcess->save($saveData);
				}
			}else if($action == 'Stop'){
				if($alertProcess){
					$systemProcessId = $alertProcess['SystemProcess']['id'];
					if($this->is_running($systemProcessId)){
						pr('is running');
						pr($systemProcessId);
						$this->kill($systemProcessId);
						
						$updateData = array(
							'SystemProcess' => array(
								'id' => $alertProcess['SystemProcess']['id'],
								'end_date' => date('Y-m-d H:i:s'),
								'status' => 'Inactive',
								'ended_user_id' => $userId
							)
						);
						$this->SystemProcess->save($updateData);
					}else{
						$updateData = array(
							'SystemProcess' => array(
								'id' => $alertProcess['SystemProcess']['id'],
								'status' => 'Inactive',
								'ended_user_id' => $userId
							)
						);
						$this->SystemProcess->save($updateData);
					}
				}else{
					$this->SystemProcess->create();
					$newProcessArr = array(
						'SystemProcess' => array(
							'name' => 'Alert Process',
							'created_user_id' => $userId
						)
					);

					$this->SystemProcess->save($newProcessArr);
				}
			}
		}
		
		$checkProcess = $this->SystemProcess->getAlertProcess();
		if(empty($checkProcess)){
			$this->SystemProcess->create();
			$newProcessArr = array(
				'SystemProcess' => array(
					'name' => 'Alert Process',
					'created_user_id' => $userId
				)
			);

			$this->SystemProcess->save($newProcessArr);
		}

		$newProcess = $this->SystemProcess->getAlertProcess();
		//pr($newProcess);
		if($newProcess){
			$process = $newProcess['SystemProcess'];
		}else{
			$this->Message->alert('Alert.noProcess');
		}
		
		//pr($process);
		$this->set(compact('process'));
		$this->render('process');
		
	}
	
	private function is_running($PID){
		$this->autoRender =false;
		exec("ps $PID", $ProcessState);
		return(count($ProcessState) >= 2);
	}
	
	private function kill($PID){
		$this->autoRender =false;
		exec("kill -KILL " . $systemProcessId);
		return true;
	}

}
?>
