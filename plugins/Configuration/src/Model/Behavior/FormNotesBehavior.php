<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class FormNotesBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';

		return $events;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFormNotes($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupFormNotes($entity);
	}

	private function setupFormNotes(Entity $entity) {
		if ($entity->type == 'Custom Validation') {
			$this->_table->fields['value']['attr']['onkeypress'] = 'return Config.inputMaskCheck(event)';
			$this->_table->field('form_notes', [
				'type' => 'element',
	            'element' => 'Configurations/form_notes',
	            'valueClass' => 'table-full-width',
	            'before' => 'value'
			]);
		}
	}
}
