<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('GradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'foreignKey' => 'assessment_grading_type_id']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

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
				'type' => $this->aliasField('result_type'),
				'max' => $this->aliasField('max')
			])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		return $subjectList;
	}
}
