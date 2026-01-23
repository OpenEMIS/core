<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class StaffCustomFormsFiltersTable extends CustomFieldOptionsTable {
	public function initialize(array $config): void {
		$this->setTable('custom_forms_filters');
		parent::initialize($config);
	}
}
