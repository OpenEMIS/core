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

class AssessmentItemType extends AppModel {
	public $actsAs = array('Reorder');
	
	public $type = array(
		'NON_OFFICIAL' => 0,
		'OFFICIAL' => 1
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique Code'
		    )
		),
		'education_grade_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select an available Grade'
			),
		)
	);
	
	public function getAssessmentByTypeAndGrade($type, $grade, $filter=array()) {
		$model = get_class($this) . '.%s';
		$conditions = array(
			sprintf($model, 'type') => $type,
			sprintf($model, 'education_grade_id') => $grade
		);
		if(!empty($filter)) {
			foreach($filter as $key => $val) {
				$conditions[sprintf($model, $key)] = $val;
			}
		}
		$data = $this->find('all', array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array(sprintf($model, 'order'))
		));
		return $data;
	}
	
	public function getAssessmentByTypeAndProgramme($type=false, $programmeId, $filter=array()) {
		$model = get_class($this) . '.%s';
		$conditions = array();
		if($type !== false) {
			$conditions = array(sprintf($model, 'type') => $type);
		} else {
			$conditions['AND'] = array();
		}
		if(!empty($filter)) {
			foreach($filter as $key => $val) {
				if($type == false) {
					if($key === 'institution_site_id' || $key === 'school_year_id') {
						$conditions['AND'][] = array('OR' => array(sprintf($model, $key) . ' = 0', sprintf($model, $key) . ' = ' . $val));
					} else {
						$conditions['AND'][sprintf($model, $key)] =  $val;
					}
				} else {
					$conditions[sprintf($model, $key)] = $val;
				}
			}
		}
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				sprintf($model, 'id'),
				sprintf($model, 'code'),
				sprintf($model, 'name'),
				sprintf($model, 'description'),
				sprintf($model, 'type'),
				sprintf($model, 'order'),
				sprintf($model, 'visible'),
				sprintf($model, 'education_grade_id'),
				'EducationGrade.name as education_grade_name',
				'EducationGrade.id as education_grade_id'
			),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = AssessmentItemType.education_grade_id',
						'EducationGrade.education_programme_id = ' . $programmeId
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationGrade.order', 'AssessmentItemType.type DESC', 'AssessmentItemType.order')
		));
		return $data;
	}
	
	public function getAssessmentsByClass($classId) {
		$list = $this->find('all', array(
			'fields' => array(
				'AssessmentItemType.id', 'AssessmentItemType.code', 'AssessmentItemType.name',
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name', 
				'EducationGrade.name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array('InstitutionSiteClassGrade.education_grade_id = AssessmentItemType.education_grade_id')
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.id = InstitutionSiteClassGrade.institution_site_class_id',
						'InstitutionSiteClass.id = ' . $classId
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = AssessmentItemType.education_grade_id')
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
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$programmeId = $obj['EducationProgramme']['id'];
			$programmeName = $obj['EducationCycle']['name'] . ' - ' . $obj['EducationProgramme']['name'];
			$assessment = $obj['AssessmentItemType'];
			if(!array_key_exists($programmeId, $data)) {
				$data[$programmeId] = array('name' => $programmeName, 'items' => array());
			}
			$data[$programmeId]['items'][] = array(
				'id' => $assessment['id'],
				'code' => $assessment['code'],
				'name' => $assessment['name'],
				'grade' => $obj['EducationGrade']['name']
			);
		}
		//pr($data);die;
		return $data;
	}
	
	public function getInstitutionAssessmentsBySchoolYear($institutionSiteId, $schoolYearId) {
		$list = $this->find('all', array(
			'fields' => array(
				'DISTINCT AssessmentItemType.id', 'AssessmentItemType.code', 'AssessmentItemType.name', 'AssessmentItemType.description',
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name', 
				'EducationGrade.name', 'EducationGrade.id'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.education_grade_id = AssessmentItemType.education_grade_id',
						'InstitutionSiteSectionGrade.status = 1'
					)
				),
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSection.id',
						'InstitutionSiteSection.institution_site_id' => $institutionSiteId,
						'InstitutionSiteSection.school_year_id' => $schoolYearId
					)
				),
				array(
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => array(
						'InstitutionSiteSectionClass.institution_site_section_id = InstitutionSiteSection.id',
						'InstitutionSiteSectionClass.status = 1'
					)
				),
				array(
					'table' => 'institution_site_class_subjects',
					'alias' => 'InstitutionSiteClassSubject',
					'conditions' => array(
						'InstitutionSiteClassSubject.institution_site_class_id = InstitutionSiteSectionClass.institution_site_class_id',
						'InstitutionSiteClassSubject.status = 1'
					)
				),
				array(
					'table' => 'assessment_items',
					'alias' => 'AssessmentItem',
					'conditions' => array(
						'AssessmentItemType.id = AssessmentItem.assessment_item_type_id',
						'InstitutionSiteClassSubject.education_grade_subject_id = AssessmentItem.education_grade_subject_id',
						'AssessmentItem.visible = 1'
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'AssessmentItemType.education_grade_id = EducationGrade.id',
						'EducationGrade.visible = 1'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationProgramme.visible = 1'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationProgramme.education_cycle_id = EducationCycle.id',
						'EducationCycle.visible = 1'
					)
				)
			),
			'conditions' => array(
				'AssessmentItemType.school_year_id' => array(0, $schoolYearId),
				'AssessmentItemType.institution_site_id' => array(0, $institutionSiteId),
				'AssessmentItemType.visible = 1'
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$gradeName = $obj['EducationCycle']['name'] . ' - ' . $obj['EducationProgramme']['name'] . ' - ' . $obj['EducationGrade']['name'];
			$assessment = $obj['AssessmentItemType'];
			$gradeId = $obj['EducationGrade']['id'];
			if(!array_key_exists($gradeId, $data)) {
				$data[$gradeId] = array('name' => $gradeName, 'items' => array());
			}
			$data[$gradeId]['items'][] = array(
				'id' => $assessment['id'],
				'code' => $assessment['code'],
				'name' => $assessment['name'],
				'description' => $assessment['description']
			);
		}
		//pr($data);die;
		return $data;
	}
	
	public function getYearListForAssessments($institutionSiteId) {
		$data = $this->find('list', array(
			'fields' => array('SchoolYear.id', 'SchoolYear.name'),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.education_grade_id = AssessmentItemType.education_grade_id',
						'InstitutionSiteSectionGrade.status = 1'
					)
				),
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteSection.id = InstitutionSiteSectionGrade.institution_site_section_id',
						'InstitutionSiteSection.institution_site_id = ' . $institutionSiteId
					)
				),
				array(
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => array(
						'InstitutionSiteSection.id = InstitutionSiteSectionClass.institution_site_section_id',
						'InstitutionSiteSectionClass.status = 1'
					)
				),
				array(
					'table' => 'institution_site_class_subjects',
					'alias' => 'InstitutionSiteClassSubject',
					'conditions' => array(
						'InstitutionSiteClassSubject.institution_site_class_id = InstitutionSiteSectionClass.institution_site_class_id',
						'InstitutionSiteClassSubject.status = 1'
					)
				),
				array(
					'table' => 'assessment_items',
					'alias' => 'AssessmentItem',
					'conditions' => array(
						'AssessmentItemType.id = AssessmentItem.assessment_item_type_id',
						'AssessmentItem.education_grade_subject_id = InstitutionSiteClassSubject.education_grade_subject_id',
						'AssessmentItem.visible = 1'
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array(
						'InstitutionSiteSection.school_year_id = SchoolYear.id',
						'SchoolYear.visible = 1'
					)
				)
			),
			'conditions' => array(
				'AssessmentItemType.institution_site_id' => array(0, $institutionSiteId),
				'AssessmentItemType.visible = 1'
			),
			'order' => array('SchoolYear.start_date DESC')
		));

		return $data;
	}
	
	public function groupByGrades($list) {
		$data = array();
		foreach($list as $obj) {
			$educationGradeId = $obj['education_grade_id'];
			if(!array_key_exists($educationGradeId, $data)) {
				$data[$educationGradeId] = array('name' => $obj['education_grade_name'], 'assessment' => array());
			}
			if(!array_key_exists($obj['type'], $data[$educationGradeId]['assessment'])) {
				$data[$educationGradeId]['assessment'][$obj['type']] = array();
			}
			$data[$educationGradeId]['assessment'][$obj['type']][] = $obj;
		}
		return $data;
	}
	
	public function getAssessment($id) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'fields' => array(
				'EducationLevel.name as education_level_name', 'EducationCycle.name as education_cycle_name',
				'EducationProgramme.name as education_programme_name', 'EducationGrade.name as education_grade_name',
				'AssessmentItemType.id', 'AssessmentItemType.code', 'AssessmentItemType.name',
				'AssessmentItemType.description', 'AssessmentItemType.visible', 'AssessmentItemType.education_grade_id',
				'SchoolYear.name as school_year_name'
			),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = AssessmentItemType.education_grade_id')
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
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array('SchoolYear.id = AssessmentItemType.school_year_id')
				)
			),
			'conditions' => array('AssessmentItemType.id' => $id)
		));
		
		if($data) {
			$AssessmentItem = ClassRegistry::init('AssessmentItem');
			$items = $this->getAssessmentItems($id);
			$data['AssessmentItem'] = $items;
		}
		return $data;
	}
	
	public function getAssessmentItems($id) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'fields' => array(
				'AssessmentItem.id', 'AssessmentItem.visible',
				'AssessmentItem.min', 'AssessmentItem.max',
				'EducationGradeSubject.id as education_grade_subject_id', 'EducationGradeSubject.education_subject_id', 'EducationSubject.code', 'EducationSubject.name',
				'EducationSubject.order'
			),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_grade_id = AssessmentItemType.education_grade_id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				),
				array(
					'table' => 'assessment_items',
					'alias' => 'AssessmentItem',
					'type' => 'LEFT',
					'conditions' => array(
						'AssessmentItem.assessment_item_type_id = AssessmentItemType.id',
						'AssessmentItem.education_grade_subject_id = EducationGradeSubject.id'
					)
				)
			),
			'conditions' => array('AssessmentItemType.id' => $id),
			'order' => array('EducationSubject.order')
		));
		return $data ? $data : array();
	}
	
	public function getGradeNameByAssessment($assessmentItemTypeId){
		$data = $this->find('all', array(
			'fields' => array(
				'EducationGrade.id', 'EducationGrade.name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('AssessmentItemType.education_grade_id = EducationGrade.id')
				)
			),
			'conditions' => array('AssessmentItemType.id' => $assessmentItemTypeId)
		));
		
		return $data;
	}
}
