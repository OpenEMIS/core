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

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('NextWorkflowSteps', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'next_workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('WorkflowStatuses' , [
			'className' => 'Workflow.WorkflowStatuses',
			'joinTable' => 'workflow_statuses_steps',
			'foreignKey' => 'workflow_step_id',
			'targetForeignKey' => 'workflow_status_id',
			'through' => 'Workflow.WorkflowStatusesSteps',
			'dependent' => true
		]);
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
		// Auto insert default workflow_actions when add
		if ($entity->isNew()) {
			if ($entity->has('stage') && in_array($entity->stage, [self::OPEN, self::PENDING, self::CLOSED])) {
				$data = [
					'workflow_actions' => []
				];
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
			}
			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function onGetIsEditable(Event $event, Entity $entity) {
		return $entity->is_editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetIsRemovable(Event $event, Entity $entity) {
		return $entity->is_removable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('stage', ['visible' => false]);
		$this->ControllerAction->field('security_roles', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Security Roles')
		]);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['workflow_id', 'name', 'security_roles', 'is_editable', 'is_removable']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($modelOptions, $selectedModel) = array_values($this->getModelOptions());
		list($workflowOptions, $selectedWorkflow) = array_values($this->getWorkflowOptions($selectedModel));

		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.WorkflowSteps/controls', 'data' => compact('modelOptions', 'selectedModel', 'workflowOptions', 'selectedWorkflow'), 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$query
			->contain(['SecurityRoles'])
			->where([$this->aliasField('workflow_id') => $selectedWorkflow]);
	}

	public function indexAfterAction(Event $event, $data) {
		$session = $this->request->session();

		$sessionKey = $this->registryAlias() . '.warning';
		if ($session->check($sessionKey)) {
			$warningKey = $session->read($sessionKey);
			$this->Alert->warning($warningKey);
			$session->delete($sessionKey);
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->matching('Workflows')
			->contain(['SecurityRoles']);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		unset($this->request->query['model']);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

		if (!$isDeletable) {
			$session = $this->request->session();
			$sessionKey = $this->registryAlias() . '.warning';
			$session->write($sessionKey, $this->aliasField('restrictDelete'));

			$url = $this->ControllerAction->url('index');
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}

    	$extra['excludedModels'] = [
    		$this->WorkflowActions->alias()
    	];
    }

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'edit') {
			$attr['visible'] = false;
		} else if ($action == 'add') {
			list($modelOptions) = array_values($this->getModelOptions());

			$attr['type'] = 'select';
			$attr['options'] = $modelOptions;
			$attr['onChangeReload'] = 'changeModel';
		}

		return $attr;
	}

	public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$selectedModel = $request->query('model');
			list($workflowOptions) = array_values($this->getWorkflowOptions($selectedModel));

			$attr['options'] = $workflowOptions;
		} else if ($action == 'edit') {
			$entity = $attr['attr']['entity'];
			$workflow = $entity->_matchingData['Workflows'];

			$attr['type'] = 'readonly';
			$attr['value'] = $workflow->id;
			$attr['attr']['value'] = $workflow->code_name;
		}

		return $attr;
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['attr']['entity'];

			list($isEditable) = array_values($this->checkIfCanEditOrDelete($entity));
			if (!$isEditable) {
				$attr['attr']['disabled'] = 'disabled';
			} else {
				$attr['attr']['disabled'] = '';
			}
		}

		return $attr;
	}

	public function onUpdateFieldSecurityRoles(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
	        $securityRoleOptions = $this->SecurityRoles
	        	->find('list')
	        	->toArray();

	        $attr['options'] = $securityRoleOptions;
		}

		return $attr;
	}

	public function onUpdateFieldIsEditable(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			$attr['type'] = 'select';
			$attr['options'] = $this->getSelectOptions('general.yesno');
		}

		return $attr;
	}

	public function onUpdateFieldIsRemovable(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			$attr['type'] = 'select';
			$attr['options'] = $this->getSelectOptions('general.yesno');
		}

		return $attr;
	}

	public function addEditOnChangeModel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) 
    {
        $request = $this->request;
        unset($request->query['model']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
					$request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
				}
			}
		}
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->field('workflow_model_id');
		$this->ControllerAction->field('workflow_id', [
			'attr' => ['entity' => $entity]
		]);
		$this->ControllerAction->field('name', [
			'attr' => ['entity' => $entity]
		]);
		$this->ControllerAction->field('is_editable');
		$this->ControllerAction->field('is_removable');

		$this->ControllerAction->setFieldOrder(['workflow_model_id', 'workflow_id', 'name', 'security_roles', 'is_editable', 'is_removable']);
	}

	private function checkIfCanEditOrDelete($entity) {
		$isEditable = true;
    	$isDeletable = true;

    	// not allow to edit name for Open, Pending For Approval and Closed
		if (!is_null($entity->stage) && in_array($entity->stage, [self::OPEN, self::PENDING, self::CLOSED])) {
			$isEditable = false;
    		$isDeletable = false;
		}

    	return compact('isEditable', 'isDeletable');
	}

	public function getModelOptions() {
		$modelOptions = $this->Workflows->WorkflowModels
			->find('list')
			->toArray();
		$selectedModel = $this->queryString('model', $modelOptions);

		return compact('modelOptions', 'selectedModel');
	}

	public function getWorkflowOptions($selectedModel=null) {
		$workflowOptions = $this->Workflows
			->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
			->where([
				$this->Workflows->aliasField('workflow_model_id') => $selectedModel
			])
			->order([
				$this->Workflows->aliasField('code')
			])
			->toArray();
		$selectedWorkflow = $this->queryString('workflow', $workflowOptions);

		return compact('workflowOptions', 'selectedWorkflow');
	}
}
