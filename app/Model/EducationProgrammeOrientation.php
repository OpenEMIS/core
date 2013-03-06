<?php
App::uses('AppModel', 'Model');

class EducationProgrammeOrientation extends AppModel {
	
	public $hasMany = array('EducationFieldOfStudy');
}
