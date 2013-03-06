<?php
App::uses('AppModel', 'Model');

class InfrastructureSanitation extends AppModel {
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Sanitation'));
		$lookup = array(
			'Sanitation' => array('model' => $modelName),
			'Materials' => array(
				'model' => 'InfrastructureMaterial',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
	
	public function findListAsSubgroups() {
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Sanitation'));
		$statusModel = ClassRegistry::init('InfrastructureStatus');
		$conditions = array('InfrastructureStatus.infrastructure_category_id' => $categoryId, 'InfrastructureStatus.visible' => 1);
		return $statusModel->findList(array('conditions' => $conditions));
	}
}
