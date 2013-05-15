<?php

class DIAreaLevel extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_area_level_en';
	
	public function import() {
		$this->truncate();
		
		$levelMap = array();
		$AreaLevel = ClassRegistry::init('AreaLevel');
		$areaLevelList = $AreaLevel->find('all', array('recursive' => 0));
		
		foreach($areaLevelList as $obj) {
			$level = $obj['AreaLevel'];
			$levelMap[$level['id']] = $level['level'];
			$model = array(
				'DIAreaLevel' => array(
					'Level_NId' => $level['id'],
					'Area_Level' => $level['level'],
					'Area_Level_Name' => $level['name']
				)
			);
			
			$this->create();
			$this->save($model);
		}
		
		return $levelMap;
	}
}
