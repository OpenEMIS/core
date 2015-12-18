<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemResultsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('GradingOptions', ['className' => 'Assessment.AssessmentGradingOptions', 'foreignKey' => 'assessment_grading_option_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}
}
