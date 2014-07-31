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
	public $belongsTo = array(
		'SecurityGroup',
		'Area'
	);
	
	public function autocomplete($search, $exclude) {
		$list = $this->Area->find('all', array(
			'fields' => array('Area.id', 'Area.code', 'Area.name', 'AreaLevel.name'),
			'conditions' => array(
				'OR' => array(
					'Area.name LIKE' => $search,
					'Area.code LIKE' => $search,
					'AreaLevel.name LIKE' => $search
				),
				'Area.id NOT' => $exclude
			),
			'order' => array('AreaLevel.level', 'Area.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$area = $obj['Area'];
			$level = $obj['AreaLevel'];
			$data[] = array(
				'label' => sprintf('%s - %s (%s)', $level['name'], $area['name'], $area['code']),
				'value' => array('value-id' => $area['id'], 'area-name' => $area['name'], 'area-code' => $area['code'])
			);
		}
		return $data;
	}
	
	public function getAreas($groupId) {
		$this->formatResult = true;
		$this->unbindModel(array('belongsTo' => array('Area')));
		$data = $this->find('all', array(
			'fields' => array('AreaLevel.name AS area_level_name', 'Area.id AS area_id', 'Area.area_level_id AS area_level_id', 'Area.name AS area_name'),
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
	
	public function findAreasByUserId($userId) {
		$areas = $this->find('list', array(
			'recursive' => -1,
			'fields' => array('SecurityGroupArea.security_group_id', 'SecurityGroupArea.area_id'),
			'joins' => array(
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array(
						'SecurityGroupUser.security_group_id = SecurityGroupArea.security_group_id',
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			)
		));
		return $areas;
	}
}
