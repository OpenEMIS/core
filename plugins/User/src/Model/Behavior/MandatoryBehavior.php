<?php
namespace User\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;

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
			$this->_info[$currModelName] = $this->getOptionValue($currModelName);
		}
		// pr($this->_info);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
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
			->where([$ConfigItems->aliasField('name') => $name])
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

	public function addBeforeAction(Event $event) {
		// mandatory associated fields
		$this->_table->ControllerAction->field('contact_type');
		$this->_table->ControllerAction->field('contact_value');
		$this->_table->ControllerAction->field('nationality');
		$this->_table->ControllerAction->field('identity_type');
		$this->_table->ControllerAction->field('identity_number');
		$this->_table->ControllerAction->field('special_need');
		$this->_table->ControllerAction->field('special_need_comment');

		$this->_table->ControllerAction->setFieldOrder(['openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'address', 'postal_code', 'gender_id', 'date_of_birth',
			// mandatory fields inserted here if behavior attached
			'contact_type', 'contact_value', 'nationality', 'identity_type', 'identity_number', 'special_need', 'special_need_comment', 
			'status','modified_user_id','modified','created_user_id','created'
		]);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		// pr('behavi addEditBeforePatch');
		// $options['associated'] = ['Identities', 'user_Nationalities', 'SpecialNeeds', 'Contacts'];

		// $options['validate'] = 'mandatory';
		// return compact('entity', 'data', 'options');
	}

	public function addOnChangeNationality(Event $event, Entity $entity, array $data, array $options) {
		$Countries = TableRegistry::get('FieldOption.Countries');
		$countryId = $data['Users']['user_nationalities'][0]['country_id'];
		$country = $Countries->findById($countryId)->first();
		$defaultIdentityType = $country->identity_type_id;
		if (is_null($defaultIdentityType)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentityType = $IdentityTypes->getDefaultValue();
		}
		
		$this->_table->fields['nationality']['default'] = $data['Users']['user_nationalities'][0]['country_id'];

		// overriding the  previous input to put in default identities
		$this->_table->fields['identity_type']['default'] = $defaultIdentityType;
		$data['Users']['identities'][0]['identity_type_id'] = $defaultIdentityType;

		$options['associated'] = [
			'InstitutionSiteStudents' => ['validate' => false],
			'InstitutionSiteStaff' => ['validate' => false],
			'Identities' => ['validate' => false],
			'UserNationalities' => ['validate' => false],
			'SpecialNeeds' => ['validate' => false],
			'Contacts' => ['validate' => false]
		];
		
		return compact('entity', 'data', 'options');
	}

	public function onUpdateFieldContactType(Event $event, array $attr, $action, $request) {
		$contactOptions = TableRegistry::get('User.ContactTypes')
			->find('list', ['keyField' => 'id', 'valueField' => 'full_contact_type_name'])
			->find('withContactOptions')
			->toArray();

		$attr['type'] = 'select';
		$attr['fieldName'] = 'Users.contacts.0.contact_type_id';
		$attr['options'] = $contactOptions;
		
		return $attr;
	}

	public function onUpdateFieldContactValue(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'string';
		$attr['fieldName'] = 'Users.contacts.0.value';

		return $attr;
	}

	public function onUpdateFieldNationality(Event $event, array $attr, $action, $request) {
		$Countries = TableRegistry::get('FieldOption.Countries');
		$nationalityOptions = $Countries->getList()->toArray();

		$attr['type'] = 'select';
		$attr['options'] = $nationalityOptions;
		$attr['onChangeReload'] = 'changeNationality';
		$attr['fieldName'] = 'Users.user_nationalities.0.country_id';

		return $attr;
	}

	public function onUpdateFieldIdentityType(Event $event, array $attr, $action, $request) {
		$identityTypeOptions = TableRegistry::get('FieldOption.IdentityTypes')->getList();
		$attr['type'] = 'select';
		$attr['fieldName'] = 'Users.identities.0.identity_type_id';
		$attr['options'] = $identityTypeOptions->toArray();
		return $attr;
	}

	public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'string';
		$attr['fieldName'] = 'Users.identities.0.number';

		return $attr;
	}

	public function onUpdateFieldSpecialNeed(Event $event, array $attr, $action, $request) {
		$specialNeedOptions = TableRegistry::get('FieldOption.SpecialNeedTypes')->getList();
		$attr['type'] = 'select';
		$attr['fieldName'] = 'Users.special_needs.0.special_need_type_id';
		$attr['options'] = $specialNeedOptions->toArray();

		return $attr;
	}

	public function onUpdateFieldSpecialNeedComment(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'string';
		$attr['fieldName'] = 'Users.special_needs.0.comment';

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
