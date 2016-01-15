<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class ConfigItemsTable extends AppTable {
	use OptionsTrait;

	private $configurations = [];

	public function initialize(array $config) {
		parent::initialize($config);

		// $this->belongsTo('ConfigItemOptions', ['foreignKey'=>'value']);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($this->action == 'view') {
			$key = isset($this->request->pass[0]) ? $this->request->pass[0] : null;
			if (!empty($key)) {
				$configItem = $this->get($key);
				if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
					if (isset($toolbarButtons['back'])) {
						unset($toolbarButtons['back']);
					}
				}
			}
		}
	}

	public function beforeAction(Event $event) {
		if ($this->action == 'view' || $this->action == 'edit') {
			$key = isset($this->request->pass[0]) ? $this->request->pass[0] : null;
			if (!empty($key)) {
				$configItem = $this->get($key);
				if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
					if (isset($this->request->data[$this->alias()]['value']) && !empty($this->request->data[$this->alias()]['value'])) {
						$value = $this->request->data[$this->alias()]['value'];
					} else {
						$value = $configItem->value;
						$this->request->data[$this->alias()]['value'] = $value;
					}

					if ($value != 'Local') {
						$this->ControllerAction->field('custom_authentication', ['type' => 'authentication_type', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
					}
				}
			}
		}

		$this->ControllerAction->field('visible', ['visible' => false]);
		$this->ControllerAction->field('editable', ['visible' => false]);
		$this->ControllerAction->field('field_type', ['visible' => false]);
		$this->ControllerAction->field('option_type', ['visible' => false]);
		$this->ControllerAction->field('code', ['visible' => false]);

		$this->ControllerAction->field('name', ['visible' => ['index'=>true]]);
		$this->ControllerAction->field('default_value', ['visible' => ['view'=>true]]);
		
		$this->ControllerAction->field('type', ['visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('label', ['visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('value', ['visible' => true]);

		$this->ControllerAction->field('form_notes', ['type' => 'form_notes', 'visible' => false]);

		$this->addBehavior('FormNotes');
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->buildSystemConfigFilters();
	}

	public function viewBeforeAction(Event $event) {
		$this->buildSystemConfigFilters();
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$key = isset($this->request->pass[0]) ? $this->request->pass[0] : null;
		if (!empty($key)) {
			$configItem = $this->get($key);
			if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
				$this->ControllerAction->field('default_value', ['visible' => false]);
				$value = $this->request->data[$this->alias()]['value'];
				if ($value != 'Local') {
					$this->ControllerAction->setFieldOrder(['type', 'label', 'value', 'custom_authentication']);
				}
			}
		}
	}

	public function onGetAuthenticationTypeElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "index":
				// No implementation
				break;

			case "view":
				$tableHeaders = [__('Attribute Name'), __('Value')];
				$tableCells = [];

				$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
				$authenticationType = $this->request->data[$this->alias()]['value'];
				$attribute = [];

				switch ($authenticationType) {
					case 'Google':
						$attribute['client_id'] = ['name' => 'Client ID'];
						$attribute['client_secret'] = ['name' => 'Client Secret'];
						$attribute['redirect_uri'] = ['name' => 'Redirect URI'];
						$attribute['hd'] = ['name' => 'Hosted Domain'];
						break;
				}
				$attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
				$attributeFieldsArray = $this->Navigation->array_column($attributesArray, 'attribute_field');
				foreach ($attribute as $key => $values) {
					$attributeValue = '';
					if (array_search($key, $attributeFieldsArray) !== false) {
						$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
					}
					$attribute[$key]['value'] = $attributeValue;
				}

				foreach ($attribute as $key => $value) {
					$row = [];
					$row[] = $value['name'];
					$row[] = $value['value'];
					$tableCells[] = $row;
				}
				
				$attr['tableHeaders'] = $tableHeaders;
		    	$attr['tableCells'] = $tableCells;
				break;

			case "edit":
				$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
				$authenticationType = $this->request->data[$this->alias()]['value'];
				$attribute = [];

				switch ($authenticationType) {
					case 'Google':
						$attribute['client_id'] = ['name' => 'Client ID'];
						$attribute['client_secret'] = ['name' => 'Client Secret'];
						$attribute['redirect_uri'] = ['name' => 'Redirect URI'];
						$attribute['hd'] = ['name' => 'Hosted Domain'];
						break;
				}
				$attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
				$attributeFieldsArray = $this->Navigation->array_column($attributesArray, 'attribute_field');
				foreach ($attribute as $key => $values) {
					$attributeValue = '';
					if (array_search($key, $attributeFieldsArray) !== false) {
						$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
					}
					$attribute[$key]['value'] = $attributeValue;
				}
				$attr = $attribute;
				break;

		}
		return $event->subject()->renderElement('Configurations/authentication', ['attr' => $attr]);
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
		if ($data[$this->alias()]['value'] != 'Local') {
			$authenticationType = $data[$this->alias()]['value'];
			$AuthenticationTypeAttributesTable->deleteAll(
				['authentication_type' => $authenticationType]
			);

			foreach ($data['AuthenticationTypeAttributes'] as $key => $value) {
				$entityData = [
					'authentication_type' => $authenticationType,
					'attribute_field' => $key,
					'attribute_name' => $value['name'],
					'value' => $value['value']
				];
				$entity = $AuthenticationTypeAttributesTable->newEntity($entityData);
				$AuthenticationTypeAttributesTable->save($entity);
			}
		}
	}

	private function buildSystemConfigFilters() {
		$toolbarElements = [
			['name' => 'Configurations/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$typeOptions = array_keys($this->find('list', ['keyField' => 'type', 'valueField' => 'type'])->order('type')->toArray());

		$selectedType = $this->queryString('type', $typeOptions);
		$this->advancedSelectOptions($typeOptions, $selectedType);
		$buffer = $typeOptions;

		foreach ($buffer as $key => $value) {
			$result = $this->find()->where([$this->aliasField('type') => $value['text'], $this->aliasField('visible') => 1])->count();
			if (!$result) {
				unset($typeOptions[$key]);
			}
		}
		$this->request->query['type_value'] = $typeOptions[$selectedType]['text'];
		
		if ($this->request->query['type_value'] == 'Authentication') {
			$urlParams = $this->ControllerAction->url('view');
			$authenticationTypeId = $this->find()->where([$this->aliasField('type') => 'Authentication', $this->aliasField('code') => 'authentication_type'])->first()->id;
			$urlParams[0] = $authenticationTypeId;
			if (isset($this->request->pass[0]) && $this->request->pass[0] == $authenticationTypeId) {
			} else {
				$this->controller->redirect($urlParams);
			}
		}
		
		$this->controller->set('typeOptions', $typeOptions);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$type = $request->query['type_value'];
		$query
			->find('visible')
			->where([$this->aliasField('type') => $type]);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		$this->fields['type']['type'] = 'readonly';
		$this->fields['label']['type'] = 'readonly';

		$pass = $this->request->param('pass');
		if (is_array($pass) && !empty($pass)) {
			$id = $pass[0];
			$entity = $this->get($id);
		}
		if (isset($entity)) {
			/**
			 * grab validation rules by either record code or record type
			 */
			$validationRules = 'validate' . Inflector::camelize($entity->code);
			if (isset($this->$validationRules)) {
				$this->validator()->add('value', $this->$validationRules);
			} else {
				$validationRules = 'validate' . Inflector::camelize($entity->type);
				if (isset($this->$validationRules)) {
					$this->validator()->add('value', $this->$validationRules);
				}
			}
			if ($entity->type == 'Custom Validation') {
				$this->fields['form_notes']['visible'] = true;
				$this->fields['form_notes']['value'] = '<ul><li>9 (Numbers)</li><li>a (Letter)</li><li>w (Alphanumeric)</li><li>* (Any Character)</li><li>? (Optional - any characters following will become optional)</li></ul>';
		
				$this->fields['value']['attr']['onkeypress'] = 'return Config.inputMaskCheck(event)';
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (is_array($data[$this->alias()]['value'])) {
			if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
				$value = $data[$this->alias()]['value']['prefix'];
				if (isset($data[$this->alias()]['value']['enable'])) {
					$value .= ',1';
				} else {
					$value .= ',0';
				}
				$data[$this->alias()]['value'] = $value;
			}
		}
	}


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/
	public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request) {
		if (in_array($action, ['edit', 'add'])) {
			$pass = $request->param('pass');
			if (!empty($pass)) {
				$id = $pass[0];
				$entity = $this->get($id);


				if ($entity->field_type == 'Dropdown') {

					$exp = explode(':', $entity->option_type);
					/**
					 * if options list is from a specific table
					 */
					if (count($exp)>0 && $exp[0]=='database') {
						$model = Inflector::pluralize($exp[1]);
						$model = $this->getActualModeLocation($model);
						$optionTable = TableRegistry::get($model);
						$attr['options'] = $optionTable->getList();
						
					/**
					 * if options list is from ConfigItemOptions table
					 */
					} else {
						$optionTable = TableRegistry::get('ConfigItemOptions');
						$options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
							->where([
								'ConfigItemOptions.option_type' => $entity->option_type
							])
							->toArray();
						$attr['options'] = $options;
					}

					if (isset($this->request->data[$this->alias()]['value'])) {
						$attr['onChangeReload'] = true;	
					}

				} else {
					if ($entity->code == 'start_time') {
						$attr['type'] = 'time';
					} else if ($entity->code == 'hours_per_day' || $entity->code == 'days_per_week') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1];
					} else if ($entity->type == 'Data Discrepancy') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 0, 'max' => 100];
					} else if ($entity->type == 'Data Outliers') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 100];
					} else if ($entity->type == 'Student Admission Age') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 100];
					} else if ($entity->code == 'no_of_shifts') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 10];
					} else if ($entity->code == 'training_credit_hour') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 0];
					} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
						$attr['type'] = 'element';
						$attr['element'] = 'Configurations/with_prefix';
						$attr['data'] = [];
					}
				}
			}
		}
		return $attr;
	}

	public function onGetValue(Event $event, Entity $entity) {
		if ($entity->type == 'Custom Validation') {
			$attr['type'] = 'string';
			$event->subject()->HtmlField->includes['configItems'] = [
				'include' => true,
				'js' => [
					'config'
				]
			];
		}
		$value = $this->recordValueForView('value', $entity);
		if (empty($value)) {
			$value = $this->recordValueForView('default_value', $entity);
		}
		return $value;
	}

	public function onGetDefaultValue(Event $event, Entity $entity) {
		return $this->recordValueForView('default_value', $entity);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		$includes['configItems'] = ['include' => true, 'js' => ['config']];
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function recordValueForView($valueField, $entity) {
		if ($entity->field_type == 'Dropdown') {

			$exp = explode(':', $entity->option_type);
			/**
			 * if options list is from a specific table
			 */
			if (count($exp)>0 && $exp[0]=='database') {
				$model = Inflector::pluralize($exp[1]);
				$model = $this->getActualModeLocation($model);
				$optionsModel = TableRegistry::get($model);
				$value = $optionsModel->get($entity->$valueField);
				if (is_object($value)) {
					return $value->name;
				} else {
					return $entity->$valueField;
				}

			/**
			 * options list is from ConfigItemOptions table
			 */
			} else {
				$optionsModel = TableRegistry::get('ConfigItemOptions');
				$value = $optionsModel->find()
					->where([
						'ConfigItemOptions.option_type' => $entity->option_type,
						'ConfigItemOptions.value' => $entity->$valueField,
					])
					->first();
				if (is_object($value)) {
					if ($entity->code == 'time_format' || $entity->code == 'date_format') {
						return date($value->$valueField);
					} else {
						return $value->option;
					}
				} else {
					return $entity->$valueField;
				}
			}

		} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
			$exp = explode(',', $entity->$valueField);
			if (!$exp[1]) {
				return __('Disabled');
			} else {
				return __('Enabled') . ' ('.$exp[0].')';
			}
		} else {
			if ($entity->code == 'time_format' || $entity->code == 'date_format') {
				return date($entity->$valueField);
			} else {
				return $entity->$valueField;
			}
		}
	}

	public function value($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = strlen($entity->value) ? $entity->value : $entity->default_value;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	public function defaultValue($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = $entity->default;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	private function getActualModeLocation($model) {
		$dir = dirname(__FILE__);
		if (!file_exists($dir . '/' . $model . 'Table.php')) {
			$dir = dirname(dirname(dirname(dirname(__FILE__)))).'/plugins';
			$folders = scandir($dir);
			foreach ($folders as $folder) {
				if (!in_array($folder, ['.', '..', '.DS_Store'])) {
					if (file_exists($dir . '/' . $folder . '/src/Model/Table/' . $model . 'Table.php')) {
						$model = $folder .'.'. $model;
						break;
					}
				}
			}
		}
		return $model;
	}


/******************************************************************************************************************
**
** value field validation rules based on specific codes
** refer to editBeforeAction() on how these validation rules are loaded dynamically
**
******************************************************************************************************************/
	   
	private $validateSupportEmail = [
		'email' => [
			'rule'	=> ['email'],
		]
	];
	   
	private $validateWhereIsMySchoolStartLong = [
		'checkLongitude' => [
			'rule'	=> ['checkLongitude'],
			'provider' => 'table',
		]
	];
	   
	private $validateWhereIsMySchoolStartLat = [
		'checkLatitude' => [
			'rule'	=> ['checkLatitude'],
			'provider' => 'table',
		]
	];
	   
	private $validateWhereIsMySchoolStartRange = [
		'num' => [
			'rule'  => ['numeric'],
		],
	];
	   
	private $validateSmsProviderUrl = [
		'url' => [
			'rule'	=> ['url', true],
			'message' => 'Please provide a valid URL with http:// or https://',
		]
	];
	   
	private $validateWhereIsMySchoolUrl = [
		'url' => [
			'rule'	=> ['url', true],
			'message' => 'Please provide a valid URL with http:// or https://',
		]
	];
	   
	private $validateStartTime = [
		'dateInput' => [
			'rule'	=> ['checkDateInput'],
			'provider' => 'table',
			'last' => true
		],
		'aPValue' => [
			'rule'	=> ['amPmValue'],
			'provider' => 'table',
			'last' => true
		]
	];
	   
	private $validateLowestYear = [
			'num' => [
				'rule'  => ['numeric'],
				'message' => 'Please provide a valid year',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1900, 9999],
				'message' => 'Please provide a valid year',
				'last' => true
			]
	];
	   
	private $validateHoursPerDay = [
		'num' => [
			'rule'  => ['numeric'],
			'message' => 'Numeric Value should be between 0 to 25',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 24],
			'message' => 'Numeric Value should be between 0 to 25',
			'last' => true
		]
	];
	   
	private $validateDaysPerWeek = [
		'num' => [
			'rule'  => ['numeric'],
			'message' => 'Numeric Value should be between 0 to 8',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 7],
			'message' => 'Numeric Value should be between 0 to 8',
			'last' => true
		]
	];
	   
	private $validateReportDiscrepancyVariationpercent = [
		'num' => [
			'rule'  => 'numeric',
			'message' => 'Numeric Value should be between -1 to 101',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 0, 100],
			'message' => 'Numeric Value should be between -1 to 101',
			'last' => true
		]
	];

  	private $validateDataOutliers = [
		'num' => [
			'rule'  => 'numeric',
			'message' => 'Numeric Value should be between 0 to 101',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 100],
			'message' => 'Numeric Value should be between 0 to 101',
			'last' => true
		]
  	];

  	private $validateStudentAdmissionAge = [
		'num' => [
			'rule'  => 'numeric',
			'message' => 'Numeric Value should be between -1 to 101',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 0, 100],
			'message' => 'Numeric Value should be between -1 to 101',
			'last' => true
		]
  	];

	private $validateNoOfShifts = [
		'num' => [
			'rule'  => 'numeric',
			'message' => 'Numeric Value should be between 0 to 11',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 10],
			'message' => 'Numeric Value should be between 0 to 11',
			'last' => true
		]
	];

  	private $validateTrainingCreditHour = [
		'num' => [
			'rule'  => 'numeric',
			'message' => 'Value should be numeric',
		]
	];
  
	private $validateSmsRetryTime = [
		'num' => [
			'rule'  => 'numeric',
			'message' =>  'Numeric Value should be between 0 to 11',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 10],
			'message' => 'Numeric Value should be between 0 to 11',
			'last' => true
		]
	];

  	private $validateSmsRetryWait = [
		'num' => [
			'rule'  => 'numeric',
			'message' =>  'Numeric Value should be between 0 to 61',
			'last' => true
		],
		'bet' => [
			'rule'	=> ['range', 1, 60],
			'message' => 'Numeric Value should be between 0 to 61',
			'last' => true
		]
	];
}
