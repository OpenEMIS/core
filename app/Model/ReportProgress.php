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

class ReportProgress extends AppModel {
	public $useTable = 'report_progress';

	public function addReport($obj) {
		$userId = CakeSession::read('Auth.User.id');

		if (isset($obj['params'])) {
			$obj['params'] = json_encode($obj['params']);
		}

		/* Currently disable the logic to prevent user to create duplicate reports
		$found = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'ReportProgress.name' => $obj['name'],
				'ReportProgress.created_user_id' => $userId
			)
		));
		if ($found) {
			$obj['id'] = $found[$this->alias]['id'];
			$obj['created'] = date('Y-m-d H:i:s');
			if ($found[$this->alias]['status'] == 1) {
				return false;
			}
		}
		*/

		$expiryDate = new DateTime();
		$expiryDate->add(new DateInterval('P3D')); // config item

		$obj['file_path'] = NULL;
		$obj['expiry_date'] = $expiryDate->format('Y-m-d H:i:s');
		$obj['current_records'] = 0;
		$obj['total_records'] = 0;
		$obj['status'] = 1;

		$result = $this->save($obj);
		return $result[$this->alias]['id'];
	}

	public function generate($id) {
		$params = array('Report', 'run', 'excel', $id);
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
		$nohup = 'nohup %s > %stmp/logs/reports.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, APP);
		$this->log($shellCmd, 'debug');
		//pr($shellCmd);
		$pid = exec($shellCmd);
		$this->id = $id;
		$this->saveField('pid', $pid);
	}

	public function purge($userId) {
		$format = 'Y-m-d';
		$data = $this->find('list', array(
			'fields' => array('ReportProgress.id', 'ReportProgress.file_path'),
			'conditions' => array('ReportProgress.expiry_date < ' => date($format))
		));

		foreach ($data as $id => $path) {
			if (file_exists($path)) {
				if (unlink($path)) {
					$this->delete($id);
				} else {
					$this->log('ReportProgress.purge - Unable to delete file id:' . $id, 'debug');
				}
			} else {
				$this->delete($id);
			}
		}
	}
}
