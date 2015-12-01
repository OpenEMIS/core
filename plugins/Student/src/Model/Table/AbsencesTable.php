<?php
namespace Student\Model\Table;

use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;

class AbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_absences');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction($event) {
		$this->fields['student_absence_reason_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$query = $this->request->query;

		$this->fields['end_date']['visible'] = false;
		$this->fields['full_day']['visible'] = false;
		$this->fields['start_time']['visible'] = false;
		$this->fields['end_time']['visible'] = false;
		$this->fields['comment']['visible'] = false;
		$this->fields['security_user_id']['visible'] = false;

		$this->ControllerAction->addField('days');
		$this->ControllerAction->addField('time');

		$order = 0;
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('days', $order++);
		$this->ControllerAction->setFieldOrder('time', $order++);
		$this->ControllerAction->setFieldOrder('student_absence_reason_id', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
		
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution->id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'StudentAbsences',
				'view', $entity->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getAcademicTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, $data) {
		if ($this->controller->name == 'Students') {
			$this->setupTabElements();
		}
	}
}
