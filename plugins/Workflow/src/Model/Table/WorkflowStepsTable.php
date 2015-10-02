<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

class WorkflowStepsTable extends AppTable {
	use OptionsTrait;

	// Workflow Steps - stage
	const OPEN = 0;
	const PENDING = 1;
	const CLOSED = 2;

	// Workflow Actions - action
	const APPROVE = 0;
	const REJECT = 1;

	private $_fieldOrder = ['workflow_id', 'name', 'security_roles', 'is_editable', 'is_removable'];
	private $_contain = ['WorkflowActions.NextWorkflowSteps', 'SecurityRoles'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'workflow_steps_roles',
			'foreignKey' => 'workflow_step_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Workflow.WorkflowStepsRoles',
			'dependent' => true
		]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		// Auto insert default workflow_actions when add
		if ($entity->isNew()) {
			if ($entity->has('stage') && in_array($entity->stage, [self::OPEN, self::PENDING, self::CLOSED])) {
			} else {
				$data = [
					'workflow_actions' => [
						[
							'name' => __('Approve'),
							'action' => self::APPROVE,
							'visible' => 1,
							'next_workflow_step_id' => 0,
							'comment_required' => 0
						],
						[
							'name' => __('Reject'),
							'action' => self::REJECT,
							'visible' => 1,
							'next_workflow_step_id' => 0,
							'comment_required' => 0
						]
					]
				];
				$entity = $this->patchEntity($entity, $data);
			}
		}

		// Always mark visible to dirty to handle retain Workflow Actions when update all visible to 0
		foreach ($entity->workflow_actions as $key => $obj) {
			$entity->workflow_actions[$key]->dirty('visible', true);
		}
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		$currentAction = $this->ControllerAction->action();

		if ($currentAction == 'index' && $field == 'actions') {
			$label = __('Actions');
			$label .= '<span class="divider"></span>';
			$label .= $this->ControllerAction->Alert->getMessage('WorkflowActions.next_step');

			return $label;
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function onGetIsEditable(Event $event, Entity $entity) {
		return $entity->is_editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetIsRemovable(Event $event, Entity $entity) {
		return $entity->is_removable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetActions(Event $event, Entity $entity) {
		$workflowActions = [];
		foreach ($entity->workflow_actions as $key => $obj) {
			if ($obj->visible == 1) {
				$workflowAction = $obj->name;
				$workflowAction .= '<span class="divider"></span>';
				if (isset($obj->next_workflow_step)) {
					$workflowAction .= $obj->next_workflow_step->name;
				} else {
					$workflowAction .= '(' . __('Not linked') . ')';
				}
				$workflowActions[$key] = $workflowAction;
			}
		}

		return implode('<br>', $workflowActions);
	}

	public function beforeAction(Event $event) {
		$this->fields['stage']['visible'] = false;
		$this->ControllerAction->field('workflow_id');
		$this->ControllerAction->field('is_editable');
		$this->ControllerAction->field('is_removable');
		$this->ControllerAction->field('security_roles', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Security Roles')
		]);

		if ($this->action != 'add') {
			$this->ControllerAction->field('actions', [
				'type' => 'element',
				'element' => 'Workflow.actions',
				'valueClass' => 'table-full-width'
			]);
			$this->_fieldOrder[] = 'actions';
		}

		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

		// Purposely set to string to use onGetActions()
		$this->fields['actions']['type'] = 'string';
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($workflowOptions, $selectedWorkflow) = array_values($this->_getSelectOptions());
		$this->controller->set(compact('workflowOptions', 'selectedWorkflow'));

		$query
			->contain($this->_contain)
			->where([$this->aliasField('workflow_id') => $selectedWorkflow]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// Build Event Options
		$eventOptions = $this->getEvents($entity);
		$this->controller->set('eventOptions', $eventOptions);
		// End
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($workflowOptions, , $securityRoleOptions) = array_values($this->_getSelectOptions());

		$this->fields['workflow_id']['options'] = $workflowOptions;
		$this->fields['workflow_id']['onChangeReload'] = true;
		$this->fields['security_roles']['options'] = $securityRoleOptions;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists($this->alias(), $data)) {
			if (!array_key_exists('workflow_actions', $data[$this->alias()])) {
				$data[$this->alias()]['workflow_actions'] = [];
			}

			// Set all Workflow Actions to visible = 0 (edit)
			if (array_key_exists('id', $data[$this->alias()])) {
				$this->WorkflowActions->updateAll(
					['visible' => 0],
					['workflow_step_id' => $data[$this->alias()]['id']]
				);
			}
		}

		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		// Build Next Step Options
		$where = [
			$this->aliasField('workflow_id') => $entity->workflow_id
		];

		if (isset($entity->id)) { // edit
			//do not allow to edit name of Open and Closed
			$this->fields['name']['attr']['disabled'] = !is_null($entity->stage) ? 'disabled' : '';
			//exclude ownself in nextStepOptions
			$where[$this->aliasField('id !=')] = $entity->id;
		}

		$nextStepOptions = $this
			->find('list')
			->where($where)
			->toArray();
		$this->controller->set('nextStepOptions', $nextStepOptions);
		// End

		// Build Event Options
		$eventOptions = $this->getEvents($entity);
		$this->controller->set('eventOptions', $eventOptions);
		// End
	}

	public function addEditOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'WorkflowActions' => ['validate' => false]
		];
	}

