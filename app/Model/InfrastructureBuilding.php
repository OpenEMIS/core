<?php
App::uses('AppModel', 'Model');

class InfrastructureBuilding extends AppModel {
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Buildings'));
		$lookup = array(
			'Buildings' => array('model' => $modelName),
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
}
