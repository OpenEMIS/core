<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomFieldsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFieldTypes', ['className' => 'CustomField.CustomFieldTypes', 'foreignKey' => 'field_type']);
		$this->hasMany('CustomFieldOptions', ['className' => 'CustomField.CustomFieldOptions', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'CustomField.CustomTableColumns', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'CustomField.CustomTableRows', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'CustomField.CustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'CustomField.CustomTableCells', 'dependent' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'CustomField.CustomForms',
			'joinTable' => 'custom_form_fields',
			'foreignKey' => 'custom_field_id',
			'targetForeignKey' => 'custom_form_id'
		]);
	}
}
