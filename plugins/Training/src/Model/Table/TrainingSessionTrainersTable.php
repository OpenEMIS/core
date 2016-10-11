<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingSessionTrainersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'trainer_id']);
	}
}
