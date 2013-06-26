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

class SecurityGroupArea extends AppModel {
	public function saveGroupAccess($groupId, $data) {
		$id = array();
		$this->deleteAll(array('SecurityGroupArea.security_group_id' => $groupId), false);
		
		foreach($data as $obj) {
			$areaId = $obj['area_id'];
			if(!in_array($areaId, $id)) {
				$dataObj = array('SecurityGroupArea' => array(
					'security_group_id' => $groupId,
					'area_id' => $areaId
				));
				$this->create();
				$this->save($dataObj);
				$id[] = $areaId;
			}
		}
	}
	
	public function getAreas($groupId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'fields' => array('AreaLevel.name AS area_level_name', 'Area.id AS area_id', 'Area.name AS area_name'),
			'joins' => array(
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array('Area.id = SecurityGroupArea.area_id')
				),
				array(
					'table' => 'area_levels',
					'alias' => 'AreaLevel',
					'conditions' => array('AreaLevel.id = Area.area_level_id')
				)
			),
			'conditions' => array('SecurityGroupArea.security_group_id' => $groupId),
			'order' => array('AreaLevel.level', 'Area.order')
		));
		return $data;
	}
	
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
