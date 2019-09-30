<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

class WorkflowStatusesTable extends AppTable {
	private $_contain = ['WorkflowSteps'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->belongsToMany('WorkflowSteps', [
			'className' => 'Workflow.WorkflowSteps',
			'joinTable' => 'workflow_statuses_steps',
			'foreignKey' => 'workflow_status_id',
			'targetForeignKey' => 'workflow_step_id',
			'through' => 'Workflow.WorkflowStatusesSteps',
			'dependent' => true
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.WorkflowModels/controls', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);
		// End
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$modelOptions = $this->WorkflowModels->find('list')->toArray();
		$modelOptions = ['-1' => __('All Models')] + $modelOptions;
		$selectedModel = $this->queryString('model', $modelOptions);
		$this->controller->set(compact('modelOptions', 'selectedModel'));
		
		$query->contain($this->_contain);
		if ($selectedModel != -1) {
			$query->where([$this->aliasField('workflow_model_id') => $selectedModel]);
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		// Remove the delete button if the status is not editable
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
		$newOptions['associated'] = ['WorkflowSteps'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function onGetStatusesStepsElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action) {
			case 'view':
				$tableHeaders = [__('Workflow Step Name'), __('Workflow Name')];
				$tableCells = [];
				$workflowStatusId = $this->paramsDecode($this->request->pass[1])['id'];
				$workflowSteps = $this->getWorkflowSteps($workflowStatusId);
				if (!empty($workflowSteps)) {
					$workflowStepOptions = $this->WorkflowSteps
						->find()
						->matching('Workflows')
						->select([
							'name' => $this->WorkflowSteps->aliasField('name'),
							'group' => 'Workflows.name'
						])
						->where([$this->WorkflowSteps->aliasField('id').' IN ' => array_keys($workflowSteps)])
						->toArray();
					foreach ($workflowStepOptions as $step) {
						$rowData = [];
						$rowData[] = $step['name'];
						$rowData[] = $step['group'];
						$tableCells[] = $rowData;
					}
				}
				$attr['tableHeaders'] = $tableHeaders;
				$attr['tableCells'] = $tableCells;
				break;

			case 'edit':
				$tableHeaders = [__('Workflow Step Name'), __('Workflow Name'),''];
				$form = $event->subject()->Form;
				$form->unlockField('WorkflowStatuses.workflow_steps');
				$form->unlockField('WorkflowStatuses.temporary');
				$tableCells = [];
				$arraySteps = [];

				$selectedModel = $entity->workflow_model_id;
				$workflowStepOptions = $this->WorkflowSteps
					->find('list', [
						'groupField' => 'group',
						'keyField' => 'id',
						'valueField' => 'name'
					])
					->matching('Workflows', function($q) use ($selectedModel) {
						return $q->where(['Workflows.workflow_model_id' => $selectedModel]);
					})
					->select([
						'id' => $this->WorkflowSteps->aliasField('id'), 
						'name' => $this->WorkflowSteps->aliasField('name'),
						'group' => 'Workflows.name'
					])
					->toArray();
				
				if ($this->request->is(['get'])) {
					if(isset($this->request->pass[1])){
						$modelId = $this->paramsDecode($this->request->pass[1])['id'];
						$steps = $this->getSteps($modelId);
						foreach($steps as $step) {
							$stepInfo = $step['_matchingData']['WorkflowSteps'];
							$arraySteps[] = [
								'id' => $step['_matchingData']['WorkflowStatusesSteps']['id'],
								'status_id' => $step['id'],
								'step_id' => $stepInfo['id'],
								'name' => $stepInfo['name'],
								'workflow_name' => $step['_matchingData']['Workflows']['name']
							];
						}
					}
				} elseif ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('workflow_steps', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['temporary'] as $key => $obj) {
							if(!empty($obj['temporary']['id'])){
								$arraySteps[] = [
									'name' => $obj['name'],
									'status_id' => $obj['workflow_status_id'],
									'step_id' => $obj['workflow_step_id'],
									'id' => $obj['id'],
									'workflow_name' => $obj['workflow_name']
								];
							}else{	
								$arraySteps[] = [
									'name' => $obj['name'],
									'status_id' => $obj['workflow_status_id'],
									'step_id' => $obj['workflow_step_id'],
									'workflow_name' => $obj['workflow_name']
								];
							}
						}
					}
					if (array_key_exists('step', $requestData[$this->alias()])) {
						$stepId = $requestData[$this->alias()]['step'];
						if($stepId != -1){
							$stepObj = $this->WorkflowSteps
								->find()
								->matching('Workflows')
								->where([$this->WorkflowSteps->aliasField('id') => $stepId])
								->first();
							$arraySteps[] = [
									'name' => $stepObj->name,
									'step_id' => $stepObj->id,
									'status_id' => $entity->id,
									'workflow_name' => $stepObj['_matchingData']['Workflows']['name']
								];
						}
					}
				}
				$cellCount = 0;
				foreach($arraySteps as $obj) {
					$fieldPrefix = $attr['model'] . '.workflow_steps';
					$temporaryPrefix = $attr['model'] . '.temporary.'.$cellCount;
					$statusId = $obj['status_id'];
					$stepId = $obj['step_id'];
					$stepName = $obj['name'];
					$workflowName = $obj['workflow_name'];

					$cellData = "";
					$cellData .= $form->hidden($fieldPrefix."._ids.".$cellCount++, ['value' => $stepId]);
					$cellData .= $form->hidden($temporaryPrefix.".workflow_step_id", ['value' => $stepId]);
					$cellData .= $form->hidden($temporaryPrefix.".name", ['value' => $stepName]);
					$cellData .= $form->hidden($temporaryPrefix.".workflow_status_id", ['value' => $statusId]);
					$cellData .= $form->hidden($temporaryPrefix.".workflow_name", ['value' => $workflowName]);

					if (isset($obj['id'])) {
						$cellData .= $form->hidden($temporaryPrefix.".id", ['value' => $obj['id']]);
					}

					$rowData = [];
					$rowData[] = $stepName.$cellData;
					$rowData[] = $workflowName;
					$rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
					$tableCells[] = $rowData;

					foreach ($workflowStepOptions as $key => $workflowGroup) {
						if(isset($workflowGroup[$stepId])) {
							unset($workflowStepOptions[$key][$stepId]);
						}
					}
					unset($workflowStepOptions[$stepId]);
				}
				// recursive count and substract the first level
				$stepsCount = count($workflowStepOptions, COUNT_RECURSIVE) - count($workflowStepOptions);
				if ($stepsCount == 0) {
					$workflowStepOptions = [
						-1 => $this->Alert->getMessage($this->aliasField('noSteps'))
					];
				} else {
					$workflowStepOptions[-1] = "-- ".__('Add Workflow Step') ." --";
					ksort($workflowStepOptions);
				}
				$attr['options'] = $workflowStepOptions;
				$attr['tableHeaders'] = $tableHeaders;
				$attr['tableCells'] = $tableCells;
				break;
		}
		return $event->subject()->renderElement('Workflow.mappings', ['attr' => $attr]);
	}

	public function getSteps($statusId) {
		return $this
			->find()
			->matching('WorkflowSteps.Workflows')
			->where([$this->aliasField('id') => $statusId])
			->hydrate(false)
			->toArray();
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('is_editable', ['visible' => ['index'=>false, 'view'=>false, 'edit'=>true, 'add' => true], 'type'=>'hidden']);
		$this->ControllerAction->field('is_removable', ['visible' => ['index'=>false, 'view'=>false, 'edit'=>true, 'add' => true], 'type'=>'hidden']);
		$this->ControllerAction->field('statuses_steps', ['type' => 'statuses_steps', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
	}
	
	public function viewAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['workflow_model_id'] = $entity->workflow_model_id;
		$this->request->data[$this->alias()]['code'] = $entity->code;
		$this->request->data[$this->alias()]['name'] = $entity->name;
		$this->request->data[$this->alias()]['is_editable'] = $entity->is_editable;
		$this->ControllerAction->field('workflow_model_id', ['type' => 'readonly', 'value' => $entity->workflow_model_id]);
		$this->ControllerAction->field('code');
		$this->ControllerAction->field('name');
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	// public function addBeforeAction(Event $event) {
	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('workflow_model_id', ['type' => 'select']);
		$this->ControllerAction->field('is_editable', ['value' => 1]);
		$this->ControllerAction->field('is_removable', ['value' => 1]);
		$this->ControllerAction->setFieldOrder([
			'workflow_model_id', 'code', 'name', 'statuses_steps'
		]);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changeModel';
		} else if ($action == 'edit') {
			$workflowModelId = $this->request->data[$this->alias()]['workflow_model_id'];
			$attr['attr']['value'] = $this->WorkflowModels->get($workflowModelId)->name;
		}
		return $attr;
	}

	public function onUpdateFieldCode(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if (!$this->request->data[$this->alias()]['is_editable']) {
				$code = $this->request->data[$this->alias()]['code'];
				$attr['attr']['value'] = $code;
				$attr['value'] = $code;
				$attr['type'] = 'readonly';
				return $attr;
			}
		}
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if (!$this->request->data[$this->alias()]['is_editable']) {
				$name = $this->request->data[$this->alias()]['name'];
				$attr['attr']['value'] = $name;
				$attr['value'] = $name;
				$attr['type'] = 'readonly';
				return $attr;
			}
		}
	}

	public function addEditOnChangeModel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['model']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
					$request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
				}
			}
			// when add clear previously selected steps when change model
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('temporary', $data[$this->alias()])) {
					$data[$this->alias()]['temporary'] = [];
				}
			}
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
			->where([ $this->aliasField('id'). ' IN ' => $workflowStatusId ])
			->select(['id' => 'WorkflowSteps.id', 'name' => 'WorkflowSteps.name'])
			->toArray();
	}
}
