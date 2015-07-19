<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class InstitutionCustomFormsFieldsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'Infrastructure.InfrastructureCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
	}
}
