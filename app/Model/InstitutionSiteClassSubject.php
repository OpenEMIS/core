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

class InstitutionSiteClassSubject extends AppModel {
	
	// used by InstitutionSite.classesEdit/classesView
	public function getSubjects($classId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'EducationGradeSubject.id', 'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.name',
				'InstitutionSiteClassSubject.education_grade_subject_id'
			),
			'joins' => array(
                array(
                    'table' => 'education_grades_subjects',
                    'alias' => 'EducationGradeSubject',
                    'type' => 'LEFT',
                    'conditions' => array('EducationGradeSubject.id = InstitutionSiteClassSubject.education_grade_subject_id',
                                          'EducationGradeSubject.visible = 1')
                ),
                array(
                    'table' => 'education_subjects',
                    'alias' => 'EducationSubject',
                    'type' => 'LEFT',
                    'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id',
                                          'EducationSubject.visible = 1')
                ),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'type' => 'LEFT',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id',
                                          'EducationGrade.visible = 1')
				)
			),
			'conditions' => array('InstitutionSiteClassSubject.institution_site_class_id' => $classId)
		));
		return $data;
	}
}
