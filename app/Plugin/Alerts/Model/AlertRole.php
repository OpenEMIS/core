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

class AlertRole extends AlertsAppModel {
	public $belongsTo = array(
		'Alert',
		'SecurityRole'
	);
	
	public function getRolesByAlertId($alertId){
		$this->formatResult = true;
		$list = $this->find('all', array(
			'recursive' => 0,
			'fields' => array('SecurityRole.id', 'SecurityRole.name', 'SecurityRole.security_group_id'),
			'conditions' => array('AlertRole.alert_id' => $alertId),
			'order' => array('SecurityRole.security_group_id', 'SecurityRole.order')
		));
		
		$data = array();
		foreach($list AS $row){
			$id = $row['id'];
			if($row['security_group_id'] == -1){
				$data[$id] = __('System') . ' - ' . $row['name'];
			}else{
				$data[$id] = $row['name'];
			}
		}
		
		return $data;
	}
}
