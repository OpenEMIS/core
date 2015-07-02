<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class WorkflowsTable extends AppTable {
	private $_contain = ['FieldOptionValues'];
	private $_fieldOrder = ['workflow_model_id'];

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
		$this->ControllerAction->field('apply_to_all', [
			'type' => 'select',
			'visible' => true
		]);
	}

	public function afterAction(Event $event) {
		$this->_fieldOrder[] = 'code';
		$this->_fieldOrder[] = 'name';
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$options['contain'] = array_merge($options['contain'], $this->_contain);
		return $options;
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

			$this->_fieldOrder[] = 'apply_to_all';
			$this->_fieldOrder[] = $labelText;
		}
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($modelOptions, , $applyToAllOptions) = array_values($this->getSelectOptions());

		$this->fields['workflow_model_id']['options'] = $modelOptions;
		$this->fields['workflow_model_id']['onChangeReload'] = true;

		$this->fields['apply_to_all']['options'] = $applyToAllOptions;
		$this->fields['apply_to_all']['attr'] = [
			'onchange' => 'if(this.value == 1){$("#workflows-field-option-values-ids").val("").trigger("chosen:updated");};'
		];
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$options['associated'] = $this->_contain;
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
				'visible' => true,
				'attr' => [
					'onchange' => 'if($(this).val()){$("#workflows-apply-to-all").val(0);};'
				]
			]);

			$this->_fieldOrder[] = 'apply_to_all';
			$this->_fieldOrder[] = $labelText;
		}

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedModel, ,$selectedApplyToAll) = array_values($this->getSelectOptions());

		$entity->workflow_model_id = $selectedModel;
		$entity->apply_to_all = $selectedApplyToAll;

		return $entity;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedApplyToAll) = array_values($this->getSelectOptions());

		$results = $this->WorkflowSubmodels
			->find('all')
			->where([
				$this->WorkflowSubmodels->aliasField('workflow_id') => $entity->id,
				$this->WorkflowSubmodels->aliasField('submodel_reference') => 0		
			]);

		if (!$results->isEmpty()) {
			$selectedApplyToAll = 1;
		}
		$entity->apply_to_all = $selectedApplyToAll;

		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

    public function onGetApplyToAll(Event $event, Entity $entity) {
    	$list = [];
		if (!empty($entity->field_option_values)) {
			foreach ($entity->field_option_values as $obj) {
				$list[] = $obj->name;
			}
		} else {
			$results = $this->WorkflowSubmodels
				->find('all')
				->where([
					$this->WorkflowSubmodels->aliasField('workflow_id') => $entity->id,
					$this->WorkflowSubmodels->aliasField('submodel_reference') => 0
				]);

			if (!$results->isEmpty()) {
				$list[] = __('Apply To All');
			}
		}

        return implode(', ', $list);
    }

	public function getSelectOptions() {
		//Return all required options and their key
		$modelOptions = $this->WorkflowModels->find('list')->toArray();
		$selectedModel = key($modelOptions);

		$applyToAllOptions = [0 => __('No'), 1 => __('Yes')];
		$selectedApplyToAll = key($applyToAllOptions);

		return compact('modelOptions', 'selectedModel', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
