<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class InstitutionCustomFieldOptionsTable extends CustomFieldOptionsTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_field_options');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Institution.InstitutionCustomFields', 'foreignKey' => 'institution_site_custom_field_id']);
	}
}
