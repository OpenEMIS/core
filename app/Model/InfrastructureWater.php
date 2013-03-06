<?php
App::uses('AppModel', 'Model');

class InfrastructureWater extends AppModel {
	public $useTable = 'infrastructure_water';
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Water'));
		$lookup = array(
			'Water' => array('model' => $modelName),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
	
	public function findListAsSubgroups() {
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Water'));
		$statusModel = ClassRegistry::init('InfrastructureStatus');
		$conditions = array('InfrastructureStatus.infrastructure_category_id' => $categoryId, 'InfrastructureStatus.visible' => 1);
		return $statusModel->findList(array('conditions' => $conditions));
	}
}
