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
}
