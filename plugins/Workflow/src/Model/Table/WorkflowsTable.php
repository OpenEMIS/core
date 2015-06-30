<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class WorkflowsTable extends AppTable {
	private $_contain = ['FieldOptionValues'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('WorkflowSubmodels', ['className' => 'Workflow.WorkflowSubmodels', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('FieldOptionValues', [
			'className' => 'FieldOptionValues',
			'joinTable' => 'workflow_submodels',
			'foreignKey' => 'workflow_id',
			'targetForeignKey' => 'submodel_reference'
		]);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		//Auto insert default workflow_steps when add
		if ($entity->isNew()) {
			$data = [
				'workflow_steps' => [
					['name' => __('Open'), 'stage' => 0],
					['name' => __('Closed'), 'stage' => 1]
				]
			];

			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (isset($entity->apply_to_all) && $entity->apply_to_all == 1) {
			$where = [
				$this->aliasField('workflow_model_id') => $entity->workflow_model_id,
				$this->aliasField('id') . ' <> ' => $entity->id
			];

			$workflowIds = $this->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where($where)->toArray();
			$this->WorkflowSubmodels->deleteAll([
				$this->WorkflowSubmodels->aliasField('workflow_id IN') => $workflowIds,
				$this->WorkflowSubmodels->aliasField('submodel_reference') => 0
			]);

			$WorkflowSubmodelTable = $this->WorkflowSubmodels;
			$workflowSubmodel = $WorkflowSubmodelTable->newEntity();
			$workflowSubmodel->workflow_id = $entity->id;
			$workflowSubmodel->submodel_reference = 0;
			if ($WorkflowSubmodelTable->save($workflowSubmodel)) {
			} else {
				$this->WorkflowSubmodels->log($workflowSubmodel->errors(), 'debug');
			}
		}
	}

	public function beforeAction(Event $event) {
		//Add new fields
		$this->ControllerAction->addField('apply_to_all', [
			'type' => 'select',
			'order' => 2,
			'visible' => true
		]);
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$selectedModel = $entity->workflow_model_id;
		$submodel = $this->WorkflowModels->findById($selectedModel)->first()->submodel;
		if (is_null($submodel)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;

			$modelAlias = $this->ControllerAction->getModel($submodel)['model'];
			$labelText = Inflector::underscore(Inflector::singularize($modelAlias));

			$submodelTable = TableRegistry::get($submodel);
			$submodelOptions = $submodelTable->getList()->toArray();

			$this->ControllerAction->addField($labelText, [
				'type' => 'chosenSelect',
				'fieldNameKey' => 'field_option_values',
				'fieldName' => $this->alias() . '.field_option_values._ids',
				'placeholder' => __('Select ') . __(Inflector::humanize($labelText)),
				'options' => $submodelOptions,
				'order' => 3,
				'visible' => true,
				'attr' => [
					'onchange' => 'if($(this).val()){$("#workflows-apply-to-all").val(0);};'
				]
			]);

			$order = 2;
			$this->ControllerAction->setFieldOrder('apply_to_all', $order++);
			$this->ControllerAction->setFieldOrder($labelText, $order++);
		}
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($modelOptions, , $applyToAllOptions) = array_values($this->getSelectOptions());

		$this->fields['workflow_model_id']['type'] = 'select';
		$this->fields['workflow_model_id']['options'] = $modelOptions;
		$this->fields['workflow_model_id']['onChangeReload'] = true;

		$this->fields['apply_to_all']['options'] = $applyToAllOptions;
		$this->fields['apply_to_all']['attr'] = [
			'onchange' => 'if(this.value == 1){$("#workflows-field-option-values-ids").val("").trigger("chosen:updated");};'
		];

		$this->setFieldOrder();
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditOnReload(Event $event, Entity $entity, array $data, array $options) {
		$options['associated'] = $this->_contain;
		return compact('entity', 'data', 'options');
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$selectedModel = $entity->workflow_model_id;
		$submodel = $this->WorkflowModels->findById($selectedModel)->first()->submodel;

		if (is_null($submodel)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;

			$modelAlias = $this->ControllerAction->getModel($submodel)['model'];
			$labelText = Inflector::underscore(Inflector::singularize($modelAlias));

			$submodelTable = TableRegistry::get($submodel);
			$submodelOptions = $submodelTable->getList()->toArray();

			$this->ControllerAction->addField($labelText, [
				'type' => 'chosenSelect',
				'fieldNameKey' => 'field_option_values',
				'fieldName' => $this->alias() . '.field_option_values._ids',
				'placeholder' => __('Select ') . __(Inflector::humanize($labelText)),
				'options' => $submodelOptions,
				'order' => 3,
				'visible' => true,
				'attr' => [
					'onchange' => 'if($(this).val()){$("#workflows-apply-to-all").val(0);};'
				]
			]);

			$order = 2;
			$this->ControllerAction->setFieldOrder('apply_to_all', $order++);
			$this->ControllerAction->setFieldOrder($labelText, $order++);
		}

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedModel, ,$selectedApplyToAll) = array_values($this->getSelectOptions());

		$entity->workflow_model_id = $selectedModel;

		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$modelOptions = $this->WorkflowModels->find('list')->toArray();
		$selectedModel = key($modelOptions);

		$applyToAllOptions = [0 => __('No'), 1 => __('Yes')];
		$selectedApplyToAll = key($applyToAllOptions);

		return compact('modelOptions', 'selectedModel', 'applyToAllOptions', 'selectedApplyToAll');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('workflow_model_id', $order++);
		$this->ControllerAction->setFieldOrder('apply_to_all', $order++);
		$this->ControllerAction->setFieldOrder('code', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
	}
}
