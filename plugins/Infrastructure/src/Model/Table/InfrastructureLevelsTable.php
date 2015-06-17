<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);
	}
}
