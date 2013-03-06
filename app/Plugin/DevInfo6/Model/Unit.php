<?php

class Unit extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Unit_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name) {
		$id = 0;
		$modelName = 'Unit';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			// check if the Unit is already in DevInfo database
			$first = $this->find('first', array('conditions' => array($modelName . '.Unit_Name' => $name)));
			
			if(!$first) { // if not exists in DevInfo DB, create the Unit
				$uuid = String::uuid();
				$model = array(
					$modelName => array(
						'Unit_Name' => $name,
						'Unit_GId' => $uuid,
						'Unit_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else { // if exists, get the Id from DevInfo DB
				$id = $first[$modelName]['Unit_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
