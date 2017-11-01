<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;

use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class AcademicPeriodLevelsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('level');
		$this->setFieldOrder(['level', 'name']);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['level']['type'] = 'hidden';
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->field('editable', ['visible' => false]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		if (!$entity->editable) {
			$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
			unset($toolbarButtonsArray['edit']);
			unset($toolbarButtonsArray['remove']);
			$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
		}
	}

	public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$query = $this->find();
			$results = $query
				->select(['level' => $query->func()->max('level')])
				->all();

			$maxLevel = 0;
			if (!$results->isEmpty()) {
				$data = $results->first();
				$maxLevel = $data->level;
			}

			$attr['attr']['value'] = ++$maxLevel;
		}

		return $attr;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			$event->stopPropagation();
			return $this->controller->redirect($this->url('index', false));
		}
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) {
		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			$event->stopPropagation();
			$extra['Alert']['message'] = 'general.delete.restrictDelete';
			// this replaces the $extra['result'] leading the conditional to reach Alert->error($extra['Alert']['message']).. probably need to revisit this as this doesnt allow for redirection here
			return false;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			unset($buttons['edit']);
			unset($buttons['remove']);
		}
    	return $buttons;
    }
}
