<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class SurveyFormQuestionsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
	}
}
