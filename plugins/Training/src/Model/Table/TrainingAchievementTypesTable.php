<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingAchievementTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('Achievements', ['className' => 'Staff.Achievements', 'foreignKey' => 'training_achievement_type_id']);
	}
}
