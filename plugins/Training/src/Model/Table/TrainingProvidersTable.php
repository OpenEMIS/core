<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingProvidersTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_provider_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
