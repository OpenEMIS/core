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
    	if ($institutionSurveyEntity->delete_table_cells) {
	    	$textValueConditions['OR'] = [
	    		'text_value' => '',
	    		'isnull(text_value)',
	    	];
	    	$decimalValueConditions['OR'] = [
	    		'decimal_value' => '',
	    		'isnull(decimal_value)',
	    	];
	    	$conditions = [
	    		'institution_survey_id' => $institutionSurveyEntity->id,
	    		'isnull(number_value)',
	    		$textValueConditions,
	    		$decimalValueConditions
	    	];
			$this->deleteAll($conditions);
    	}
    }
}
