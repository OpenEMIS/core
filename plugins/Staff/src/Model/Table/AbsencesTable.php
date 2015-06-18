<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class AbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_staff_absences');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('StaffAbsenceReasons', ['className' => 'FieldOption.StaffAbsenceReasons']);
	}

	public function beforeAction($event) {
		$this->fields['staff_absence_reason_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$query = $this->request->query;

		$this->fields['last_date_absent']['visible'] = false;
		$this->fields['full_day_absent']['visible'] = false;
		$this->fields['start_time_absent']['visible'] = false;
		$this->fields['end_time_absent']['visible'] = false;
		$this->fields['comment']['visible'] = false;
		$this->fields['security_user_id']['visible'] = false;

		$this->ControllerAction->addField('days', []);
		$this->ControllerAction->addField('time', []);

		$order = 0;
		$this->ControllerAction->setFieldOrder('first_date_absent', $order++);
		$this->ControllerAction->setFieldOrder('days', $order++);
		$this->ControllerAction->setFieldOrder('time', $order++);
		$this->ControllerAction->setFieldOrder('staff_absence_reason_id', $order++);
		$this->ControllerAction->setFieldOrder('absence_type', $order++);
	}
}
