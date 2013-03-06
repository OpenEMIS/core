<?php
App::uses('AppModel', 'Model');

class InfrastructureCategory extends AppModel {
	public function getLookupVariables() {
		return array('Categories' => array('model' => 'InfrastructureCategory'));
	}
}
