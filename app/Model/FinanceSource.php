<?php
App::uses('AppModel', 'Model');

class FinanceSource extends AppModel {
	public function getLookupVariables() {
		$lookup = array('Source' => array('model' => 'FinanceSource'));
		return $lookup;
	}
}
