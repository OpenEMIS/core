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
App::uses('AppTask', 'Console/Command/Task');
App::uses('CakeEmail', 'Network/Email');

class AttendanceTask extends AppTask {

	public $uses = array(
		'Alerts.Alert', 
		'Alerts.AlertLog',
		'SecurityRole'
	);
	public $tasks = array('Common');

	public function execute() {
		$CakeMail = new CakeEmail('default');
		$CakeMail->subject('Test Alone');
		$CakeMail->to('dzhu@kordit.com');
		$CakeMail->send();
//		$alertId = 2;
//		$data = $this->Alert->getAlertWithRoles($alertId);
//		foreach ($data AS $record) {
//			$alert = $record['Alert'];
//			$roleId = $record['AlertRole']['security_role_id'];
//			$subject = $alert['subject'];
//			$message = $alert['message'];
//			if ($alert['method'] == 'Email') {
//				$securityUsers = $this->SecurityRole->getUsersByRole($roleId);
//				foreach ($securityUsers AS $user) {
//					$userEmail = $user['SecurityUser']['email'];
//					$userId = $user['SecurityUser']['id'];
//					
//					$CakeMail->subject($subject);
//					$CakeMail->to($userEmail);
//					
//					$CakeMail->viewVars(array('message' => $message));
//					try {
//						$success = $CakeMail->send();
//						if ($success) {
//							$this->AlertLog->create();
//
//							$newLog = array(
//								'method' => 'Email',
//								'destination' => $userEmail,
//								'type' => 'Alert',
//								'status' => 'Success',
//								'subject' => $subject,
//								'message' => $message,
//								'security_user_id' => $userId
//							);
//
//							$this->AlertLog->save($newLog);
//						}
//					} catch (SocketException $e) {
//						debug($e->getMessage());
//					}
//				}
//			}
//		}
	}

}

?>