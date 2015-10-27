<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Entity;

class WorkflowStatusesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowStatusMappings', ['className' => 'Workflow.WorkflowStatusMappings', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			// Remove the edit button if the status is not editable
			$isEditable = $this->request->data[$this->alias()]['is_editable'];
			if (! $isEditable) {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		// Remove the edit button and delete button if the status is not editable
		if (! $entity->is_editable) {
			if (isset($buttons['edit'])) {
				unset($buttons['edit']);
			}
		}

		if (! $entity->is_removable) {
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}
		return $buttons;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('name');
		$this->ControllerAction->field('is_editable', ['visible' => ['index'=>false, 'view'=>false, 'edit'=>true, 'add' => true], 'type'=>'hidden', 'value'=>1]);
	}
	
	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['is_editable'] = $entity->is_editable;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if (!$entity->is_editable) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin'=>'Workflow', 'controller' => 'Workflows', 'action' => 'Statuses']);
		}
		$this->request->data[$this->alias()]['workflow_model_id'] = $entity->workflow_model_id;
		$this->ControllerAction->field('workflow_model_id', ['type' => 'readonly', 'value' => $entity->workflow_model_id]);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('workflow_model_id', ['type' => 'select']);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$workflowModelId = $this->request->data[$this->alias()]['workflow_model_id'];
			$attr['attr']['value'] = $this->WorkflowModels->get($workflowModelId)->name;
			return $attr;
		}
	}

	public function getWorkflowStepStatusNameMappings($modelName) {
		return $this
			->find('list')
			->matching('WorkflowStatusMappings')
			->matching('WorkflowModels')
			->where(['WorkflowModels.model' => $modelName])
			->select(['id' => 'WorkflowStatusMappings.workflow_step_id', 'name' => $this->aliasField('name')])
			->toArray();
	}
}
