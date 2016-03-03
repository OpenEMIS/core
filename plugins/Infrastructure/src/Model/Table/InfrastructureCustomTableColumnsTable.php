<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class InfrastructureCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->hasMany('CustomTableCells', ['className' => 'Infrastructure.InfrastructureCustomTableCells', 'foreignKey' => 'infrastructure_custom_table_column_id', 'dependent' => true]);
	}
}
