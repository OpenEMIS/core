<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StaffAbsencesTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_site_staff_absences');
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('StaffAbsenceReasons', ['className' => 'FieldOption.StaffAbsenceReasons']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
		return $validator;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('start_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('end_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('full_day');
		$this->ControllerAction->field('staff_absence_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('type');
		$this->ControllerAction->setFieldOrder([
			'security_user_id', 'full_day', 'start_date', 'end_date', 
			'start_time', 'end_time', 'type', 'staff_absence_reason_id'
		]);

		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAttendance'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAbsences'],
				'text' => __('Absence')
			]
		];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absence');
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$staff = [];

			$session = $request->session();
			if ($session->check('Institutions.id')) {
				$institutionId = $session->read('Institutions.id');

				$Staff = TableRegistry::get('Institution.InstitutionSiteStaff');
				$staff = $Staff
					->findAllByInstitutionSiteId($institutionId)
					->contain(['Users'])
					->find('list', ['keyField' => 'security_user_id', 'valueField' => 'staff_name'])
					->toArray();
			}
			$attr['options'] = $staff;
		}
		return $attr;
	}
}
