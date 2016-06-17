<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class InfrastructureCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_infrastructure_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'Infrastructure.InfrastructureCustomTableRows', 'foreignKey' => 'infrastructure_custom_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'Infrastructure.InfrastructureCustomTableColumns', 'foreignKey' => 'infrastructure_custom_table_column_id']);
	}
}
