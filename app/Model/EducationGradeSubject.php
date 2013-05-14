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

class EducationGradeSubject extends AppModel {
	public $useTable = 'education_grades_subjects';
	public $belongsTo = array('EducationGrade', 'EducationSubject');
	
	public function findSubjectsByGrades($gradeIds) {
		$list = $this->find('all', array(
				'fields' => array(
					'EducationGradeSubject.id', 'EducationGradeSubject.education_grade_id', 
					'EducationGradeSubject.education_subject_id', 'EducationSubject.code', 'EducationSubject.name'
				),
				'conditions' => array('EducationGradeSubject.education_grade_id' => $gradeIds),
				'order' => array('EducationSubject.order')
			)
		);
		
		$list  = $this->formatArray($list);
		
		return $list;
	}
	
	public function groupSubjectsByGrade($subjectList) {
		$list = array(0 => array());
		foreach($subjectList as $subject) {
			$gradeId = $subject['education_grade_id'];
			$subjectId = $subject['education_subject_id'];
			
			$found = false;
			foreach($list[0] as $id => $item) {
				if(intval($subjectId) == intval($item['education_subject_id'])) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				$list[0][$subject['id']] = array(
					'education_grade_subject_id' => $subject['id'],
					'education_subject_id' => $subjectId,
					'education_subject_name' => $subject['name']
				);
			}
			$list[$gradeId][$subject['id']] = array(
				'education_grade_subject_id' => $subject['id'],
				'education_subject_id' => $subjectId,
				'education_subject_name' => $subject['name']
			);
		}
		return $list;
	}
}
