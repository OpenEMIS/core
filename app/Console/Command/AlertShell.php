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


class AlertShell extends AppShell {

	public $uses = array('ConfigItem');
	public $tasks = array('Attendance', 'Common');

	public function main() {
		
//		$i=0;
//		while (true) {
//			
//			$continue = $this->ConfigItem->getValue('alert_retry');
//			
//			if ($continue == 0) {
//				break;
//			}
//			
//			// execute logic
//			pr($i++);
//			
//			sleep(3);
//		}
		//pr($this->ConfigItem->getValue('alert_retry'));
		//$this->Attendance->execute();
		//$this->out($this->Common->getReportWebRootPath());
		//$this->Common->createLog($this->Common->getLogPath().'alert.log', 'log me');
		//sleep(5000); // 5 seconds
		
		$this->Attendance->execute();
	}
	
	public function hey_there() {
        $this->out('Hey there ' . $this->args[0]);
    }

}

?>