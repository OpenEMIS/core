<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('InfrastructureLevels', ['className' => 'Infrastructure.InfrastructureLevels']);
	}
}
