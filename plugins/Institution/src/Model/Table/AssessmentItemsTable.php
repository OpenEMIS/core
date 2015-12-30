<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AssessmentItemsTable extends AppTable {
	public function initialize(array $config) {
		// $this->table('assessment_results');
        parent::initialize($config);
		
		$this->belongsTo('AssessmentItemTypes', ['className' => 'Institution.AssessmentItemTypes']);
		
		// public $belongsTo = array(
		// 	'AssessmentItemType'
		// );
	}

	// public function getItem($id) {
	// 	$data = $this->find('first', array(
	// 		'fields' => array(
	// 			'AssessmentItemType.id', 'AssessmentItemType.name', 'AssessmentItemType.visible',
	// 			'AssessmentItem.id', 'AssessmentItem.min', 'AssessmentItem.max',
	// 			'EducationGradeSubject.education_grade_id',
	// 			'EducationSubject.code', 'EducationSubject.name'
	// 		),
	// 		'joins' => array(
	// 			array(
	// 				'table' => 'education_grades_subjects',
	// 				'alias' => 'EducationGradeSubject',
	// 				'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
	// 			),
	// 			array(
	// 				'table' => 'education_subjects',
	// 				'alias' => 'EducationSubject',
	// 				'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
	// 			),
	// 			array(
	// 				'table' => 'assessment_item_types',
	// 				'alias' => 'AssessmentItemType',
	// 				'conditions' => array('AssessmentItemType.id = AssessmentItem.assessment_item_type_id')
	// 			)
	// 		),
	// 		'conditions' => array('AssessmentItem.id' => $id)
	// 	));
	// 	return $data;
	// }
	
	// public function getItemList($assessmentId) {
	// 	$data = $this->find('list', array(
	// 		'fields' => array('AssessmentItem.id', 'EducationSubject.name'),
	// 		'joins' => array(
	// 			array(
	// 				'table' => 'education_grades_subjects',
	// 				'alias' => 'EducationGradeSubject',
	// 				'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
	// 			),
	// 			array(
	// 				'table' => 'education_subjects',
	// 				'alias' => 'EducationSubject',
	// 				'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
	// 			)
	// 		),
	// 		'conditions' => array('assessment_item_type_id' => $assessmentId),
	// 		'order' => array('EducationSubject.order')
	// 	));
	// 	return $data;
	// }
	
	// public function getClassItemList($assessmentId, $classId) {
	// 	$data = $this->find('list', array(
	// 		'fields' => array('AssessmentItem.id', 'EducationSubject.name'),
	// 		'joins' => array(
	// 			array(
	// 				'table' => 'education_grades_subjects',
	// 				'alias' => 'EducationGradeSubject',
	// 				'conditions' => array(
	// 					'EducationGradeSubject.id = AssessmentItem.education_grade_subject_id',
	// 					'EducationGradeSubject.visible' => 1
	// 				)
	// 			),
	// 			array(
	// 				'table' => 'education_subjects',
	// 				'alias' => 'EducationSubject',
	// 				'conditions' => array(
	// 					'EducationSubject.id = EducationGradeSubject.education_subject_id',
	// 					'EducationSubject.visible' => 1
	// 				)
	// 			),
	// 			array(
	// 				'table' => 'institution_classes',
	// 				'alias' => 'InstitutionClass',
	// 				'conditions' => array(
	// 					'InstitutionClass.education_subject_id = EducationGradeSubject.education_subject_id',
	// 					'InstitutionClass.id = ' . $classId
	// 				)
	// 			),
	// 		),
	// 		'conditions' => array(
	// 			'AssessmentItem.assessment_item_type_id' => $assessmentId,
	// 			'AssessmentItem.visible' => 1
	// 		),
	// 		'order' => array('EducationSubject.order')
	// 	));
	// 	return $data;
	// }
}
