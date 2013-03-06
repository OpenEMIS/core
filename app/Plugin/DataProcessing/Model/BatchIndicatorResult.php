<?php
App::uses('AppModel', 'Model');

class BatchIndicatorResult extends DataProcessingAppModel {
	public $useTable = 'batch_indicator_results';
	
	public function truncate($indicatorId=0) {
		if($indicatorId==0) {
			return $this->query(sprintf('TRUNCATE TABLE %s', $this->useTable));
		} else {
			return $this->query(sprintf('DELETE FROM %s WHERE batch_indicator_id = %d', $this->useTable, $indicatorId));
		}
	}
	
	public function createNew($indicatorId, $subgroups, $data) {
		$model = $this->alias;
		foreach($data as $row) {
			$obj = array($model => $row);
			$obj[$model]['batch_indicator_id'] = $indicatorId;
			$obj[$model]['subgroups'] = $subgroups;
			$this->create();
			$this->save($obj);
		}
	}
}
