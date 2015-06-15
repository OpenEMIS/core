<?php
namespace CustomField\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class CustomField extends Entity {
	protected $_virtual = ['is_mandatory', 'is_unique'];

	protected function _getIsMandatory($is_mandatory) {
		if (!is_null($is_mandatory)) {
			$selectedFieldType = $this->_properties['field_type'];
			$CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
			$isMandatory = $CustomFieldTypes->find('all')->where([$CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_mandatory;
			$is_mandatory = ($isMandatory == 0) ? '<i class="fa fa-minus"></i>' : ($is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
		}

		return $is_mandatory;
	}

	protected function _getIsUnique($is_unique) {
		if (!is_null($is_unique)) {
			$selectedFieldType = $this->_properties['field_type'];
			$CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
			$isUnique = $CustomFieldTypes->find('all')->where([$CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_unique;
			$is_unique = ($isUnique == 0) ? '<i class="fa fa-minus"></i>' : ($is_unique == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
		}

		return $is_unique;
	}
}
