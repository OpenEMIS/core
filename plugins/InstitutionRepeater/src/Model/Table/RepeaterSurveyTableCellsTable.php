<?php
namespace InstitutionRepeater\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class RepeaterSurveyTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		$this->table('institution_repeater_survey_table_cells');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'foreignKey' => 'institution_repeater_survey_id']);
	}
}
