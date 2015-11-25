<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingSessionsTraineesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
	}
}
