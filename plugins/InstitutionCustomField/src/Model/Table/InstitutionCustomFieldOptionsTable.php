<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class InstitutionCustomFieldOptionsTable extends CustomFieldOptionsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'institution_custom_field_id',
			]);
		}
	}
}
