<?php
App::uses('AppModel', 'Model');

class InfrastructureEnergy extends AppModel {
	public $useTable = 'infrastructure_energy';
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Energy'));
		$lookup = array(
			'Energy' => array('model' => $modelName),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
}
