<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class WorkflowStatusesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->belongsToMany('WorkflowSteps' , [
			'className' => 'Workflow.WorkflowSteps',
			'joinTable' => 'workflow_statuses_steps',
			'foreignKey' => 'workflow_status_id',
			'targetForeignKey' => 'workflow_step_id',
			'through' => 'Workflow.WorkflowStatusesSteps',
			'dependent' => true
		]);
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
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
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

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// To handle when delete all mappings
		if (!array_key_exists('workflow_steps', $data[$this->alias()])) {
			$data[$this->alias()]['workflow_steps'] = [];
		}

		// Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = ['WorkflowSteps._joinData'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function onGetStatusesStepsElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action) {
			case 'view':
				$tableHeaders = [__('Workflow Statuses Steps Mapping')];
				$tableCells = [];
				$workflowStatusId = $this->request->pass[1];
				$workflowSteps = $this->getWorkflowSteps($workflowStatusId);
				foreach ($workflowSteps as $key => $step) {
					$rowData = [];
					$rowData[] = $step;
					$tableCells[] = $rowData;
				}
				$attr['tableHeaders'] = $tableHeaders;
				$attr['tableCells'] = $tableCells;
				break;

			case 'edit':
				$tableHeaders = [__('Workflow Statuses Steps Mapping'),''];
				$form = $event->subject()->Form;
				$tableCells = [];
				$arraySteps = [];
				if ($this->request->is(['get'])) {
					if(isset($this->request->pass[1])){
						$modelId = $this->request->pass[1];
						$steps = $this->getSteps($modelId);
						foreach($steps as $step) {
							$stepInfo = $step['_matchingData']['WorkflowSteps'];
							$arraySteps[] = [
								'status_id' => $step['id'],
								'step_id' => $stepInfo['id'],
								'name' => $stepInfo['name'],
								'stage' => $stepInfo['stage']
							];
						}
					}

				} else if ($this->request->is(['post', 'put'])) {

				}
				$cellCount = 0;
				foreach($arraySteps as $obj) {
					$fieldPrefix = $attr['model'] . '.workflow_steps.' . $cellCount++;
					$joinDataPrefix = $fieldPrefix . '._joinData';
					$statusId = $obj['status_id'];
					$stepId = $obj['step_id'];
					$stepName = $obj['name'];

					$cellData = "";
					$cellData .= $form->hidden($fieldPrefix.".workflow_step_id", ['value' => $stepId]);
					$cellData .= $form->hidden($fieldPrefix.".workflow_status_id", ['value' => $statusId]);

					$rowData = [];
					$rowData[] = $stepName.$cellData;
					$rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
					$tableCells[] = $rowData;
				}
				$attr['tableHeaders'] = $tableHeaders;
				$attr['tableCells'] = $tableCells;
				break;
		}

		return $event->subject()->renderElement('Workflow.mappings', ['attr' => $attr]);
	}

	public function getSteps($statusId) {
		return $this
			->find()
			->matching('WorkflowSteps')
			->where([$this->aliasField('id') => $statusId])
			->hydrate(false)
			->toArray();
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('name');
		$this->ControllerAction->field('is_editable', ['visible' => ['index'=>false, 'view'=>false, 'edit'=>true, 'add' => true], 'type'=>'hidden', 'value'=>1]);
		$this->ControllerAction->field('is_removable', ['visible' => ['index'=>false, 'view'=>false, 'edit'=>true, 'add' => true], 'type'=>'hidden', 'value'=>1]);
		$this->ControllerAction->field('statuses_steps', ['type' => 'statuses_steps', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
	}
	
	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['is_editable'] = $entity->is_editable;
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if (!$entity->is_editable) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin'=>'Workflow', 'controller' => 'Workflows', 'action' => 'Statuses']);
		}
		$this->request->data[$this->alias()]['workflow_model_id'] = $entity->workflow_model_id;
		$this->ControllerAction->field('workflow_model_id', ['type' => 'readonly', 'value' => $entity->workflow_model_id]);
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('workflow_model_id', ['type' => 'select']);
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$workflowModelId = $this->request->data[$this->alias()]['workflow_model_id'];
			$attr['attr']['value'] = $this->WorkflowModels->get($workflowModelId)->name;
			return $attr;
		}
	}

	public function getWorkflowStepStatusNameMappings($model) {
		return $this
			->find('list')
			->matching('WorkflowSteps')
			->matching('WorkflowModels')
			->where(['WorkflowModels.model' => $model])
			->select(['id' => 'WorkflowSteps.id', 'name' => $this->aliasField('name')])
			->toArray();
	}

	public function getWorkflowSteps($workflowStatusId) {
		return $this
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'name'
			])
			->matching('WorkflowSteps')
			->where([$this->aliasField('id') => $workflowStatusId])
			->select(['id' => 'WorkflowSteps.id', 'name' => 'WorkflowSteps.name'])
			->toArray();
	}
}
