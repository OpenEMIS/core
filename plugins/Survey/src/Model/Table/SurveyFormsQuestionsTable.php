<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class SurveyFormsQuestionsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);

		$this->removeBehavior('Reorder');
	}

	public function findDropDownQuestions(Query $query, array $options)
	{
		$query
			->matching('CustomFields', function ($q) {
				return $q->where(['field_type' => 'DROPDOWN']);
			});
	}
}
