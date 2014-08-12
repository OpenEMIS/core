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

class SecurityUserAccess extends AppModel {
	public $useTable = 'security_user_access';
	
	public function isAccessExists($conditions) {
		$data = $this->find('first', array(
			'conditions' => $conditions
		));
		return $data;
	}
	
	public function getAccess($userId) {
		$modules = array(
			//'Teacher' => ClassRegistry::init('Teachers.Teacher'),
			'Staff' => ClassRegistry::init('Staff.Staff'),
			'Student' => ClassRegistry::init('Students.Student')
		);
		$data = $this->find('all', array(
			'conditions' => array('security_user_id' => $userId),
			'order' => array('table_name')
		));
		
		foreach($data as &$row) {
			$obj = &$row['SecurityUserAccess'];
			$table = $obj['table_name'];
			$id = $obj['table_id'];
			$user = $modules[$table]->find('first', array(
				'fields' => array('first_name', 'last_name', 'identification_no'),
				'recursive' => -1,
				'conditions' => array($table . '.id' => $id)
			));
			if($user) {
				$obj['name'] = $user[$table]['first_name'] . ' ' . $user[$table]['last_name'];
				$obj['identification_no'] = $user[$table]['identification_no'];
			}
		}
		return $data;
	}
}
