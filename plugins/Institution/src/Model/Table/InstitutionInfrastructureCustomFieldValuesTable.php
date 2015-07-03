<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class InstitutionInfrastructureCustomFieldValuesTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		$this->table('institution_site_infrastructure_custom_field_values');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_site_infrastructure_id']);
	}
}
