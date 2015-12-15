<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'FieldOption.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('student_id', ['visible' => false]);
		$this->ControllerAction->field('student_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'StudentBehaviours',
				'view', $entity->id,
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Behaviours';
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
