<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Query;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable {

	public function initialize(array $config) 
	{
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		
		//$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->hasMany('AssessmentItemsGradingTypes', ['className' => 'Assessment.AssessmentItemsGradingTypes', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->belongsToMany('GradingTypes', [
			'className' => 'Assessment.AssessmentGradingTypes',
			'joinTable' => 'assessment_items_grading_types',
			'foreignKey' => 'assessment_item_id',
			'targetForeignKey' => 'assessment_grading_type_id',
			'through' => 'Assessment.AssessmentItemsGradingTypes',
			'dependent' => true,
			'cascadeCallbacks' => true
			// 'saveStrategy' => 'append'
		]);

		// $this->belongsToMany('Assessments', [
		// 	'className' => 'Assessment.Assessments',
		// 	'joinTable' => 'assessment_items_grading_types',
		// 	'foreignKey' => 'assessment_item_id',
		// 	'targetForeignKey' => 'assessment_id',
		// 	'through' => 'Assessment.AssessmentItemsGradingTypes',
		// 	'dependent' => true,
		// 	'cascadeCallbacks' => true
		// 	// 'saveStrategy' => 'append'
		// ]);

		$this->belongsToMany('AssessmentPeriods', [
			'className' => 'Assessment.AssessmentPeriods',
			'joinTable' => 'assessment_items_grading_types',
			'foreignKey' => 'assessment_item_id',
			'targetForeignKey' => 'assessment_period_id',
			'through' => 'Assessment.AssessmentItemsGradingTypes',
			'dependent' => true,
			'cascadeCallbacks' => true
			// 'saveStrategy' => 'append'
		]);

		// $this->fields['assessment_id']['type'] = 'hidden';
		// $this->fields['id']['type'] = 'hidden';
		// $this->fields['education_subject_id']['type'] = 'readonly';
		// $this->fields['academic_period_id']['type'] = 'select';
		// $this->fields['weight']['type'] = 'string';

		// $this->fields['assessment_grading_type_id']['type'] = 'select';
		// $this->fields['assessment_grading_type_id']['options'] = $this->GradingTypes->getList()->toArray();
		// $this->fields['assessment_grading_type_id']['required'] = true;
		// $this->fields['grading_type.result_type'] = [
		// 	'type' => 'string',
		// 	'field' => 'result_type',
		// ];
		// $this->fields['grading_type.pass_mark'] = [
		// 	'type' => 'string',
		// 	'field' => 'pass_mark',
		// ];
		// $this->fields['grading_type.max'] = [
		// 	'type' => 'string',
		// 	'field' => 'max',
		// ];
	}

	// public function getFormFields($action = 'edit') 
	// {
	// 	// if ($action=='add') {
	// 	// 	return ['education_subject_id'=>'', 'assessment_grading_type_id'=>'', 'weight'=>''];
	// 	// } else if ($action=='edit') {
	// 	// 	return ['education_subject_id'=>'', 'assessment_id'=>'', 'assessment_grading_type_id'=>'', 'weight'=>'', 'id'=>''];
	// 	// } else {
	// 	// 	return ['education_subject_id'=>'', 'assessment_grading_type_id'=>'', 'grading_type.result_type'=>'', 'grading_type.pass_mark'=>'', 'grading_type.max'=>'', 'weight'=>''];
	// 	// }
	// 	return ['education_subject_id'=>'', 'weight'=>''];
	// }

	public function validationDefault(Validator $validator) 
	{
		$validator = parent::validationDefault($validator);

		$validator
			->add('weight', 'ruleIsDecimal', [
				'rule' => ['decimal', null],
			]);
		return $validator;
	}

	public function populateAssessmentItemsArray($gradeId) 
	{
		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$gradeSubjects = $EducationGradesSubjects->find()
			->contain('EducationSubjects')
			->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
			->toArray();

		$assessmentItems = [];
		foreach ($gradeSubjects as $key => $gradeSubject) {
			if (!empty($gradeSubject->education_subject)) {
				$assessmentItems[] = [
				    'education_subject_id' => $gradeSubject->education_subject->id,
				    'education_subject' => $gradeSubject->education_subject,
					'weight' => '0.00'
				];
			}
		}
		return $assessmentItems;
	}

	/**
	 *	Function to get the assessment items id and the subject name and the result type
	 *
	 *	@param integer $assessmentId The assessment ID
	 *
	 *	@return array The array containing the assessment item id, subject name and the result type
	 */
	// public function getAssessmentItemSubjects($assessmentId) 
	// {
	// 	$subjectList = $this
	// 		->find()
	// 		->matching('EducationSubjects')
	// 		->where([$this->aliasField('assessment_id') => $assessmentId])
	// 		->select([
	// 			'id' => $this->aliasField('id'), 
	// 			'name' => 'EducationSubjects.name', 
	// 			'type' => $this->aliasField('mark_type'),
	// 			'max' => $this->aliasField('max')
	// 		])
	// 		->order(['EducationSubjects.order'])
	// 		->hydrate(false)
	// 		->toArray();
	// 	return $subjectList;
	// }

	public function getAssessmentItemSubjects($assessmentId) 
	{
		$subjectList = $this
			->find()
			->matching('EducationSubjects')
			->where([$this->aliasField('assessment_id') => $assessmentId])
			->select([
				'assessment_item_id' => $this->aliasField('id'), 
				'education_subject_name' => 'EducationSubjects.name'
			])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		// pr($subjectList);
		return $subjectList;
	}

	// public function findGradingTypes(Query $query, array $options) {
			// $assessmentId = $options['assessment_id'];
			// $academicPeriodId = $options['academic_period_id'];
	// }

	// public function findStaffSubjects(Query $query, array $options) 
	// {	
	// 	if (isset($options['class_id']) && isset($options['staff_id'])) 
	// 	{
	// 		$classId = $options['class_id'];
	// 		$staffId = $options['staff_id'];

	// 		$query->where([
	// 				// For subject teachers
	// 				'EXISTS (
	// 					SELECT 1 
	// 					FROM institution_subjects InstitutionSubjects
	// 					INNER JOIN institution_class_subjects InstitutionClassSubjects
	// 						ON InstitutionClassSubjects.institution_class_id = '.$classId.'
	// 						AND InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id
	// 					INNER JOIN institution_subject_staff InstitutionSubjectStaff
	// 						ON InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id
	// 						AND InstitutionSubjectStaff.staff_id = '.$staffId.'
	// 					WHERE InstitutionSubjects.education_subject_id = ' . $this->aliasField('education_subject_id') .')'
	// 			]);

	// 		return $query;
	// 	}
	// }
}
