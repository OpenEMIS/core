<?php
class TrainingSessionTrainer extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'Staff' => array(
			'className' => 'Staff',
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
		
		$staff = ClassRegistry::init('Staff');
		$staff->useTable = 'Staff';
		$list = $staff->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'),
			'conditions' => array(
				'OR' => array(
					'Staff.identification_no LIKE' => $search,
					'Staff.first_name LIKE' => $search,
					'Staff.last_name LIKE' => $search
				)
			),
			'order' => array('Staff.first_name')
		));
		
		foreach($list as $obj) {
			$id = $obj['Staff']['id'];
			$firstName = $obj['Staff']['first_name'];
			$lastName = $obj['Staff']['last_name'];
			$identificationNo = $obj['Staff']['identification_no'];
			
			$data[] = array(
				'label' => trim(sprintf('%s,  %s', $firstName, $lastName)),
				'value' => array(
					'trainer-id-'.$index => $id, 
					'trainer-name-'.$index => trim(sprintf('%s, %s', $firstName, $lastName)),
					'trainer-full-name-'.$index => trim(sprintf('%s, %s', $firstName, $lastName)),
					'trainer-table-'.$index => 'Staff',
					'trainer-validate-'.$index => 'Staff_'.$id
					)
			);
		}

		return $data;
	}

}