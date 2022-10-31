<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;
use ArrayObject;

class TrainingSessionsTraineesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
	}

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->training_session_id . ',' . $entity->trainee_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
