<?php
namespace InstitutionRepeater\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class RepeaterSurveyAnswersTable extends CustomFieldValuesTable {
	public function initialize(array $config): void {
		$this->setTable('institution_repeater_survey_answers');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'foreignKey' => 'institution_repeater_survey_id']);
	}
}
