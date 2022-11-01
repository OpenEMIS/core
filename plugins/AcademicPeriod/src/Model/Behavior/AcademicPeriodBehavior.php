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
			'ControllerAction.Model.add.beforeSave' => 'addBeforeSave',
			'ControllerAction.Model.edit.beforePatch' => 'editBeforePatch',
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
			'ControllerAction.Model.view.afterAction' => 'viewAfterAction',
			'Model.custom.onUpdateActionButtons' => ['callable' => 'onUpdateActionButtons', 'priority' => 100]
		];

		if ($this->isCAv4()) {
			$newEvent['ControllerAction.Model.afterAction'] = 'afterAction';
		}

		$tableAlias = $this->_table->alias();

		switch ($tableAlias) {
			case 'InstitutionAssessments':
				$newEvent = ['ControllerAction.Model.onGetAssessmentId' => 'onGetAssessmentId'] + $newEvent;
				break;

			case 'StaffAttendances':
			case 'StudentAttendances':
				$newEvent = ['ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'] + $newEvent;
				break;
		}
		$events = array_merge($events, $newEvent);
		return $events;
	}

	private function isCAv4() {
		return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
	}

	public function indexBeforeAction(Event $event) {
		if(isset($this->_table->request->query['mode'])) {
			$academicPeriodId = $this->_table->request->query['academic_period_id'];
			$editable = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getEditable($academicPeriodId);
			if (!$editable) {
				if ($this->isCAv4()) {
					$urlParams = $this->_table->url('index');
				} else {
					$urlParams = $this->_table->ControllerAction->url('index');
				}
				if (isset($urlParams['mode'])) {
					unset($urlParams['mode']);
				}
				$event->stopPropagation();
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->has('academic_period_id')) {
			$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$isEditable = $AcademicPeriodTable->getEditable($entity->academic_period_id);
			if (! $isEditable) {
				if ($this->isCAv4()) {
					$urlParams = $this->_table->url('view');
				} else {
					$urlParams = $this->_table->ControllerAction->url('view');
				}
				$event->stopPropagation();
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		if ($entity->has('academic_period_id')) {
			$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$this->request->data[$this->_table->alias()]['editable'] = $AcademicPeriodTable->getEditable($entity->academic_period_id);
		}
	}

	public function afterAction(Event $event, $extra)
	{	
		$action = $this->_table->action;
		$toolbarButtons = new ArrayObject($extra['toolbarButtons']);
		$this->onUpdateToolbarButtons($event, new ArrayObject(), $toolbarButtons, [], $action, null);
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'view':
				if (isset($this->request->data[$this->_table->alias()]['editable'])) {
					$isEditable = $this->request->data[$this->_table->alias()]['editable'];
					if (!$isEditable) {
						if(isset($toolbarButtons['edit'])) {
							unset($toolbarButtons['edit']);
						}
						if(isset($toolbarButtons['remove'])) {
							unset($toolbarButtons['remove']);
						}
					}
				}
				break;
			case 'index':
				$tableAlias = $this->_table->alias();
				if ($tableAlias == 'StudentAttendances' || $tableAlias == 'StaffAttendances') {
					if ($this->_table->AccessControl->check(['Institutions', $tableAlias, 'indexEdit'])) {
						if (isset($this->_table->request->query['academic_period_id'])) {
							$academicPeriodId = $this->_table->request->query['academic_period_id'];
							$editable = 1;
							if ($academicPeriodId != 0 || !empty($academicPeriodId)) {
								$editable = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getEditable($academicPeriodId);
							}
							if (!isset($this->_table->request->query['mode'])) {
								if ($editable) {
									if ($tableAlias == 'StudentAttendances') {
										$toolbarButtons['edit'] = $buttons['index'];
								    	$toolbarButtons['edit']['url'][0] = 'index';
										$toolbarButtons['edit']['url']['mode'] = 'edit';
										$toolbarButtons['edit']['type'] = 'button';
										$toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
										$toolbarButtons['edit']['attr'] = $attr;
										$toolbarButtons['edit']['attr']['title'] = __('Edit');
									}
								} else {
									if ($tableAlias == 'StaffAttendances') {
										// used for CAv4 logic in StaffAttendance
										if ($toolbarButtons->offsetExists('indexEdit')) {
											$toolbarButtons->offsetUnset('indexEdit');
										}
									}
								}
							}
						}
					}
				}
				break;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$isEditable = 1;
		if ($entity->has('academic_period_id')) {
			$isEditable = $AcademicPeriodTable->getEditable($entity->academic_period_id);
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
			return $buttons;
		}	
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {

		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$isEditable = 1;
		if ($entity->has('academic_period_id')) {
			if (!empty($entity->academic_period_id)) {
				$isEditable = $AcademicPeriodTable->getEditable($entity->academic_period_id);
			}
		} else if (isset($data[$this->_table->alias()]['academic_period_id']) && !empty($data[$this->_table->alias()]['academic_period_id'])) {
			$academicPeriodId = $data[$this->_table->alias()]['academic_period_id'];
			if (!empty($academicPeriodId)) {
				$isEditable = $AcademicPeriodTable->get($academicPeriodId)->editable;
			}
		}
		if (! $isEditable) {
			$urlParams = $this->_table->ControllerAction->url('add');
			$event->stopPropagation();

			// Error message to tell user that they cannot add into a non-editable academic period
			$this->_table->Alert->error('general.academicPeriod.notEditable');
			return $this->_table->controller->redirect($urlParams);
		}
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		if ($entity->has('academic_period_id')) {
			// $academicPeriodId = $data[$this->_table->alias()]['academic_period_id'];
			$isEditable = $AcademicPeriodTable->getEditable($entity->academic_period_id);
			if (! $isEditable) {
				$urlParams = $this->_table->ControllerAction->url('edit');
				$event->stopPropagation();

				// Error message to tell user that they cannot add into a non-editable academic period
				$this->_table->Alert->error('general.academicPeriod.notEditable');
				return $this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function onGetAssessmentId(Event $event, Entity $entity) {
		$editable = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getEditable($entity->academic_period_id);
		if (! $editable) {
			$event->stopPropagation();
			return $entity->assessment->code_name;
		}
	}
}
