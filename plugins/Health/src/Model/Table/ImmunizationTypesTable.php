<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class ImmunizationTypesTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('health_immunization_types');
		parent::initialize($config);

		$this->hasMany('Immunizations', ['className' => 'Health.Immunizations', 'foreignKey' => 'health_immunization_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
