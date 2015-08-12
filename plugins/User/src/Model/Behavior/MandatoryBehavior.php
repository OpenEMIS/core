<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\Utility\Inflector;

class MandatoryBehavior extends Behavior {
	protected $_userRole;
	protected $_info;
	protected $_roleFields;

	public function initialize(array $config) {
		$this->_userRole = (array_key_exists('userRole', $config))? $config['userRole']: null;
		$this->_roleFields = (array_key_exists('roleFields', $config))? $config['roleFields']: [];
		if (is_null($this->_userRole)) die('userRole must be set in mandatory behavior');

		$ConfigItems = TableRegistry::get('ConfigItems');

		$this->_info = [];
		foreach ($this->_roleFields as $key => $value) {
			$currModelName = $this->_userRole.$value;
			$this->_info[$value] = $this->getOptionValue($currModelName);
		}

		$this->_table->hasMany('Identities', 				['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->_table->hasMany('Nationalities', 			['className' => 'User.Nationalities', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->_table->hasMany('SpecialNeeds', 				['className' => 'User.SpecialNeeds', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->_table->hasMany('Contacts', 					['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		// pr($this->_info);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.add.onInitialize' => 'addOnInitialize',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.add.onChangeNationality' => 'addOnChangeNationality',
			'ControllerAction.Model.onUpdateFieldContactType' => 'onUpdateFieldContactType',
			'ControllerAction.Model.onUpdateFieldContactValue' => 'onUpdateFieldContactValue',
			'ControllerAction.Model.onUpdateFieldNationality' => 'onUpdateFieldNationality',
			'ControllerAction.Model.onUpdateFieldIdentityType' => 'onUpdateFieldIdentityType',
			'ControllerAction.Model.onUpdateFieldIdentityNumber' => 'onUpdateFieldIdentityNumber',
			'ControllerAction.Model.onUpdateFieldSpecialNeed' => 'onUpdateFieldSpecialNeed',
			'ControllerAction.Model.onUpdateFieldSpecialNeedComment' => 'onUpdateFieldSpecialNeedComment'
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function getOptionValue($name) {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$data = $ConfigItems
			->find()
			->where([$ConfigItems->aliasField('code') => $name])
			->first()
		;

		$optionType = $data->option_type;
		$value = $data->value;

		$ConfigItemOptions = TableRegistry::get('ConfigItemOptions');
		$result = $ConfigItemOptions
			->find()
			->where([$ConfigItemOptions->aliasField('option_type') => $optionType, $ConfigItemOptions->aliasField('value') => $value])
			->first();
		return $result->option;
	}

	public function addOnInitialize(Event $event, Entity $entity) { 
		$Countries = TableRegistry::get('FieldOption.Countries');
		$defaultCountry = $Countries->getDefaultEntity();
		
		$this->fields['nationality']['default'] = $defaultCountry->id;

		$defaultIdentityType = $defaultCountry->identity_type_id;
		if (is_null($defaultIdentityType)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentityType = $IdentityTypes->getDefaultValue();
		}
		$this->fields['identity_type']['default'] = $defaultIdentityType;

		return $entity;
	}

	public function addBeforeAction(Event $event) {
		// mandatory associated fields

		$i = 30;
		if (array_key_exists('Contacts', $this->_info) && $this->_info['Contacts'] != 'Excluded') {
			$this->_table->ControllerAction->field('contact_type', ['order' => $i++]);
			$this->_table->ControllerAction->field('contact_value', ['order' => $i++]);
		}

		if (array_key_exists('Nationalities', $this->_info) && $this->_info['Nationalities'] != 'Excluded') {
			$this->_table->ControllerAction->field('nationality', ['order' => $i++]);
		}

		if (array_key_exists('Identities', $this->_info) && $this->_info['Identities'] != 'Excluded') {
			$this->_table->ControllerAction->field('identity_type', ['order' => $i++]);
			$this->_table->ControllerAction->field('identity_number', ['order' => $i++]);
		}

		if (array_key_exists('SpecialNeeds', $this->_info) && $this->_info['SpecialNeeds'] != 'Excluded') {
			$this->_table->ControllerAction->field('special_need', ['order' => $i++]);
			$this->_table->ControllerAction->field('special_need_comment', ['order' => $i++]);
		}

		// need to set the handling for non-mandatory require = false here
		foreach ($this->_info as $key => $value) {
			if ($value == 'Non-Mandatory') {
				// need to set the relevant non-mandatory fields and set it to required = false to remove *
				$singularAndLowerKey = strtolower(Inflector::singularize(Inflector::tableize($key)));
				foreach ($event->subject()->model->fields as $fkey => $fvalue) {
					if (strpos($fkey, $singularAndLowerKey)!==false) {
						$event->subject()->model->fields[$fkey]['attr']['required'] = false;
					}
				}
			}
		}

	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];

		$newOptions['associated'] = ['Identities', 'Nationalities', 'SpecialNeeds', 'Contacts'];

		foreach ($this->_info as $key => $value) {
			// default validation is 'Mandatory'
			if ($value == 'Non-Mandatory') {
				$newOptions['associated'][$key] = ['validate' => 'NonMandatory'];
				// also need to remove the data if the field is empty
				$tableName = Inflector::tableize($key);

				if (array_key_exists($tableName, $data[$this->_table->alias()])) {
					if (array_key_exists(0, $data[$this->_table->alias()][$tableName])) {
						// going to check all fields.. if something is empty(form fill incomplete).. the data will not be removed and not saved
						$incompleteField = false;
						foreach ($data[$this->_table->alias()][$tableName][0] as $ckey => $check) {
							if (empty($check)) {
								$incompleteField = true;
							}
						}
						if ($incompleteField) {
							unset($data[$this->_table->alias()][$tableName]);
						}
					}
				}
			} else {
				if ($value != 'Excluded') {
					$newOptions['associated'][] = $key;
				}
			}
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addOnChangeNationality(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$Countries = TableRegistry::get('FieldOption.Countries');
		$countryId = $data[$this->_table->alias()]['nationalities'][0]['country_id'];
		$country = $Countries->findById($countryId)->first();
		$defaultIdentityType = $country->identity_type_id;
		if (is_null($defaultIdentityType)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentityType = $IdentityTypes->getDefaultValue();
		}
		
		$this->_table->fields['nationality']['default'] = $data[$this->_table->alias()]['nationalities'][0]['country_id'];

		// overriding the  previous input to put in default identities
		$this->_table->fields['identity_type']['default'] = $defaultIdentityType;
		$data[$this->_table->alias()]['identities'][0]['identity_type_id'] = $defaultIdentityType;

		$options['associated'] = [
			'InstitutionSiteStudents' => ['validate' => false],
			'InstitutionSiteStaff' => ['validate' => false],
			'Identities' => ['validate' => false],
			'Nationalities' => ['validate' => false],
			'SpecialNeeds' => ['validate' => false],
			'Contacts' => ['validate' => false]
		];
	}

	public function onUpdateFieldContactType(Event $event, array $attr, $action, $request) {
		if (!empty($this->_info)) {
			if (array_key_exists('Contacts', $this->_info)) {
				if ($this->_info['Contacts'] == 'Non-Mandatory') {
					$attr['empty'] = 'Select';
				}
			}
		}

		$contactOptions = TableRegistry::get('User.ContactTypes')
			->find('list', ['keyField' => 'id', 'valueField' => 'full_contact_type_name'])
			->find('withContactOptions')
			->toArray();

		$attr['type'] = 'select';
		$attr['fieldName'] = $this->_table->alias().'.contacts.0.contact_type_id';
		$attr['options'] = $contactOptions;
		
		return $attr;
	}

	public function onUpdateFieldContactValue(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'string';
		$attr['fieldName'] = $this->_table->alias().'.contacts.0.value';

		return $attr;
	}

	public function onUpdateFieldNationality(Event $event, array $attr, $action, $request) {
		if (!empty($this->_info)) {
			if (array_key_exists('Nationalities', $this->_info)) {
				if ($this->_info['Nationalities'] == 'Non-Mandatory') {
					$attr['empty'] = 'Select';
				}
			}
		}

		$Countries = TableRegistry::get('FieldOption.Countries');
		$nationalityOptions = $Countries->getList()->toArray();

		$attr['type'] = 'select';
		$attr['options'] = $nationalityOptions;
		$attr['onChangeReload'] = 'changeNationality';
		$attr['fieldName'] = $this->_table->alias().'.nationalities.0.country_id';

		return $attr;
	}

	public function onUpdateFieldIdentityType(Event $event, array $attr, $action, $request) {
		if (!empty($this->_info)) {
			if (array_key_exists('Identities', $this->_info)) {
				if ($this->_info['Identities'] == 'Non-Mandatory') {
					$attr['empty'] = 'Select';
				}
			}
		}

		$identityTypeOptions = TableRegistry::get('FieldOption.IdentityTypes')->getList();
		$attr['type'] = 'select';
		$attr['fieldName'] = $this->_table->alias().'.identities.0.identity_type_id';
		$attr['options'] = $identityTypeOptions->toArray();
		return $attr;
	}

	public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'string';
		$attr['fieldName'] = $this->_table->alias().'.identities.0.number';

		return $attr;
	}

	public function onUpdateFieldSpecialNeed(Event $event, array $attr, $action, $request) {
		if (!empty($this->_info)) {
			if (array_key_exists('SpecialNeeds', $this->_info)) {
				if ($this->_info['SpecialNeeds'] == 'Non-Mandatory') {
					$attr['empty'] = 'Select';
				}
			}
		}

		$specialNeedOptions = TableRegistry::get('FieldOption.SpecialNeedTypes')->getList();
		$attr['type'] = 'select';
		$attr['fieldName'] = $this->_table->alias().'.special_needs.0.special_need_type_id';
		$attr['options'] = $specialNeedOptions->toArray();
		
		return $attr;
	}

	public function onUpdateFieldSpecialNeedComment(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'text';
		$attr['fieldName'] = $this->_table->alias().'.special_needs.0.comment';

		return $attr;
	}

    // public function getMandatoryList() {
    //     $list = [0 => __('No'), 1 => __('Yes')];
    //     return $list;
    // }

    // public function getMandatoryVisibility($selectedFieldType) {
    //     $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_mandatory;
    //     return ($isMandatory == 1 ? true : false);
    // }

    // public function onGetIsMandatory(Event $event, Entity $entity) {
    //     $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $entity->field_type])->first()->is_mandatory;
    //     $is_mandatory = ($isMandatory == 0) ? '<i class="fa fa-minus"></i>' : ($entity->is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
    //     return $is_mandatory;
    // }
}
