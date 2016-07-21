<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingPrioritiesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
        $this->table('training_priorities');
		parent::initialize($config);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_priority_id']);
	}
}
