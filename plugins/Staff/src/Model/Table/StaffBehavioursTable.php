<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;

class StaffBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'FieldOption.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('staff_id', ['visible' => false]);
		$this->ControllerAction->field('staff_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'staff_behaviour_category_id']);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'StaffBehaviours',
				'view', $entity->id,
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Behaviours');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
