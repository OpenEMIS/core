<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class SurveyQuestionsTable extends CustomFieldsTable {
	protected $_fieldFormat = ['OpenEMIS', 'OpenEMIS_Institution'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'Survey.SurveyQuestionChoices', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldParams', ['className' => 'Survey.SurveyQuestionParams', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'Survey.SurveyForms',
			'joinTable' => 'survey_forms_questions',
			'foreignKey' => 'survey_question_id',
			'targetForeignKey' => 'survey_form_id'
		]);
	}
}