	public function addEditOnAddAction(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$actionOptions = [
			'name' => '',
			'visible' => 1,
			'comment_required' => 0
		];
		$data[$this->alias()]['workflow_actions'][] = $actionOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'WorkflowActions' => ['validate' => false]
		];
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedWorkflow) = array_values($this->_getSelectOptions());
		$entity->workflow_id = $selectedWorkflow;
	}

	public function onUpdateFieldIsEditable(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');

		return $attr;
	}

	public function onUpdateFieldIsRemovable(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');

		return $attr;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		// Do not allow user to delete Open and Closed
		if (!is_null($entity->stage)) {
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}

		return $buttons;
	}

	public function getEvents(Entity $entity) {
		$eventOptions = [];

		// trigger Workflow.getEvents to retrieve the list of available events for the model
		if (isset($entity->workflow_id)) {
			$workflow = $this->Workflows
				->find()
				->matching('WorkflowModels')
				->where([
					$this->Workflows->aliasField('id') => $entity->workflow_id
				])
				->first();

			$registryAlias = $workflow->_matchingData['WorkflowModels']->model;
			$subject = TableRegistry::get($registryAlias);
			$subjectEvent = $subject->dispatchEvent('Workflow.getEvents', null, $subject);
			if ($subjectEvent->isStopped()) { return $subjectEvent->result; }

			$events = $subjectEvent->result;
			if (empty($events)) {
				$eventOptions = [
					0 => $this->ControllerAction->Alert->getMessage('general.select.noOptions')
				];
			} else {
				$eventOptions = [
					0 => __('-- Select Event --')
				];
				foreach ($events as $key => $event) {
					$eventOptions[$key] = $event;
				}
			}
		}

		return $eventOptions;
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$workflowOptions = $this->Workflows
			->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
			->order([
				$this->Workflows->aliasField('workflow_model_id'),
				$this->Workflows->aliasField('code')
			])
			->toArray();
		$selectedWorkflow = !is_null($this->request->query('workflow')) ? $this->request->query('workflow') : key($workflowOptions);

		$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $securityRoleOptions = $SecurityRoles
        	->find('list')
        	->toArray();
        $selectedSecurityRole = key($securityRoleOptions);

		return compact('workflowOptions', 'selectedWorkflow', 'securityRoleOptions', 'selectedSecurityRole');
	}
}
