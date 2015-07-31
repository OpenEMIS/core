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

	public function onGetCustomModuleId(Event $event, Entity $entity) {
		return $entity->custom_module->code;
	}

	public function onGetCustomSectionElement(Event $event, $action, $entity, $attr, $options=[]) {
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
	public function beforeAction(Event $event){
		parent::beforeAction($event);
		$this->ControllerAction->field('section', ['type' => 'custom_section', 'valueClass' => 'table-full-width']);
		// $this->fields['apply_to_all']['visible'] = false;
		// $this->fields['custom_filters']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
		parent::indexBeforeAction($event);
		$this->fields['apply_to_all']['visible'] = false;
		$this->fields['custom_filters']['visible'] = false;
	}

	public function editBeforeAction(Event $event){
		$this->ControllerAction->field('questions', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Survey.Form/questions',
			'data' => [	
				// 'students'=>[],
				// 'studentOptions'=>[],
				// 'categoryOptions'=>$categoryOptions
			],
			'visible' => ['edit'=>true]
			// 'visible' => false
		]);
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
