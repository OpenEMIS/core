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

class InstitutionSiteClassGrade extends AppModel {
	public $actsAs = array(
		'CascadeDelete' => array(
			'cascade' => array('InstitutionSiteClassGradeStudent')
		)
	);
	
	// used by InstitutionSite classes
	public function getGradesByClass($classId) {
		$data = $this->find('all', array(
			'fields' => array('InstitutionSiteClassGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				)
			),
			'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id' => $classId),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$list = array();
		foreach($data as $obj) {
			$id = $obj['InstitutionSiteClassGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		}
		return $list;
	}
	
	public function getStudentIdsByProgramme($gradeId) {
		$this->formatResult = true;
		$obj = $this->find('first', array(
			'fields' => array(
				'InstitutionSiteClassGrade.education_grade_id', 
				'InstitutionSiteClassGrade.institution_site_class_id',
				'EducationGrade.education_programme_id'
			),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
				)
			),
			'conditions' => array('InstitutionSiteClassGrade.id' => $gradeId)
		));
		
		$classId = $obj['institution_site_class_id'];
		$programmeId = $obj['education_programme_id'];
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClassGradeStudent.student_id', 'InstitutionSiteClassGradeStudent.id'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = InstitutionSiteClassGrade.education_grade_id',
						'EducationGrade.education_programme_id = ' . $programmeId
					)
				),
				array(
					'table' => 'institution_site_class_grade_students',
					'alias' => 'InstitutionSiteClassGradeStudent',
					'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteClassGrade.institution_site_class_id' => $classId
			)
		));
		return $data;
	}
}
