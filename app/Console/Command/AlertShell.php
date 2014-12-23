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

App::uses('CakeEmail', 'Network/Email');
class AlertShell extends AppShell {

	public $uses = array('ConfigItem', 'SystemProcess', 'Alerts.Alert', 'Alerts.AlertLog');
	public $tasks = array('AlertAttendance');
	
	public function run() {
		$CakeMail = new CakeEmail('default');
		
		$args = $this->args;
		$taskName = 'Alert' . array_shift($args);

		$newLogIds = $this->{$taskName}->execute($args);
		
		$alertLogs = $this->AlertLog->find('all', array('recursive' => -1, 'conditions' => array('AlertLog.id' => $newLogIds)));
		
		foreach($alertLogs as $row) {
			$alertLog = $row['AlertLog'];
			$destination = $alertLog['destination'];
			$subject = $alertLog['subject'];
			$message = $alertLog['message'];
			
			$CakeMail->subject($subject);
			$CakeMail->to($destination);
			$CakeMail->viewVars(array('message' => $message));
			
			try {
				$success = $CakeMail->send();
			} catch (SocketException $e) {
				debug($e->getMessage());
				$this->log($e->getMessage(), 'alert_processes');
			}
		}
	}

	public function main() {
		$firstExec = true;
		$interval = 24*60*60;
		$CakeMail = new CakeEmail('default');
		
		while (true) {
			if($firstExec){
				$strTimeNow = time();
				$strToday = strtotime(date('Y-m-d') . ' 23:59:59');
				$timeDifference = $strToday - $strTimeNow;
				//$this->log('First execution at ' . $strToday, 'alert_processes');
				$interval = $timeDifference;
				$firstExec = false;
			}
			
			sleep($interval);
			
			// execute tasks here
			// Attendance alert start
			$alertAttendance = $this->Alert->getAlertByName('Student Absent');
			if($alertAttendance){
				$subject = $alertAttendance['Alert']['subject'];
				$message = $alertAttendance['Alert']['message'];
				
				$resultAttendance = $this->AlertAttendance->execute();
				//pr($resultAttendance);
				foreach($resultAttendance AS $row){
					$securityUser = $row['SecurityUser'];
					$userEmail = $securityUser['email'];
					
					$CakeMail->subject($subject);
					$CakeMail->to($userEmail);
					$CakeMail->viewVars(array('message' => $message));
					try {
						$success = $CakeMail->send();
						if ($success) {
							$this->AlertLog->create();

							$newLog = array(
								'id' => NULL,
								'method' => 'Email',
								'destination' => $userEmail,
								'type' => 'Alert',
								'status' => 1,
								'subject' => $subject,
								'message' => $message
							);

							$this->AlertLog->save($newLog);
						}
					} catch (SocketException $e) {
						debug($e->getMessage());
						$this->log($e->getMessage(), 'alert_processes');
					}
				}
			}
			// Attendance alert end
			
			// sleep to make sure next execution is on next date
			sleep(10);
			
			$timeAfterExec = time();
			$timeNewDay = strtotime(date('Y-m-d') . ' 23:59:59');
			$newDifference = $timeNewDay - $timeAfterExec;
			$interval = $newDifference;
		}
	}
	
}

?>