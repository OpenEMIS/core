<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class InfrastructureCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->hasMany('CustomTableCells', ['className' => 'Infrastructure.InfrastructureCustomTableCells', 'foreignKey' => 'infrastructure_custom_table_row_id', 'dependent' => true]);
	}
}
