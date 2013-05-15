<?php

class TimePeriod extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_timeperiod';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name) {
		$id = 0;
		$modelName = 'TimePeriod';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.TimePeriod' => $name)));
			
			if(!$first) {
				$model = array($modelName => array('TimePeriod' => $name));
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['TimePeriod_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
