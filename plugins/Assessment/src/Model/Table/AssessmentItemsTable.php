<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('GradingTypes', ['className' => 'Assessment.AssessmentGradingTypes']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		// $this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults']);
	}
}
