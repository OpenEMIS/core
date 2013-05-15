<?php

class IndicatorClassificationIUS extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_classifications_ius';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($ICId, $IUSId) {
		$id = 0;
		$modelName = 'IndicatorClassificationIUS';
		$set = 'primaryKey';
		$name = implode(',', array($ICId, $IUSId));
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$conditions = array($modelName . '.IC_NId' => $ICId, $modelName . '.IUSNId' => $IUSId);
			$first = $this->find('first', array('conditions' => $conditions));
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'IC_NId' => $ICId,
						'IUSNId' => $IUSId,
						'RecommendedSource' => 0,
						'IC_IUS_Order' => null,
						'IC_Label' => null
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['IC_IUSNId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
