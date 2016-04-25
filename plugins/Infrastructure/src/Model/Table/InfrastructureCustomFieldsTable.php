<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class InfrastructureCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.InfrastructureCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Infrastructure.InfrastructureCustomTableColumns', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Infrastructure.InfrastructureCustomTableRows', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'Infrastructure.InfrastructureCustomTableCells', 'dependent' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'Infrastructure.InfrastructureCustomForms',
			'joinTable' => 'infrastructure_custom_forms_fields',
			'foreignKey' => 'infrastructure_custom_field_id',
			'targetForeignKey' => 'infrastructure_custom_form_id',
			'through' => 'Infrastructure.InfrastructureCustomFormsFields',
			'dependent' => true
		]);
	}
}
