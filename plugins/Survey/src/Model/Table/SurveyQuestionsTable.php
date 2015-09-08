<?php
namespace Survey\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFieldsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

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

	public function editOnInitialize(Event $event, Entity $entity) {
		parent::editOnInitialize($event, $entity);
		foreach ($entity->custom_field_params as $key => $fieldParam) {
            if ($fieldParam->param_key == 'survey_form_id') {
            	$entity->survey_form = $fieldParam->param_value;
            }
        }
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		parent::addEditBeforePatch($event, $entity, $data, $options);

		if (array_key_exists($this->alias(), $data)) {
			if (array_key_exists('field_type', $data[$this->alias()])) {
				if ($data[$this->alias()]['field_type'] == 'STUDENT_LIST') {
					if ($entity->isNew()) {
						$data[$this->alias()]['custom_field_params'][] = [
							'param_key' => 'survey_form_id',
							'param_value' => $data[$this->alias()]['survey_form']
						];
					}
				}
			}
		}
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}

		return $attr;
	}
}
