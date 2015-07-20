<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormsFiltersTable;

class InfrastructureCustomFormsFiltersTable extends CustomFormsFiltersTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'Infrastructure.InstitutionCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
		$this->belongsTo('CustomFilters', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_custom_filter_id']);
	}
}
