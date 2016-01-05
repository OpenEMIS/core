<?php
namespace Health\Model\Table;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class AllergiesTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('user_health_allergies');
		parent::initialize($config);

		$this->belongsTo('AllergyTypes', ['className' => 'Health.AllergyTypes', 'foreignKey' => 'health_allergy_type_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}

	public function onGetSevere(Event $event, Entity $entity) {
		$severeOptions = $this->getSelectOptions('general.yesno');
		return $severeOptions[$entity->severe];
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldSevere(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');
		return $attr;
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->field('severe');
		$this->ControllerAction->field('health_allergy_type_id', ['type' => 'select']);
	}
}
