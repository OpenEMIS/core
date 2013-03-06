<?php
App::uses('AppModel', 'Model');

class FinanceType extends AppModel {
	public $belongsTo = array('FinanceNature');
	public $hasMany = array('FinanceCategory');
	
	public function getLookupVariables() {
		$parent = ClassRegistry::init('FinanceNature');
		$list = $parent->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array('model' => 'FinanceType', 'conditions' => array('finance_nature_id' => $id));
		}
		return $lookup;
	}
}
