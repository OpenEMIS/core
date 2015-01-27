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
			'fields' => array('DISTINCT Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name'),
			'conditions' => array(
				'OR' => array(
					'Staff.identification_no LIKE' => $search,
					'Staff.first_name LIKE' => $search,
					'Staff.middle_name LIKE' => $search,
					'Staff.third_name LIKE' => $search,
					'Staff.last_name LIKE' => $search
				)
			),
			'order' => array('Staff.first_name')
		));
		
		foreach($list as $obj) {
			$id = $obj['Staff']['id'];
			$identificationNo = $obj['Staff']['identification_no'];
			
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