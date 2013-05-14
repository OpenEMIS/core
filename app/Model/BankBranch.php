<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

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