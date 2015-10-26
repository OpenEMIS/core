<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemResultsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('GradingOptions', ['className' => 'Assessment.AssessmentGradingOptions', 'foreignKey' => 'assessment_grading_option_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}

	public function getAssessmentItemResults($institutionId, $academicPeriodId) {
		$results = $this
			->find()
			->matching('GradingOptions')
			->where([
				$this->aliasField('institution_id') => $institutionId, 
				$this->aliasField('academic_period_id') => $academicPeriodId
			])
			->select(['grade_name' => 'GradingOptions.name', 'grade_code' => 'GradingOptions.code'])
			->autoFields(true)
			->hydrate(false)
			->toArray();
		$returnArray = [];
		foreach ($results as $result) {
			$returnArray[$result['institution_id']][$result['student_id']][$result['assessment_item_id']] = [
					'marks' => $result['marks'], 
					'grade_name' => $result['grade_name'], 
					'grade_code' => $result['grade_code']
				];
		}
		return $returnArray;
	}
}
