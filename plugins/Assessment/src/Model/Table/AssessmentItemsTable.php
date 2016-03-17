<?php
namespace Assessment\Model\Table;

use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class AssessmentItemsTable extends ControllerActionTable {
	use MessagesTrait;
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('GradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'foreignKey' => 'assessment_grading_type_id']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->fields['assessment_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['education_subject_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['weights']['type'] = 'string';

		$this->fields['assessment_grading_type_id']['type'] = 'select';
		$this->fields['assessment_grading_type_id']['options'] = $this->GradingTypes->getList()->toArray();
		$this->fields['grading_type.result_type'] = [
			'type' => 'string',
			'field' => 'result_type',
		];
		$this->fields['grading_type.pass_mark'] = [
			'type' => 'string',
			'field' => 'pass_mark',
		];
		$this->fields['grading_type.max'] = [
			'type' => 'string',
			'field' => 'max',
		];
	}

	public function getFormFields($action = 'edit') {
		if ($action=='edit' || $action=='add') {
			return ['education_subject_id'=>'', 'assessment_id'=>'', 'assessment_grading_type_id'=>'', 'weights'=>'', 'id'=>''];
		} else {
			return ['education_subject_id'=>'', 'assessment_grading_type_id'=>'', 'grading_type.result_type'=>'', 'grading_type.pass_mark'=>'', 'grading_type.max'=>'', 'weights'=>''];
		}
	}

	// public function validationDefault(Validator $validator) {
	// 	$validator
	// 		->allowEmpty('code')
	// 		->add('code', 'ruleUniqueCode', [
	// 		    'rule' => ['checkUniqueCode', 'assessment_grading_type_id'],
	// 		    'last' => true
	// 		])
	// 		->add('code', 'ruleUniqueCodeWithinForm', [
	// 		    'rule' => ['checkUniqueCodeWithinForm', $this->AssessmentGradingTypes],
			   
	// 		])
	// 		;
	// 	return $validator;
	// }

	/**
	 *	Function to get the assessment items id and the subject name and the result type
	 *
	 *	@param integer $assessmentId The assessment ID
	 *
	 *	@return array The array containing the assessment item id, subject name and the result type
	 */
	public function getAssessmentItemSubjects($assessmentId) {
		$subjectList = $this
			->find()
			->matching('EducationSubjects')
			->where([$this->aliasField('assessment_id') => $assessmentId])
			->select([
				'id' => $this->aliasField('id'), 
				'name' => 'EducationSubjects.name', 
				'type' => $this->aliasField('mark_type'),
				'max' => $this->aliasField('max')
			])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		return $subjectList;
	}
}
