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

class InstitutionSiteSectionClass extends AppModel {
	public $actsAs = array(
	);
	
	public $belongsTo = array(
		'InstitutionSiteSection',
		'InstitutionSiteClass'
	);
	
	// used by InstitutionSiteClass.edit
	public function getAvailableGradesForSection($id) {
		$data = $this->EducationGrade->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'EducationProgramme.name', 'EducationGrade.name', 'EducationGrade.id', 
				'InstitutionSiteSectionGrade.id', 'InstitutionSiteSectionGrade.status'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id'
					)
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
						'InstitutionSiteProgramme.status = 1'
					)
				),
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = InstitutionSiteSection.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = InstitutionSiteSection.school_year_id',
						'InstitutionSiteSection.id = ' . $id
					)
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteSectionGrade.education_grade_id = EducationGrade.id',
						'InstitutionSiteSectionGrade.institution_site_section_id = ' . $id
					)
				)
			),
			'order' => array('InstitutionSiteSectionGrade.id DESC', 'EducationProgramme.order', 'EducationGrade.order')
		));
		//pr($data);
		return $data;
	}
	
	public function getAvailableGradesForNewSection($institutionSiteId, $schoolYearId) {
		$data = $this->EducationGrade->find('all', array(
			'fields' => array('EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteProgramme.school_year_id = ' . $schoolYearId,
						'InstitutionSiteProgramme.status = 1'
					)
				)
			),
			'order' => array('EducationProgramme.order', 'EducationGrade.order')
		));
		return $data;
	}
	
	public function getAvailableSectionsForNewClass($institutionSiteId, $schoolYearId) {
		$data = $this->InstitutionSiteSection->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name'),
			'conditions' => array(
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId,
				'InstitutionSiteSection.school_year_id' => $schoolYearId
			),
			'order' => array('InstitutionSiteSection.name')
		));
		return $data;
	}
	
	public function getAvailableSectionsForClass($id) {
		$data = $this->InstitutionSiteSection->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteSection.id', 'InstitutionSiteSection.name',
				'InstitutionSiteSectionClass.id', 'InstitutionSiteSectionClass.status'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.institution_site_id = InstitutionSiteSection.institution_site_id',
						'InstitutionSiteClass.school_year_id = InstitutionSiteSection.school_year_id',
						'InstitutionSiteClass.id = ' . $id
					)
				),
				array(
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteSectionClass.institution_site_section_id = InstitutionSiteSection.id',
						'InstitutionSiteSectionClass.institution_site_class_id = ' . $id
					)
				)
			),
			'order' => array('InstitutionSiteSectionClass.id DESC')
		));
		//pr($data);
		return $data;
	}
	
	// used by InstitutionSite classes
	public function getSectionsByClass($classId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name', 'InstitutionSiteSectionClass.id'),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array('InstitutionSiteSectionClass.institution_site_section_id = InstitutionSiteSection.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionClass.institution_site_class_id' => $classId,
				'InstitutionSiteSectionClass.status' => 1
			),
			'order' => array('InstitutionSiteSection.name')
		));
		
		$list = array();
		foreach($data as $obj) {
			$id = $obj['InstitutionSiteSectionClass']['id'];
			$sectionName = $obj['InstitutionSiteSection']['name'];
			$list[$id] = $sectionName;
		}
		return $list;
	}
	
	public function getGradeOptions($sectionId=null, $status=null) {
		$conditions = array();
		
		if(!is_null($sectionId)){
			$conditions['InstitutionSiteSectionGrade.institution_site_section_id'] = $sectionId;
		}
		if(!is_null($status)) {
			$conditions['InstitutionSiteSectionGrade.status'] = $status;
		}
		$this->unbindModel(array('belongsTo' => array('EducationGrade')));
		$data = $this->find('all', array(
			'fields' => array('EducationGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
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
			'conditions' => $conditions,
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		$this->bindModel(array('belongsTo' => array('EducationGrade')));
		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationGrade']['id'];
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
				'InstitutionSiteSectionGrade.education_grade_id', 
				'InstitutionSiteSectionGrade.institution_site_class_id',
				'EducationGrade.education_programme_id'
			),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
				)
			),
			'conditions' => array('InstitutionSiteSectionGrade.id' => $gradeId)
		));
		
		$classId = $obj['institution_site_class_id'];
		$programmeId = $obj['education_programme_id'];
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteSectionGradeStudent.student_id', 'InstitutionSiteSectionGradeStudent.id'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id',
						'EducationGrade.education_programme_id = ' . $programmeId
					)
				),
				array(
					'table' => 'institution_site_class_grade_students',
					'alias' => 'InstitutionSiteSectionGradeStudent',
					'conditions' => array('InstitutionSiteSectionGradeStudent.institution_site_class_grade_id = InstitutionSiteSectionGrade.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionGrade.institution_site_class_id' => $classId
			)
		));
		return $data;
	}
        
	public function getGradesByInstitutionSiteId($institutionSiteId, $year = null) {
		$conditions = array('InstitutionSiteClass.id = InstitutionSiteSectionGrade.institution_site_class_id', 'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId); 
		if(!is_null($year)){
			$conditions[] = 'InstitutionSiteClass.school_year_id = '.$year;
		}
		
		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'InstitutionSiteClass')));
		$data = $this->find('all', array(
			'fields' => array('InstitutionSiteSectionGrade.id', 'EducationCycle.name', 'EducationProgramme.name',  'EducationGrade.id', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
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
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => $conditions
				),
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order'),
			'conditions' => array('InstitutionSiteSectionGrade.status' => 1)
		));
		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		}
		return $list;
	}
	
	public function getGrade($id) {
		$data = $this->find('first', array(
			'fields' => array('InstitutionSiteSectionGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
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
			'conditions' => array('EducationGrade.id' => $id),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
                
		$list = array();
		//foreach($data as $obj) {
			$id = $data['InstitutionSiteSectionGrade']['id'];
			$cycleName = $data['EducationCycle']['name'];
			$programmeName = $data['EducationProgramme']['name'];
			$gradeName = $data['EducationGrade']['name'];
			$list['InstitutionSiteSectionGrade']['grade_name'] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		//}
		return $list;
	}
}
