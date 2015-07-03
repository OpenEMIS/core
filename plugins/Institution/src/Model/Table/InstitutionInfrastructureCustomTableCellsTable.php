<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class InstitutionInfrastructureCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		$this->table('institution_site_infrastructure_custom_table_cells');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_site_infrastructure_id']);
	}
}
