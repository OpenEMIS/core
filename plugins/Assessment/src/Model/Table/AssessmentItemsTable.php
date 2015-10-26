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

	public function getAssessmentItemSubjects($assessmentId) {
		$subjectList = $this
			->find()
			->matching('EducationSubjects')
			->where([$this->aliasField('assessment_id') => $assessmentId])
			->select(['id' => $this->aliasField('id'), 'name' => 'EducationSubjects.name', 'type' => $this->aliasField('result_type')])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		return $subjectList;
	}
}
