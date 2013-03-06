<?php
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
