<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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

class Teacher extends TeachersAppModel {

	public $actsAs = array(
		'Search' => array(
			'notExists' => 'SELECT teacher_id FROM institution_site_teachers WHERE teacher_id = Teacher.id',
			'areaJoins' => array(
				array(
					'table' => 'institution_site_teachers',
					'alias' => 'InstitutionSiteTeacher',
					'conditions' => array('InstitutionSiteTeacher.teacher_id = Teacher.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = InstitutionSiteTeacher.institution_site_id')
				)
			)
		),
		'UserAccess',
		'TrackHistory' => array('historyTable' => 'Teachers.TeacherHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Teachers.TeacherAttachment',
				'Teachers.TeacherCustomValue',
				'Teachers.TeacherQualification',
				'Teachers.TeacherTraining'
			)
		),
		'CustomReport'
	);

	public $validate = array(
		'first_name' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			)
		),
		'identification_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid OpenEMIS ID'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique OpenEMIS ID'
		    )
		),
		'gender' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', 'not equal', '0'),
				'required' => true,
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select a Date of Birth'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)
	);
	
	public function getLookupVariables() {
		$lookup = array(
			'Positions' => array('model' => 'Teachers.TeacherCategory')
		);
		return $lookup;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		return $this->getPaginate($conditions, $fields, $order, $limit, $page, $recursive, $extra);
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->getPaginateCount($conditions, $recursive, $extra);
	}
}
?>
