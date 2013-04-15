<?php
App::uses('AppModel', 'Model');

class EducationCycle extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Education Cycle.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system.'
			)
		),
		'admission_age' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter the admission age'
		)
	);
	*/
	
	public $belongsTo = array('EducationLevel');
	public $hasMany = array('EducationProgramme');
	
	public function getOfficialAgeByGrade($gradeId) {
		$age = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('EducationCycle.admission_age', 'EducationGrade.order'),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.education_cycle_id = EducationCycle.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.id = ' . $gradeId
					)
				)
			)
		));
		return $age['EducationCycle']['admission_age'] + $age['EducationGrade']['order'] - 1;
	}

    public function getCycles() {
        $this->unbindModel(array('hasMany' => array('EducationProgramme'), 'belongsTo' => array('EducationLevel')));
        // $records = $this->find('list', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->find('all', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->formatArray($records);
        return $records;
    }
}
