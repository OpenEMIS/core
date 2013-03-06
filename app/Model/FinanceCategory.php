<?php
App::uses('AppModel', 'Model');

class FinanceCategory extends AppModel {
	public $belongsTo = array('FinanceType');
	
	public function getLookupVariables() {
		$nature = ClassRegistry::init('FinanceNature');
		$type = ClassRegistry::init('FinanceType');
		$natureList = $nature->findList();
		$lookup = array();
		
		foreach($natureList as $natureId => $natureName) {
			$lookup[$natureName] = array('model' => 'FinanceCategory', 'options' => array());
			$typeList = $type->findList(array('conditions' => array('finance_nature_id' => $natureId)));
			foreach($typeList as $typeId => $typeName) {
				$conditions = array('conditions' => array('finance_type_id' => $typeId));
				$lookup[$natureName]['options'][$typeName] = array(
					'conditions' => array('finance_type_id' => $typeId),
					'options' => $this->findOptions($conditions)
				);
			}
		}
		return $lookup;
	}
}
