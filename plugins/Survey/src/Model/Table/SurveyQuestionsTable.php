<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class SurveyQuestionsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'Survey.SurveyQuestionChoices', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
