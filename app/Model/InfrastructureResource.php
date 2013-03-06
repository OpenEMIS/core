<?php
App::uses('AppModel', 'Model');

class InfrastructureResource extends AppModel {	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Resources'));
		$lookup = array(
			'Resources' => array('model' => $modelName),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
}
