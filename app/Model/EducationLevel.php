<?php
App::uses('AppModel', 'Model');

class EducationLevel extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Education Level.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system.'
			)
		)
	);
	*/
	
	public $belongsTo = array('EducationSystem', 'EducationLevelIsced');
	public $hasMany = array('EducationCycle');
}
