<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

class AbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_staff_absences');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffAbsenceReasons', ['className' => 'Institution.StaffAbsenceReasons']);
		$this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
	}

	public function beforeAction($event) {
		$this->fields['staff_absence_reason_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$query = $this->request->query;

		$this->fields['end_date']['visible'] = false; //Need to change last_date_absence to end_date
		$this->fields['full_day']['visible'] = false; //Need to change from full_day_absence to full_day
		$this->fields['start_time']['visible'] = false; //Need to change from start_time_absence to start_time
		$this->fields['end_time']['visible'] = false; //Need to change from end_time_absence to end_time
		$this->fields['comment']['visible'] = false;
		$this->fields['staff_id']['visible'] = false;

		$this->ControllerAction->addField('days', []);
		$this->ControllerAction->addField('time', []);

		$order = 0;
		$this->ControllerAction->setFieldOrder('start_date', $order++); //Need to change first_date_absence to start date
		$this->ControllerAction->setFieldOrder('days', $order++);
		$this->ControllerAction->setFieldOrder('time', $order++);
		$this->ControllerAction->setFieldOrder('staff_absence_reason_id', $order++);
		//$this->ControllerAction->setFieldOrder('absence_type', $order++); //Remove this line as the absence_type has been drop in the new table structure
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution->id;
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StaffAbsences',
				'view',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;

			// POCOR-1893 unset the view button on profiles controller
			if ($this->controller->name == 'Profiles') {
				unset($buttons['view']);
			}
			// end POCOR-1893
		}

		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
