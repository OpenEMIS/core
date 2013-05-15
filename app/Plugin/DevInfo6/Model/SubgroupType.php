<?php

class SubgroupType extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_subgroup_type_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $order) {
		$id = 0;
		$modelName = 'SubgroupType';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.Subgroup_Type_Name' => $name)));
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'Subgroup_Type_Name' => $name,
						'Subgroup_Type_GID' => String::uuid(),
						'Subgroup_Type_Order' => $order,
						'Subgroup_Type_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['Subgroup_Type_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
