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

class InstitutionSiteSectionGrade extends AppModel {
	public $actsAs = array(
		'InstitutionSiteProgramme'
	);
	
	public $belongsTo = array(
		'EducationGrade',
		'InstitutionSiteSection'
	);
	
	public function getAvailableGradesForNewSection($institutionSiteId, $academicPeriodId) {
		$conditions = ClassRegistry::init('InstitutionSiteProgramme')->getConditionsByAcademicPeriodId($academicPeriodId);
		$conditions = array_merge(array(
			'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		), $conditions);

		$data = $this->EducationGrade->find('all', array(
			'fields' => array('EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => $conditions
				)
			),
			'order' => array('EducationProgramme.order', 'EducationGrade.order')
		));
		return $data;
	}
	
	// used by InstitutionSite classes
	public function getGradesBySection($sectionId) {
		$this->unbindModel(array('belongsTo' => array('EducationGrade')));
		$data = $this->find('all', array(
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
			'conditions' => array(
				'InstitutionSiteSectionGrade.institution_site_section_id' => $sectionId,
				'InstitutionSiteSectionGrade.status' => 1
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		$this->bindModel(array('belongsTo' => array('EducationGrade')));

		$list = array();
		foreach($data as $obj) {
			$id = $obj['InstitutionSiteSectionGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
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
        
	public function getGradesByInstitutionSiteId($institutionSiteId, $year = null) {
		$data = $this->getInstitutionSiteGradesData($institutionSiteId, $year);
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
	
	public function getInstitutionSiteGradeOptions($institutionSiteId, $year = null) {
		$data = $this->getInstitutionSiteGradesData($institutionSiteId, $year);
		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationGrade']['id'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = $gradeName;
		}
		return $list;
	}
	
	public function getInstitutionSiteGradesData($institutionSiteId, $year = null) {
		$conditions = array('InstitutionSiteSection.id = InstitutionSiteSectionGrade.institution_site_section_id', 'InstitutionSiteSection.institution_site_id = ' . $institutionSiteId); 
		if(!is_null($year)){
			$conditions[] = 'InstitutionSiteSection.academic_period_id = '.$year;
		}
		
		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'InstitutionSiteSection')));
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
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => $conditions
				),
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order'),
			'conditions' => array('InstitutionSiteSectionGrade.status' => 1)
		));

		return $data;
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
	
	public function getInstitutionGradeOptions($institutionSiteId, $academicPeriodId) {		
		$conditions = ClassRegistry::init('InstitutionSiteProgramme')->getConditionsByAcademicPeriodId($academicPeriodId);
		$conditions = array_merge(array(
			'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		), $conditions);

		$data = $this->EducationGrade->find('list', array(
			'fields' => array('EducationGrade.id', 'EducationGrade._name'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => $conditions
				)
			),
			'order' => array('EducationGrade.order')
		));
		return $data;
	}
}
