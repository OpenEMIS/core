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
		if (isset($entity->academic_period_id)) {
			$isEditable = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
			if (! $isEditable) {
				$urlParams = $this->_table->ControllerAction->url('view');
				$event->stopPropagation();
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		if (isset($entity->academic_period_id)) {
			$this->request->data[$this->_table->alias()]['editable'] = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if (isset($this->request->data[$this->_table->alias()]['editable'])) {
				$isEditable = $this->request->data[$this->_table->alias()]['editable'];
				if (!$isEditable) {
					if(isset($toolbarButtons['edit'])) {
						unset($toolbarButtons['edit']);
					}
				}
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$isEditable = 1;
		if (isset($entity->academic_period_id)) {
			$isEditable = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
		} else if (isset($this->_table->request->query['academic_period_id'])) {
			$academicPeriodId = $this->_table->request->query['academic_period_id'];
			if(!empty($academicPeriodId) || $academicPeriodId > 0) {
				$isEditable = $AcademicPeriodTable->get($academicPeriodId)->editable;
			}
		}
		if (! $isEditable) {
			if (isset($buttons['edit'])) {
				unset($buttons['edit']);
			}
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
			// To stop calling the onUpdateActionButtons event on the table again
			$event->stopPropagation();
			return $buttons;
		}	
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		if (isset($data[$this->_table->alias()]['academic_period_id'])) {
			$isEditable = $AcademicPeriodTable->get($data[$this->_table->alias()]['academic_period_id'])->editable;
			if (! $isEditable) {
				$urlParams = $this->_table->ControllerAction->url('add');
				$event->stopPropagation();

				// Error message to tell user that they cannot add into a non-editable academic period
				$this->_table->Alert->error('general.add.failed');
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		if (isset($entity->academic_period_id)) {
			$isEditable = $AcademicPeriodTable->get($entity->academic_period_id)->editable;
			if (! $isEditable) {
				$urlParams = $this->_table->ControllerAction->url('edit');
				$event->stopPropagation();

				// Error message to tell user that they cannot add into a non-editable academic period
				$this->_table->Alert->error('general.edit.failed');
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}
}
