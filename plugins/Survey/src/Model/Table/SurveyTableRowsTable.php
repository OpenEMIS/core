<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class SurveyTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
	}
}
