<?php
App::uses('AppModel', 'Model');

class SchoolYear extends AppModel {
	
	public function getAvailableYears($list = true, $order='DESC') {
		if($list) {
			$result = $this->find('list', array(
					'fields' => array('SchoolYear.id', 'SchoolYear.name'),
					'conditions' => array('SchoolYear.available' => 1),
					'order' => array('SchoolYear.name ' . $order)
				)
			);
		} else {
			$result = $this->find('all', array(
					'conditions' => array('SchoolYear.available' => 1),
					'order' => array('SchoolYear.name ' . $order)
				)
			);
		}
		return $result;
	}
	
	public function getYearList($order='DESC') {
		$result = $this->find('list', array(
				'fields' => array('SchoolYear.id', 'SchoolYear.name'),
				'order' => array('SchoolYear.name ' . $order)
			)
		);
		
		return $result;
	}
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		
		$list = $this->find('all', array('order' => array('SchoolYear.name DESC')));
		$options = array();
		foreach($list as $obj) {
			$options[] = $obj['SchoolYear'];
		}
		$lookup = array('School Year' => array('model' => $modelName, 'options' => $options));
		return $lookup;
	}


	/**
	 * get school year id based on the given year
	 * @return int 	school year id
	 */
	public function getSchoolYearId($year) {
		$data = $this->findByName($year);	
		return $data['SchoolYear']['id'];
	}
}
