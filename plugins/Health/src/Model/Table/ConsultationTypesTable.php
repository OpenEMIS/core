<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class ConsultationTypesTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('health_consultation_types');
		parent::initialize($config);

		$this->hasMany('Consultations', ['className' => 'Health.Consultations', 'foreignKey' => 'health_consultation_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
