<?php

class IndicatorUnitSubgroup extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Indicator_Unit_Subgroup';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($indicator, $unit, $subgroup) {
		$id = 0;
		$modelName = 'IndicatorUnitSubgroup';
		$set = 'primaryKey';
		$name = implode(',', array($indicator, $unit, $subgroup));
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array(
				'conditions' => array(
						$modelName . '.Indicator_NId' => $indicator,
						$modelName . '.Unit_NId' => $unit,
						$modelName . '.Subgroup_Val_NId' => $subgroup
					)
				)
			);
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'Indicator_NId' => $indicator,
						'Unit_NId' => $unit,
						'Subgroup_Val_NId' => $subgroup,
						'Min_Value' => 0,
						'Max_Value' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['IUSNId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
