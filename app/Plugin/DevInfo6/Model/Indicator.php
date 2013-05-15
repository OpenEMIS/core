<?php

class Indicator extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public $info = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Indicator_Info><Row1><Fld_Name>Definition</Fld_Name><FLD_VAL><RowData><temp1>%s</temp1></RowData></FLD_VAL></Row1></Indicator_Info>";
	
	public function getPrimaryKey($name, $metadata) {
		$id = 0;
		$modelName = 'Indicator';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			// check if the indicator is already in DevInfo database
			$first = $this->find('first', array('conditions' => array($modelName . '.Indicator_Name' => $name)));
			
			if(!$first) { // if not exists in DevInfo DB, create the indicator
				$uuid = String::uuid();
				$model = array(
					$modelName => array(
						'Indicator_Name' => $name,
						'Indicator_GId' => $uuid,
						'Indicator_Global' => 0
					)
				);
				
				if(strlen($metadata) > 0) {
					$model[$modelName]['Indicator_Info'] = sprintf($this->info, $metadata);
				}
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else { // if exists, get the Id from DevInfo DB
				$id = $first[$modelName]['Indicator_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
