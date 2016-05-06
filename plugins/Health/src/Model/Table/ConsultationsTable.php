<?php
namespace Health\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;

class ConsultationsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_consultations');
		parent::initialize($config);

		$this->belongsTo('ConsultationTypes', ['className' => 'Health.ConsultationTypes', 'foreignKey' => 'health_consultation_type_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('health_consultation_type_id', ['type' => 'select']);
	}
}
