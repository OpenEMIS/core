<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class InfrastructureCustomFieldValuesTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		$config['extra'] = ['scope' => 'infrastructure_custom_field_id'];
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_infrastructure_id']);
	}
}
