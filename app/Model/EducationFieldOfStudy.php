<?php
App::uses('AppModel', 'Model');

class EducationFieldOfStudy extends AppModel {
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Field of Study.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This Field of Study is already exists in the system.'
			)
		),
		'education_programme_orientation_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the programme orientation.'
			)
		)
	);
	
	public $belongsTo = array('EducationProgrammeOrientation');
	public $hasMany = array('EducationProgramme');
	
	public $virtualFields = array(
		'fullname' => "CONCAT((SELECT name FROM `education_programme_orientations` WHERE id = EducationFieldOfStudy.education_programme_orientation_id), ' - ', EducationFieldOfStudy.name)"
	);
}
