<?php

class SubgroupVal extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_subgroup_vals_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $subgroupTypes) {
		$id = 0;
		$modelName = 'SubgroupVal';
		$set = 'primaryKey';
		$Subgroup = ClassRegistry::init('DevInfo6.Subgroup');
		$SubgroupValsSubgroup = ClassRegistry::init('DevInfo6.SubgroupValsSubgroup');
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.Subgroup_Val' => $name)));
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'Subgroup_Val' => $name,
						'Subgroup_Val_GId' => String::uuid(),
						'Subgroup_Val_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
				
				$subgroupList = explode(' - ', $name);
				foreach($subgroupList as $subgroupName) {
					$subgroupType = $this->getSubgroupType($subgroupTypes, $subgroupName);
					$subgroupId = $Subgroup->getPrimaryKey($subgroupName, $subgroupType);
					
					$model = array('SubgroupValsSubgroup' => array('Subgroup_Val_NId' => $id, 'Subgroup_NId' => $subgroupId));
					
					$SubgroupValsSubgroup->create();
					$SubgroupValsSubgroup->save($model);
				}
			} else {
				$id = $first[$modelName]['Subgroup_Val_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
	
	private function getSubgroupType($types, $subgroup) {
		foreach($types as $type => $list) {
			if(in_array($subgroup, $list)) {
				return array($type => $list['order']);
			}
		}
		return NULL;
	}
}
