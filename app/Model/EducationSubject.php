<?php
App::uses('AppModel', 'Model');

class EducationSubject extends AppModel {
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Subject.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This subject is already exists in the system.'
			)
		)
	);
	
	public $hasMany = array('EducationGradeSubject');
}
