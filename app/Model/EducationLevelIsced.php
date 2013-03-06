<?php
App::uses('AppModel', 'Model');

class EducationLevelIsced extends AppModel {
	public $useTable = 'education_level_isced';
	public $hasMany = array('EducationLevel');
	
	public function getList() {
		$model = get_class($this);
		$list = $this->find('all', array('recursive' => 0, 'order' => array('order')));
		
		$options = array();
		foreach($list as $obj) {
			if($obj[$model]['isced_level'] >= 0) {
				$options[$obj[$model]['id']] = sprintf('Level %d - %s', $obj[$model]['isced_level'], $obj[$model]['name']);
			} else {
				$options[$obj[$model]['id']] = $obj[$model]['name'];
			}
		}
		return $options;
	}
}
