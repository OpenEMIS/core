<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class InfrastructureCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'infrastructure_custom_field_id',
			]);
		}
	}
}
