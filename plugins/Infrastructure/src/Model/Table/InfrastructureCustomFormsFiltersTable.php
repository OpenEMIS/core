<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureCustomFormsFiltersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'Infrastructure.InfrastructureCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
		$this->belongsTo('CustomFilters', ['className' => 'Infrastructure.InfrastructureTypes', 'foreignKey' => 'infrastructure_custom_filter_id']);
	}
}
