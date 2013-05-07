<?php
App::uses('AppModel', 'Model');

class AssessmentItemType extends AppModel {
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
	
	public function getAssessmentByTypeAndProgramme($type, $programmeId, $filter=array()) {
		$model = get_class($this) . '.%s';
		$conditions = array(sprintf($model, 'type') => $type);
		if(!empty($filter)) {
			foreach($filter as $key => $val) {
				$conditions[sprintf($model, $key)] = $val;
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
				sprintf($model, 'order'),
				sprintf($model, 'visible'),
				sprintf($model, 'education_grade_id'),
				'EducationGrade.name as education_grade_name'
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
			'order' => array('EducationGrade.order', 'AssessmentItemType.order')
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
			$data[$educationGradeId]['assessment'][] = $obj;
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
				'AssessmentItemType.description', 'AssessmentItemType.visible', 'AssessmentItemType.education_grade_id'
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
				'AssessmentItem.id', 'AssessmentItem.education_grade_subject_id', 'AssessmentItem.visible',
				'AssessmentItem.min', 'AssessmentItem.max',
				'EducationGradeSubject.education_subject_id', 'EducationSubject.code', 'EducationSubject.name',
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
}
