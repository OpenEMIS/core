<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;

class SurveyFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('SurveyStatuses', ['className' => 'Survey.SurveyStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomFields', [
			'className' => 'Survey.SurveyQuestions',
			'joinTable' => 'survey_forms_questions',
			'foreignKey' => 'survey_form_id',
			'targetForeignKey' => 'survey_question_id',
			'through' => 'Survey.SurveyFormsQuestions',
			'dependent' => true,
		]);

		//$this->addBehavior('Reorder', ['filter' => 'field_option_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'custom_module_id']],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function beforeAction(Event $event){
		parent::beforeAction($event);
		$this->ControllerAction->field('question', ['type' => 'custom_question', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
		//$this->ControllerAction->field('custom_fields', ['visible' => false]);
		//$this->ControllerAction->field('custom_fields');
	}

	public function indexBeforeAction(Event $event) {
		parent::indexBeforeAction($event);
		$this->fields['apply_to_all']['visible'] = false;
		$this->fields['custom_filters']['visible'] = false;
	}

	public function afterAction(Event $event){
		unset($this->fields['custom_fields']);
		$this->ControllerAction->setFieldOrder(['custom_module_id', 'apply_to_all', 'custom_filters', 'name', 'description', 'question']);
	}

	public function onGetCustomModuleId(Event $event, Entity $entity) {
		return $entity->custom_module->code;
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

		// To handle when delete all subjects
		if (!array_key_exists('custom_fields', $data[$this->alias()])) {
			$data[$this->alias()]['custom_fields'] = [];
		}

		// Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = ['CustomFields._joinData'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function onGetCustomQuestionElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "index":
				// No implementation yet
				break;

			case "view":
				$tableHeaders = [__('Questions')];
				$tableCells = [];

				$surveyFormId = $this->request->pass[1];
				$surveyQuestions = $this->SurveyFormsQuestions
					->find('all')
					->select([
							'id' => 'SurveyFormsQuestions.survey_question_id',
							'name' => 'SurveyQuestions.name'
						])		
					->innerJoin(['SurveyQuestions' => 'survey_questions'],
						[
							'SurveyQuestions.id = ' . $this->SurveyFormsQuestions->aliasField('survey_question_id'),
						]
					)
					->order(['SurveyFormsQuestions.order'])
					->where(['SurveyFormsQuestions.survey_form_id' => $surveyFormId])
					->toArray();

				foreach ($surveyQuestions as $key => $obj) {
						$rowData = [];
						$rowData[] = $obj['name'];
						$tableCells[] = $rowData;
				}
				$attr['tableHeaders'] = $tableHeaders;
		    	$attr['tableCells'] = $tableCells;
				break;

			case "add":
			case "edit":
				$tableHeaders = [__('Questions'), '' , ''];
				$tableCells = [];
				$cellCount = 0;
				$form = $event->subject()->Form;
				// Build Questions options
				$questionOptions = $this->CustomFields
					->find('list')
					->toArray();
				
				$arrayQuestions = [];
				// Showing the list of the questions that are already added
				if ($this->request->is(['get'])) {
					$surveyFormId = $this->request->pass[1];
					$surveyQuestions = $this->SurveyFormsQuestions
						->find('all')
						->select([
								'name' => 'SurveyQuestions.name',
								'survey_question_id' => 'SurveyFormsQuestions.survey_question_id',
								'survey_form_id' => 'SurveyFormsQuestions.survey_form_id',
								'id' => 'SurveyFormsQuestions.id'

							])		
						->innerJoin(['SurveyQuestions' => 'survey_questions'],
							[
								'SurveyQuestions.id = ' . $this->SurveyFormsQuestions->aliasField('survey_question_id'),
							]
						)
						->order(['SurveyFormsQuestions.order'])
						->where(['SurveyFormsQuestions.survey_form_id' => $surveyFormId])
						->toArray();

					foreach ($surveyQuestions as $key => $obj) {
						$arrayQuestions[] = [
							'name' => $obj->name,
							'survey_question_id' => $obj->survey_question_id,
							'survey_form_id' => $obj->survey_form_id,
							'id' => $obj->id
						];
					}
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('custom_fields', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['custom_fields'] as $key => $obj) {
							if(!empty($obj['_joinData']['id'])){
								$arrayQuestions[] = [
									'name' => $obj['_joinData']['name'],
									'survey_question_id' => $obj['id'],
									'survey_form_id' => $obj['_joinData']['survey_form_id'],
									'id' => $obj['_joinData']['id']
								];
							}else{
								$arrayQuestions[] = [
									'name' => $obj['_joinData']['name'],
									'survey_question_id' => $obj['id'],
									'survey_form_id' => $obj['_joinData']['survey_form_id'],
								];
							}
						}
					}
					if (array_key_exists('survey_question_id', $requestData[$this->alias()])) {
						$questionId = $requestData[$this->alias()]['survey_question_id'];
						$questionObj = $this->CustomFields->get($questionId);
						$arrayQuestions[] = [
							'name' => $questionObj->name,
							'survey_question_id' => $questionObj->id,
							'survey_form_id' => $entity->id,
							'custom_module_id' => $entity->custom_module_id,
						];
					}
				}
				$count = 0;
				foreach ($arrayQuestions as $key => $obj) {
					$fieldPrefix = $attr['model'] . '.custom_fields.' . $cellCount++;
					$joinDataPrefix = $fieldPrefix . '._joinData';

					$surveyQuestionName = $obj['name'];
					$surveyQuestionId = $obj['survey_question_id'];
					$surveyFormId = $obj['survey_form_id'];

					$cellData = "";
					$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $surveyQuestionId]);
					$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $surveyQuestionName]);
					$cellData .= $form->hidden($joinDataPrefix.".survey_form_id", ['value' => $surveyFormId]);
					$cellData .= $form->hidden($joinDataPrefix.".survey_question_id", ['value' => $surveyQuestionId]);
					$cellData .= $form->hidden($joinDataPrefix.".order", ['value' => ++$count, 'class' => 'order']);
					$cellData .= $form->hidden($joinDataPrefix.".section", ['value' => '', 'class' => 'section']);
					
					if (isset($obj['id'])) {
						$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
					}
					$rowData = [];
					$rowData[] = $surveyQuestionName;
					$rowData[] = $cellData;
					$rowData[] = '<button onclick="jsTable.doRemoveWithAppend(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
					$rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
					$tableCells[] = $rowData;

					unset($questionOptions[$obj['survey_question_id']]);
				}
				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;
	    		$attr['reorder'] = true;

				$questionOptions[-1] = "-- ".__('Add Question') ." --";
	    		ksort($questionOptions);
	    		$attr['options'] = $questionOptions;
				break;

		}
		return $event->subject()->renderElement('Survey.subjects', ['attr' => $attr]);
	}


	public function _getSelectOptions() {
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values(parent::_getSelectOptions());
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->CustomModules
			->find('list', ['keyField' => 'id', 'valueField' => 'code'])
			->find('visible')
			->where([
				$this->CustomModules->aliasField('parent_id') => 0
			])
			->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
