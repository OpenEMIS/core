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

    public function getCycles() {
        $this->unbindModel(array('hasMany' => array('EducationProgramme'), 'belongsTo' => array('EducationLevel')));
        // $records = $this->find('list', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->find('all', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->formatArray($records);
        return $records;
    }
}
