<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;
use Cake\ORM\Query;

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

	public function findSurveyFormChoices(Query $query, array $options)
	{
		$query
			->select(['survey_question_choice_id' => 'SurveyQuestionChoices.id', 'survey_question_choice_name' => 'SurveyQuestionChoices.name'])
			->innerJoin(
				['SurveyQuestionChoices' => 'survey_question_choices'],
				['SurveyQuestionChoices.survey_question_id = '.$this->aliasField('survey_question_id')]
			)
			->order(['SurveyQuestionChoices.order'])
			->autoFields(true)
			;
	}
}
