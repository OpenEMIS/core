<?php

class Data extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_data';
	
	public function createRecord($data) {
		$model = array(
			'Data' => array(
				'Start_Date' => null,
				'End_Date' => null,
				'Data_Denominator' => 0,
				'FootNote_NId' => -1,
				'IC_IUS_Order' => null
			)
		);
		
		$model['Data'] = array_merge($model['Data'], $data);
		
		$this->create();
		$this->save($model);
	}
}
