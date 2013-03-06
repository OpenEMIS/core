<?php
App::uses('AppModel', 'Model');

class InfrastructureMaterial extends AppModel {
	public $belongsTo = array( 
		'InfrastructureCategory'
	);
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$lookup = array('Materials' => array('model' => $modelName));
		return $lookup;
	}
}
