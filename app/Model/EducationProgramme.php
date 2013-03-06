<?php
App::uses('AppModel', 'Model');

class EducationProgramme extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter a name for the programme.'
		),
		'education_field_of_study_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please choose a field of study.'
		),
		'education_certification_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please select a certification.'
		),
		'duration' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter the duration.'
		)
	);
	*/
	
	public $belongsTo = array('EducationCycle', 'EducationFieldOfStudy', 'EducationCertification');
	public $hasMany = array('EducationGrade', 'InstitutionSiteProgramme');
}
