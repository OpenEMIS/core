<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class SurveyFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		$config['extra'] = [
			'fieldClass' => [
				'className' => 'Survey.SurveyQuestions',
				'joinTable' => 'survey_forms_questions',
				'foreignKey' => 'survey_form_id',
				'targetForeignKey' => 'survey_question_id',
				'through' => 'Survey.SurveyFormsQuestions',
				'dependent' => true
			],
			'label' => [
				'custom_fields' => 'Survey Questions',
				'add_field' => 'Add Question',
				'fields' => 'Questions'
			]
		];
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('SurveyStatuses', ['className' => 'Survey.SurveyStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
		// The hasMany association for InstitutionSurveys and StudentSurveys is done in onBeforeDelete() and is added based on module to avoid conflict.
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'custom_module_id']],
			        'provider' => 'table',
			        'message' => 'This name already exists in the system'
			    ]
		    ])
	    	->add('code', [
	    		'unique' => [
			        'rule' => ['validateUnique'],
			        'provider' => 'table',
			        'message' => 'This code already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function afterAction(Event $event){
		// unset($this->fields['custom_fields']);
		$this->ControllerAction->setFieldOrder(['custom_module_id', 'code', 'name', 'description']);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('code');
	}

	public function onGetCustomModuleId(Event $event, Entity $entity) {
		return $entity->custom_module->code;
	}

	public function indexAfterAction(Event $event, $data) {
		parent::indexAfterAction($event, $data);
		unset($this->fields['apply_to_all']);
		unset($this->fields['custom_filters']);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		parent::viewAfterAction($event, $entity);
		unset($this->fields['apply_to_all']);
		unset($this->fields['custom_filters']);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		parent::addEditAfterAction($event, $entity);
		unset($this->fields['apply_to_all']);
		unset($this->fields['custom_filters']);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$surveyForm = $this->get($id);
		$customModule = $this->CustomModules
			->find()
			->where([
				$this->CustomModules->aliasField('id') => $surveyForm->custom_module_id
			])
			->first();

		$model = $customModule->model;
		if ($model == 'Institution.Institutions') {
			$this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
		} else if ($model == 'Student.Students') {
			$this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if ($this->AccessControl->check([$this->controller->name, 'Forms', 'download'])) {
				$id = $buttons['download']['url'][1];
				$toolbarButtons['download'] = $buttons['download'];
				$toolbarButtons['download']['url'] = [
					'plugin' => 'Restful',
					'controller' => 'Rest',
					'action' => 'survey',
					'download',
					'xform',
					$id,
					0
				];
				$toolbarButtons['download']['type'] = 'button';
				$toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
				$toolbarButtons['download']['attr'] = $attr;
				$toolbarButtons['download']['attr']['title'] = __('Download');
			}
		}
	}

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (!$request->is('post')) {
				$textValue = substr(Text::uuid(), 0, 8);
				$attr['attr']['value'] = $textValue;
			}
			return $attr;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($this->AccessControl->check([$this->controller->name, 'Forms', 'download'])) {
			if (array_key_exists('view', $buttons)) {
				$downloadButton = $buttons['view'];
				$downloadButton['url'] = [
					'plugin' => 'Restful',
					'controller' => 'Rest',
					'action' => 'survey',
					'download',
					'xform',
					$entity->id,
					0
				];
				$downloadButton['label'] = '<i class="kd-download"></i>' . __('Download');
				$buttons['download'] = $downloadButton;
			}
		}

		return $buttons;
	}

	public function getModuleQuery() {
		return $this->CustomModules
			->find('list', ['keyField' => 'id', 'valueField' => 'code'])
			->find('visible')
			->where([
				$this->CustomModules->aliasField('parent_id') => 0
			]);
	}
}
