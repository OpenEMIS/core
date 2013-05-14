<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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

class SecurityRoleArea extends AppModel {
	public $belongsTo = array('SecurityRole', 'Area');
	
	public function filterData(&$data) {
		$tmpData = $data;
		foreach($tmpData as $key => $obj) {
			if($obj['area_id']==0) {
				unset($data[$key]);
			}
		}
	}
	
	public function fetchAreas($levelList, $conditions) {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('SecurityRoleArea.area_id', 'Area.name', 'Area.area_level_id'),
			'conditions' => $conditions,
			'order' => array('Area.area_level_id', 'Area.order')
		));
		
		foreach($list as &$obj) {
			$obj['area_level_name'] = $levelList[$obj['area_level_id']];
		}
		return $list;
	}
	
	public function findAreasByRoles($roleIds) {
		$areas = $this->find('list', array(
			'fields' => array('SecurityRoleArea.id', 'SecurityRoleArea.area_id'),
			'conditions' => array('SecurityRoleArea.security_role_id' => $roleIds)
		));
		return $areas;
	}
}
