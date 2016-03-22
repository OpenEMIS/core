<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class AllergyTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_allergy_types');
		parent::initialize($config);

		$this->hasMany('Allergies', ['className' => 'Health.Allergies', 'foreignKey' => 'health_allergy_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
