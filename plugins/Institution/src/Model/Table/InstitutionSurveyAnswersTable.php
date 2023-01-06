<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class InstitutionSurveyAnswersTable extends CustomFieldValuesTable {
	protected $extra = ['scope' => 'survey_question_id'];

	public function initialize(array $config) {
		$this->table('institution_survey_answers');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'institution_survey_id']);
	}
}
