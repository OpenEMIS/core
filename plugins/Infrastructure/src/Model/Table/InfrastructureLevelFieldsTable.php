<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormFieldsTable;

class InfrastructureLevelFieldsTable extends CustomFormFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
	}
}
