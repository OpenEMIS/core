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

	public function initialize(array $config) {
		$roleFields = ['Identity', 'Nationality', 'Contact', 'SpecialNeed'];
		
		$this->_userRole = (array_key_exists('userRole', $config))? $config['userRole']: null;
		if (is_null($this->_userRole)) die('userRole must be set in mandatory behavior');

		$ConfigItems = TableRegistry::get('ConfigItems');

		$this->_info = [];
		foreach ($roleFields as $key => $value) {
			$currModelName = $this->_userRole.$value;
			$this->_info[$currModelName] = $this->getOptionValue($currModelName);
		}
		// pr($this->_info);
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
