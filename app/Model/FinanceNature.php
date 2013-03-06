<?php
App::uses('AppModel', 'Model');

class FinanceNature extends AppModel {
	public $hasMany = array('FinanceType');
	
	public function getLookupVariables() {
		$lookup = array('Nature' => array('model' => 'FinanceNature'));
		return $lookup;
	}
}