<?php
class TrainingSessionTrainer extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'Staff' => array(
			'className' => 'Staff.Staff',
			'foreignKey' => 'ref_trainer_id',
		  	'conditions' => array('ref_trainer_table' => 'Staff'),
		),
	);

	public $validate = array(
		'ref_trainer_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Trainer.'
			)
		)
	);

	public function autocomplete($search, $index) {
		$search = sprintf('%%%s%%', $search);
		$data = array();
		
		$list = $this->Staff->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT Staff.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name'),
			'conditions' => array(
				'OR' => array(
					'SecurityUser.openemis_no LIKE' => $search,
					'SecurityUser.first_name LIKE' => $search,
					'SecurityUser.middle_name LIKE' => $search,
					'SecurityUser.third_name LIKE' => $search,
					'SecurityUser.last_name LIKE' => $search
				)
			),
			'order' => array('SecurityUser.first_name')
		));
		
		foreach($list as $obj) {
			$id = $obj['Staff']['id'];
			$identificationNo = $obj['SecurityUser']['openemis_no'];
			
			$data[] = array(
				'label' => ModelHelper::getName($obj['Staff']),
				'value' => array(
					'trainer-id-'.$index => $id, 
					'trainer-name-'.$index => ModelHelper::getName($obj['Staff'], array('middle'=>false, 'third'=>false)),
					'trainer-full-name-'.$index => ModelHelper::getName($obj['Staff']),
					'trainer-table-'.$index => 'Staff',
					'trainer-validate-'.$index => 'Staff_'.$id
					)
			);
		}

		return $data;
	}

}