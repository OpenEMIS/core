<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Utility\Inflector;

class FieldOptionValuesTable extends AppTable {
	use OptionsTrait;
	private $fieldOption = null;
	private $parentFieldOptionList = ['FieldOption.BankBranches' => ['parentModel' => 'FieldOption.Banks', 'foreignKey' => 'bank_id'], 
									  'User.ContactTypes' => ['parentModel' => 'User.ContactOptions', 'foreignKey' => 'contact_option_id'], 
									  'FieldOption.Banks' => [],
									  'FieldOption.Countries' => []
									  ];

	private $defaultFieldOrder = ['field_option_id', 'parent_field_option_id', 'name', 'national_code', 'international_code', 'visible', 'default', 'editable', 'foreignKey'];
	private $excludeFieldList = ['modified_user_id', 'modified', 'created_user_id', 'created'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('FieldOptions', ['className' => 'FieldOption.FieldOptions']);

		$this->addBehavior('Reorder', ['filter' => 'field_option_id']);
		$this->addBehavior('ParamModel');
	}

	public function onGetFieldOptionId(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('field_option')) {
			$value = $entity->field_option->parent . ' - ' . $entity->field_option->name;
		} else {
			$selectedOption = $this->request->query('field_option_id');
			$fieldOption = $this->FieldOptions->get($selectedOption);
			$value = $fieldOption->parent . ' - ' . $fieldOption->name;
		}
		return $value;
	}

	public function onGetEditable(Event $event, Entity $entity) {
		return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetDefault(Event $event, Entity $entity) {
		return $entity->default == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $data) {
		pr($options);
		pr($entity); die;

		parent::beforeSave($event, $entity, $options);

		if ($entity->default == 1) {
			$this->updateAll(['default' => 0], ['field_option_id' => $entity->field_option_id]);
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('order', ['type' => 'hidden']);
		$this->ControllerAction->field('default', ['options' => $this->getSelectOptions('general.yesno')]);
		$this->ControllerAction->field('editable', ['options' => $this->getSelectOptions('general.yesno'), 'visible' => ['index' => true]]);
		
		$fieldOptions = [];
		$data = $this->FieldOptions
			->find()
			->find('visible')
			->find('order')
			->all();

		foreach ($data as $obj) {
			$key = $obj->id;
			
			$parent = __($obj->parent);
			if (!array_key_exists($parent, $fieldOptions)) {
				$fieldOptions[$parent] = array();
			}
			$fieldOptions[$parent][$key] = __($obj->name);
		}
		$this->controller->set('fieldOptions', $fieldOptions);

		$selectedOption = $this->queryString('field_option_id', $fieldOptions);
		$this->controller->set('selectedOption', $selectedOption);
		$this->fieldOption = $this->FieldOptions->get($selectedOption);
		$this->fieldOption->name = $this->fieldOption->parent . ' - ' . $this->fieldOption->name;

		$defaultFieldOrder = $this->defaultFieldOrder;

		//try to get the list of values from selected options
		$currentfieldOption = $this->FieldOptions->get($selectedOption);
		if(array_key_exists($currentfieldOption->plugin.'.'.$currentfieldOption->code, $this->parentFieldOptionList)) {
			$parentFieldOptionInfo = $this->parentFieldOptionList[$currentfieldOption->plugin.'.'.$currentfieldOption->code];	
			$parentFieldOptionTable = TableRegistry::get($parentFieldOptionInfo['parentModel']);

			if(!empty($parentFieldOptionInfo['foreignKey'])) {
				$parentFieldOptions = $parentFieldOptionTable->find('list')->where([$parentFieldOptionTable->aliasField('visible') => 1])->toArray();	

				$this->controller->set('parentFieldOptions', $parentFieldOptions);
				$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);
				$this->controller->set('selectedParentFieldOption', $selectedParentFieldOption);
				$this->controller->set('foreignKey', $parentFieldOptionInfo['foreignKey']);

				$foreignKey =  $parentFieldOptionInfo['foreignKey'];
				$parentFieldOptionValue = $parentFieldOptions[$selectedParentFieldOption];

				// $foreignKeyLabel = Inflector::humanize($foreignKey);
				// if($this->endsWith($foreignKey, '_id')){
				// 	$foreignKeyLabel = str_replace(' Id', '', $foreignKeyLabel);
				// }

				$this->ControllerAction->field($foreignKey, ['type' => 'readonly', 
																  'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => true], 
																  'model' => $parentFieldOptionTable->alias(), 
																  'value' => $parentFieldOptionValue,
																  'className' => $parentFieldOptionInfo['parentModel'], 
																  'attr' => ['value' => $parentFieldOptionValue]]);

				// $this->ControllerAction->field($foreignKey, ['type' => 'hidden', 
				// 											 'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => false], 
				// 											 'model' => $parentFieldOptionTable->alias(), 
				// 											 'className' => $parentFieldOptionInfo['parentModel'], 
				// 											 'attr' => ['value' => $selectedParentFieldOption]]);
			}

			//activate behaviour on the fly
			if(!empty($parentFieldOptionInfo['behavior'])){
				// pr($parentFieldOptionInfo['behavior']); //die;
				// $this->addBehavior($parentFieldOptionInfo['behavior']);
			}
			
		}

		if ($this->action == 'index') {
			$toolbarElements = [
				['name' => 'FieldOption.controls', 'data' => [], 'options' => []],
			];
			$this->controller->set('toolbarElements', $toolbarElements);
		}

		$fieldOptionList = $this->FieldOptions->getList()->toArray();
		$this->ControllerAction->field('field_option_id', [
			'type' => 'readonly', 
			'options' => $fieldOptionList,
			'attr' => ['value' => $this->fieldOption->name],
			'visible' => ['index' => false, 'view' => true, 'edit' => true]
		]);

		$this->ControllerAction->field('parent_field_option_id', [
			'type' => 'readonly', 
			'options' => $parentFieldOptions,
			'attr' => ['value' => $this->fieldOption->name],
			'visible' => ['index' => false, 'view' => false, 'edit' => false]
		]);
		
		$this->ControllerAction->setFieldOrder($defaultFieldOrder); //die;
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {		
		$settings['pagination'] = false;

		$selectedOption = $this->ControllerAction->getVar('selectedOption');

		$fieldOption = $this->FieldOptions->get($selectedOption);
		if (!empty($fieldOption->params)) {
			$params = json_decode($fieldOption->params);
			$table = TableRegistry::get($params->model);

			$query = $table->find();
		} else {
			$query->where([$this->aliasField('field_option_id') => $selectedOption]);
			$this->ControllerAction->setFieldOrder([
				'visible', 'default', 'editable', 'name', 'national_code'
			]);
		}

		$selectedParentFieldOption = $this->ControllerAction->getVar('selectedParentFieldOption');
		$foreignKey = $this->ControllerAction->getVar('foreignKey');
		if (!empty($selectedParentFieldOption) && !empty($foreignKey)) {	
			$query->where([$foreignKey => $selectedParentFieldOption]);
		}	

		return $query->find('order');
	}

	public function getFieldOption() {
		return $this->fieldOption;
	}

	public function viewBeforeAction(Event $event) {
		if (!empty($this->fieldOption->params)) {
			$params = json_decode($this->fieldOption->params);
			$table = TableRegistry::get($params->model);
			return $table;
		}
	}

	public function addEditBeforeAction(Event $event) {
		if (!empty($this->fieldOption->params)) {
			$params = json_decode($this->fieldOption->params);
			$table = TableRegistry::get($params->model);
			return $table;
		}
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$fieldOption = $this->fieldOption;
		if (!empty($fieldOption->params)) {
			$process = function($model, $id, $options) use ($fieldOption) {
				$params = json_decode($this->fieldOption->params);
				$table = TableRegistry::get($params->model);
				$entity = $table->get($id);
				return $table->delete($entity);
			};
			return $process;
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		// set field option value for add page
		$selectedOption = $this->ControllerAction->getVar('selectedOption');
		$entity->field_option_id = $selectedOption;
	}

	public function deleteBeforeAction(Event $event, ArrayObject $settings) {
		$fieldOption = $this->fieldOption;
		$codes[] = $fieldOption->code;
		if (in_array($fieldOption->code, $codes)) {
			$settings['deleteStrategy'] = 'transfer';
			if (empty($fieldOption->params)) {
				$model = $fieldOption->code;
				if (!is_null($fieldOption->plugin)) {
					$model = $fieldOption->plugin . '.' . $model;
				}
				$settings['model'] = $model;
			} else {
				//get model through params
				if (is_object(json_decode($fieldOption->params))) { 
			        $decoded = json_decode($fieldOption->params);
			        $settings['model'] = (!empty($decoded->model)) ? $decoded->model : '';
			    }
			}
		}

	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$fieldOption = $this->fieldOption;
		$codes[] = $fieldOption->code;
		if (in_array($fieldOption->code, $codes)) {
			if (empty($fieldOption->params)) {
				$query->where([$query->repository()->aliasField('field_option_id') => $fieldOption->id]);
				$availFieldOptions = $this->findById($fieldOption->id)->count();
				
			} else{
				if (is_object(json_decode($fieldOption->params))) { 
					$decoded = json_decode($fieldOption->params);
					if(array_key_exists($decoded->model, $this->parentFieldOptionList)){
						$fieldOptionTable = TableRegistry::get($decoded->model);
						$availFieldOptions = $fieldOptionTable->find()->count();
					}
				}
			}
			if($availFieldOptions == 1) {
				$this->Alert->warning('general.notTransferrable');
				$event->stopPropagation();
				return $this->controller->redirect($this->ControllerAction->url('index'));
			}
		}
	}

	public function getList($customOptions=[]) {
		// pr($this);
		$alias = $this->table();
		$options = [
			'recursive' => -1,
			'joins' => [
				[
					'table' => 'field_options',
					'alias' => 'FieldOption',
					'conditions' => [
						'FieldOption.id = ' . $this->alias() . '.field_option_id',
						"FieldOption.code = '" . $this->alias() . "'"
					]
				]
			],
			'order' => [$this->alias().'.order']
		];


		$options['conditions'] = [];

		if (array_key_exists('visibleOnly', $customOptions)) {
			$options['conditions'][$alias.'.visible >'] = 0;
		}

		if (array_key_exists('conditions', $customOptions)) {
			$options['conditions'] = array_merge($options['conditions'], $customOptions['conditions']);
		}
		
		if (array_key_exists('value', $customOptions)) {
			$selected = $customOptions['value'];
		} else {
			$selected = false;
		}

		$query = $this->find('all')
				->join($options['joins'])
				->order($options['order'])
				->where($options['conditions']);

		$result = array();
		if (array_key_exists('listOnly', $customOptions) && $customOptions['listOnly']) {
			foreach ($query as $key => $value) {
				$name = __($value->name);
				$result[$value->id] = $name;
			}
		} else {
			foreach ($query as $key => $value) {
				array_push($result, 
					array(
						'id' => $value->id,
						'text' => __($value->name), 
						'national_code' => $value->national_code, 
						'value' => $value->id,
						'obsolete' => ($value->visible!='0') ? false : true,
						'selected' => ($selected && $selected==$value->id) ? true : ((!$selected && $value->default!='0') ? true : false)
					)
				);
			}
			if (array_key_exists('value', $customOptions)) {
				$value = $customOptions['value'];

				if (is_array($value)) {
					foreach ($result as $okey => $ovalue) {
					if ($ovalue['obsolete'] == '1' && !in_array($ovalue['value'], $value)) {
						unset($result[$okey]);
					}
				}
				} else {
					foreach ($result as $okey => $ovalue) {
						if ($ovalue['obsolete'] == '1' && $ovalue['value']!=$value) {
							unset($result[$okey]);
						}
					}	
				}
			}
		}
		return $result;
	}

}
