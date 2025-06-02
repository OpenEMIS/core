<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use ArrayObject;
use Cake\Log\Log;

class SurveyFormsQuestionsTable extends CustomFormsFieldsTable {
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);

        $this->hasOne('SurveyRules', [  // ✅ only ONE rule per form-question
            'className' => 'Survey.SurveyRules',
            'foreignKey' => 'survey_question_id',
            'bindingKey' => 'survey_question_id',
            'conditions' => [
                'SurveyRules.survey_form_id = SurveyFormsQuestions.survey_form_id'
            ],
            'joinType' => 'LEFT'
        ]);

//        $this->hasMany('SurveyQuestionChoices', [
//            'className' => 'CustomField.CustomFieldOptions',
//            'foreignKey' => 'survey_question_id',
//            'bindingKey' => 'survey_question_id'
//        ]);
		$this->addBehavior('Reorder', ['enabled' => false]);
		// $this->removeBehavior('Reorder');
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
			return $query;
	}

	public function findSurveyFormChoices(Query $query, array $options)
	{
        // POCOR-9147
        $section = $options['section'] ?? -1;
        $survey_form_id = $options['survey_form_id'] ?? -1;
        $survey_question_id = $options['survey_question_id'] ?? -1;
        if($section) {
            $query->where(['SurveyFormsQuestions.section' => $section]);
        }
        if($survey_form_id) {
            $query->where(['SurveyFormsQuestions.survey_form_id' => $survey_form_id]);
        }
        if($survey_question_id) {
            $query->where([$this->aliasField('survey_question_id') => $survey_question_id]);
        }
		$query
			->select(['survey_question_choice_id' => 'SurveyQuestionChoices.id',
                'survey_question_choice_name' => 'SurveyQuestionChoices.name'])
			->innerJoin(
				['SurveyQuestionChoices' => 'survey_question_choices'],
				['SurveyQuestionChoices.survey_question_id = '.$this->aliasField('survey_question_id')]
			)
			->order(['SurveyQuestionChoices.order'])
			->enableAutoFields(true)
			;
			return $query;
	}
    // POCOR-9147
	public function findForSurveyRules(Query $query, array $options)
	{
//        Log::debug('SurveyFormsQuestionsTable::findSurveyRules');
//        Log::debug(print_r($options, true));
        // POCOR-9147 start
        $section = $options['section'] ?? null;
        $survey_form_id = $options['survey_form_id'] ?? null;
        if($section) {
            $query->where(['SurveyFormsQuestions.section' => $section]);
        }
        if($survey_form_id) {
            $query->where(['SurveyFormsQuestions.survey_form_id' => $survey_form_id]);
        }
        // POCOR-9147 end
		$query
            ->contain([
                'SurveyRules',
                'CustomFields' => ['CustomFieldOptions']
            ])
			->enableAutoFields(true) // POCOR-8465
			->order([$this->aliasField('order') => 'ASC'])// POCOR-8729
			;
//        Log::debug($query->sql());
		return $query;
	}
}
