<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ImmunizationTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('health_immunization_types');
		parent::initialize($config);
		$this->hasMany('Immunizations', ['className' => 'Health.Immunizations', 'foreignKey' => 'health_immunization_type_id', 'dependent' => true]);
	}
}
