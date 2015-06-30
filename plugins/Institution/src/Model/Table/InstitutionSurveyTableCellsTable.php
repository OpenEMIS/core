<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionSurveyTableCellsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_survey_table_cells');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'institution_site_survey_id']);
	}
}
