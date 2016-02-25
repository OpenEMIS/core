<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class InstitutionSurveyTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'institution_survey_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'foreignKey' => 'survey_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'foreignKey' => 'survey_table_column_id']);
	}
}
