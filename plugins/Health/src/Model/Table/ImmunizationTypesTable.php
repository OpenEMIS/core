<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ImmunizationTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_immunization_types');
		parent::initialize($config);

		$this->hasMany('Immunizations', ['className' => 'Health.Immunizations', 'foreignKey' => 'health_immunization_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
