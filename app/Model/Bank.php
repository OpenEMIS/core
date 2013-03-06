<?php
App::uses('AppModel', 'Model');

class Bank extends AppModel {
	public $hasMany = array('BankBranch');
	
	public function getLookupVariables() {
		$lookup = array('Banks' => array('model' => 'Bank'));
		return $lookup;
	}
}
?>