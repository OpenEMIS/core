<?php
namespace Institution\Model\Table;
use Cake\Event\Event;
use Cake\ORM\Entity;
use CustomField\Model\Table\CustomTableCellsTable;
use Cake\Log\Log;
class InstitutionSurveyTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'institution_survey_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'foreignKey' => 'survey_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'foreignKey' => 'survey_table_column_id']);
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['Model.InstitutionSurveys.afterSave'] = 'institutionSurveyAfterSave';
		return $events;
	}

	public function institutionSurveyAfterSave(Event $event, Entity $institutionSurveyEntity)
	{
		$deleteTableCells = $institutionSurveyEntity->delete_table_cells;
		if (!empty($deleteTableCells)) {
			$conditions = [
				'institution_survey_id' => $institutionSurveyEntity->id
			];
			foreach ($deleteTableCells as $key => $value) {
				$conditions['OR'][] = [
					'survey_question_id' => $value['survey_question_id'],
					'survey_table_row_id' => $value['survey_table_row_id'],
					'survey_table_column_id' => $value['survey_table_column_id']
				];
			}
			$this->deleteAll($conditions);
		}
	}
}
