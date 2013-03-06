<?php
App::uses('AppModel', 'Model');

class EducationSystem extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system.'
			)
		)
	);
	*/
	public $hasMany = array('EducationLevel');
}
