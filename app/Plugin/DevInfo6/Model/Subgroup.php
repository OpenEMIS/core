<?php

class Subgroup extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_subgroup_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $subgroupType) {
		$id = 0;
		$modelName = 'Subgroup';
		$set = 'primaryKey';
		$SubgroupTypeModel = ClassRegistry::init('DevInfo6.SubgroupType');
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.Subgroup_Name' => $name)));
			
			if(!$first) {
				$type = key($subgroupType);
				$order = $subgroupType[$type];
				$typeId = $SubgroupTypeModel->getPrimaryKey($type, $order);
				$model = array(
					$modelName => array(
						'Subgroup_Name' => $name,
						'Subgroup_GId' => String::uuid(),
						'Subgroup_Type' => $typeId,
						'Subgroup_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['Subgroup_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
