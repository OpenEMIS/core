<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use ArrayObject;

class SurveyFormsQuestionsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);

		$this->removeBehavior('Reorder');
		$this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$SurveyRules = TableRegistry::get('Survey.SurveyRules');
		$entities = $SurveyRules
			->find()
			->where([
				$SurveyRules->aliasField('survey_form_id') => $entity->survey_form_id,
				$SurveyRules->aliasField('survey_question_id') => $entity->survey_question_id
			])
			->toArray();
		foreach($entities as $entity) {
			$SurveyRules->delete($entity);
		}
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

	public function findSurveyRules(Query $query, array $options)
	{
		$query
			->leftJoin(
				['SurveyRules' => 'survey_rules'],
				[
					'SurveyRules.survey_form_id = '.$this->aliasField('survey_form_id'),
					'SurveyRules.survey_question_id = '.$this->aliasField('survey_question_id')
				]
			)
			->select([
				'survey_rule_enabled' => 'SurveyRules.enabled',
				'dependent_question' => 'SurveyRules.dependent_question_id',
				'show_options' => 'SurveyRules.show_options',
			])
			->autoFields(true)
			;
	}
}
