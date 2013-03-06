<?php
App::uses('AppModel', 'Model');

class InfrastructureFurniture extends AppModel {
	public $useTable = 'infrastructure_furniture';
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Furniture'));
		$lookup = array(
			'Furniture' => array('model' => $modelName),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
}
