<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingAchievementTypesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('ControllerAction.FieldOption');
        $this->table('training_achievement_types');
		parent::initialize($config);

		$this->hasMany('Achievements', ['className' => 'Staff.Achievements', 'foreignKey' => 'training_achievement_type_id']);
	}
}
