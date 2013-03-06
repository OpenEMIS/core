<?php
App::uses('AppModel', 'Model');

class BankBranch extends AppModel {
	public $belongsTo = array('Bank');
	
	public function getLookupVariables() {
		$bankModel = ClassRegistry::init('Bank');
		$bankList = $bankModel->findList();
		$lookup = array();
		
		foreach($bankList as $bankId => $bank) {
			$branchList = $this->find('all', array(
				'recursive' => 0,
				'conditions' => array('BankBranch.bank_id' => $bankId),
				'order' => array('Bank.order', 'BankBranch.order')
			));
			
			if(!isset($lookup[$bank])) {
				$lookup[$bank] = array(
					'bankId' => $bankId, 
					'model' => 'BankBranch',
					'conditions' => array('bank_id' => $bankId),
					'options' => array()
				);
			}
			foreach($branchList as $obj) {
				$branch = $obj['BankBranch'];
				$lookup[$bank]['options'][] = $branch;
			}
		}
		return $lookup;
	}
}
?>