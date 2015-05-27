<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureLevelsTable extends AppTable {
	public function initialize(array $config) {
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);
	}
}
