<?php
App::uses('AppModel', 'Model');

class EducationCertification extends AppModel {
	public $hasMany = array('EducationProgramme');
}
