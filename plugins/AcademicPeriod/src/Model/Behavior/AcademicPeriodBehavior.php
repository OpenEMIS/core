<?php 
namespace AcademicPeriod\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;

class AcademicPeriodBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.edit.afterAction' => 'editAfterAction',
			// 'ControllerAction.Model.add.afterAction' => 'addAfterAction',
			// 'ControllerAction.Model.addEdit.afterAction' => 'addEditAfterAction',
			'ControllerAction.Model.add.beforeSave' => 'addBeforeSave',
			'ControllerAction.Model.edit.beforePatch' => 'editBeforePatch',
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
			'ControllerAction.Model.view.afterAction' => 'viewAfterAction',
			'Model.custom.onUpdateActionButtons' => 'onUpdateActionButtons'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$isEditable = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
		if (! $isEditable) {
			$urlParams = $this->_table->ControllerAction->url('view');
			$event->stopPropagation();
			return $this->_table->controller->redirect($urlParams);
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$this->request->data[$this->_table->alias()]['editable'] = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			$isEditable = $this->request->data[$this->_table->alias()]['editable'];
			if (!$isEditable) {
				if(isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$isEditable = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
		if (! $isEditable) {
			if (isset($buttons['edit'])) {
				unset($buttons['edit']);
			}
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}
		// To stop calling the onUpdateActionButtons event on the table again
		$event->stopPropagation();
		return $buttons;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		// Check if the academic period that they are saving if is editable, if it is not then they shouldn't be able to save
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Check if the academic period that they are saving if is editable, if it is not then they shouldn't be able to update

	}
}
