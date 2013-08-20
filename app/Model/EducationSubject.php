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
				'message' => 'This subject already exists in the system.'
			)
		)
	);
	
	public $hasMany = array('EducationGradeSubject');
	
	// Used by InstitutionSiteController.classesAddTeacherRow
	public function getSubjectByClassId($classId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationGradeSubject.id', 'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id',
						'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId
					)
				)
			),
			'group' => array('EducationSubject.id'),
			'conditions' => array('EducationSubject.visible' => 1),
			'order' => array('EducationSubject.order')
		));
		return $data;
	}
}
