<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

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
				'message' => 'This Field of Study already exists in the system.'
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
