<?php
App::uses('AppModel', 'Model');

class BatchIndicator extends DataProcessingAppModel {
	public $data = array();
	
	public $exportOptions = array(
		'DevInfo6' => 'DevInfo 6',
		//'DevInfo7' => 'DevInfo 7',
        'Olap'=> 'OLAP',
		//'SDMX' => 'SDMX'
	);
	
	public function getIndicator($id) {
		$obj = '';
		if(isset($this->data[$id])) {
			$obj = $this->data[$id];
		} else {
			$obj = $this->find('first', array('conditions' => array('BatchIndicator.id' => $id)));
			$this->data[$id] = $obj;
		}
		return isset($obj['BatchIndicator']) ? $obj['BatchIndicator'] : null;
	}
}
