<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

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
			'dependent' => true
		]);

		$this->addBehavior('Reorder', ['filter' => 'field_option_id']);
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
	}

	public function indexBeforeAction(Event $event) {
		parent::indexBeforeAction($event);
		$this->fields['apply_to_all']['visible'] = false;
		$this->fields['custom_filters']['visible'] = false;
	}

	public function afterAction(Event $event){
		$this->ControllerAction->setFieldOrder(['custom_module_id', 'apply_to_all', 'custom_filters', 'name', 'description', 'custom_fields', 'question']);
	}

	public function onGetCustomModuleId(Event $event, Entity $entity) {
		return $entity->custom_module->code;
	}

	public function onGetCustomQuestionElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "view":

				$tableHeaders = [__('Name'), __('Code'), __('Hours Required')];
				$tableCells = [];

				// $educationSubjects = $entity->extractOriginal(['education_subjects']);
				// foreach ($educationSubjects['education_subjects'] as $key => $obj) {
				// 	if ($obj->_joinData->visible == 1) {
				// 		$rowData = [];
				// 		$rowData[] = $obj->name;
				// 		$rowData[] = $obj->code;
				// 		$rowData[] = $obj->_joinData->hours_required;
				// 		$tableCells[] = $rowData;
				// 	}
				// }
				$attr['tableHeaders'] = $tableHeaders;
		    	$attr['tableCells'] = $tableCells;

				break;

			case "add":
			case "edit":
				$tableHeaders = [__('No.'), __('Questions')];
				$tableCells = [];
				$cellCount = 0;
				$form = $event->subject()->Form;
				// Build Questions options
				$questionOptions = $this->CustomFields
					->find('list')
					->toArray();
				$tableHeaders = [__('Questions')];
				$tableCells = [];

				$arraySubjects = [];

				// Showing the list of the questions that are already added
				if ($this->request->is(['get'])) {
					pr('get');
					$surveyQuestions = $entity->extractOriginal(['custom_fields']);
					// pr($surveyQuestions);die;
					foreach ($surveyQuestions['custom_fields'] as $key => $obj) {
					// 	if ($obj->_joinData->visible == 1) {
						pr($obj);
						$arraySubjects[] = [
							'name' => $obj->name,
							'survey_question_id' => $obj->id,
							'survey_form_id' => $obj->_joinData->survey_form_id,
							// 'code' => $obj->code,
							// 'hours_required' => $obj->_joinData->hours_required,
							// 'education_grade_id' => $obj->_joinData->education_grade_id,
							// 'education_subject_id' => $obj->_joinData->education_subject_id,
							// 'visible' => $obj->_joinData->visible
						];
					}
					// }
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					//	pr('post');
					// if (array_key_exists('custom_fields', $requestData[$this->alias()])) {
					// 	foreach ($requestData[$this->alias()]['custom_fields'] as $key => $obj) {
					// 		$arraySubjects[] = $obj['_joinData'];
					// 	}
					// }
					//pr($entity);
					if (array_key_exists('survey_question_id', $requestData[$this->alias()])) {
						$questionId = $requestData[$this->alias()]['survey_question_id'];
						$questionObj = $this->CustomFields
							->findById($questionId)
							->first();
						$arraySubjects[] = [
							'name' => $questionObj->name,
							'survey_question_id' => $questionObj->id,
							'survey_form_id' => $entity->id,
							'custom_module_id' => $entity->custom_module_id,
						];
					}
					foreach ($arraySubjects as $key => $obj) {
						$questionName = $obj['name'];

						$fieldPrefix = $attr['model'] . '.custom_fields.' . $cellCount++;
						$rowData = [];
						$rowData[] = $questionName;
						$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
						$tableCells[] = $rowData;
					}	
				}

				// Table Headers
				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;

				$questionOptions[-1] = "-- ".__('Add Question') ." --";
	    		ksort($questionOptions);
	    		$attr['options'] = $questionOptions;

				break;
		}
		return $event->subject()->renderElement('Survey.subjects', ['attr' => $attr]);
	}

	public function onGetCustomFieldsElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "index":

				break;
			case "view":
				return 'asd';
				break;

			case "add":
			case "edit":

				break;
		}
		// return $event->subject()->renderElement('Education.subjects', ['attr' => $attr]);
	}
	public function getQuestionsOptions(){
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
