<?php

class DIArea extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Area_en';
	
	public function import($levelMap) {
		$this->truncate();
		
		$AreaModel = ClassRegistry::init('Area');
		$areaList = $AreaModel->find('all', array('recursive' => 0));
		
		foreach($areaList as $obj) {
			$area = $obj['Area'];
			$model = array(
				'DIArea' => array(
					'Area_NId' => $area['id'],
					'Area_Parent_NId' => $area['parent_id'],
					'Area_ID' => $area['code'],
					'Area_Name' => $area['name'],
					'Area_GId' => String::uuid(),
					'Area_Level' => $levelMap[$area['area_level_id']],
					'Area_Map' => '',
					'Area_Block' => '',
					'Area_Global' => 0
				)
			);
			
			$this->create();
			$this->save($model);
		}
	}
}
