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

	public function onGetDate(Event $event, Entity $entity) {
		$startDate = date('d-m-Y', strtotime($entity->start_date));
		$endDate = date('d-m-Y', strtotime($entity->end_date));
		if ($entity->full_day == 1) {
			if (!empty($entity->end_date) && strtotime($entity->end_date) > strtotime($entity->start_date)) {
				$value = sprintf('%s - %s (%s)', $startDate, $endDate, __('full day'));
			} else {
				$value = sprintf('%s (%s)', $startDate, __('full day'));
			}
		} else {
			$value = sprintf('%s (%s - %s)', $startDate, $entity->start_time, $entity->end_time);
		}
		
		return $value;
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		return $entity->user->name_with_id;
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Absence.types');
		return $entity->staff_absence_reason_id == 0 ? $types['UNEXCUSED'] : $types['EXCUSED'];
	}

	public function onGetStaffAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->staff_absence_reason_id == 0) {
			return '-';
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('start_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('end_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('full_day');
		$this->ControllerAction->field('staff_absence_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('type');
		$this->ControllerAction->setFieldOrder([
			'academic_period', 'security_user_id', 'full_day', 'start_date', 'end_date', 
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

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('date');

		$this->fields['full_day']['visible'] = false;
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$this->ControllerAction->setFieldOrder(['date', 'security_user_id', 'type']);
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$staff = [];

			$institutionId = $this->Session->read('Institutions.id');
			$periodId = key($this->fields['academic_period']['options']);
			if ($request->is('post')) {
				$periodId = $this->request->data($this->aliasField('academic_period'));
			}

			$Staff = TableRegistry::get('Institution.InstitutionSiteStaff');
			$staff = $Staff
				->findAllByInstitutionSiteId($institutionId)
				->find('academicPeriod', ['academic_period_id' => $periodId])
				->contain(['Users'])
				->find('list', ['keyField' => 'security_user_id', 'valueField' => 'staff_name'])
				->toArray();
			$attr['options'] = $staff;
		}
		return $attr;
	}

	public function onUpdateFieldFullDay(Event $event, array $attr, $action, $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');
		$attr['onChangeReload'] = true;
		if ($request->is(['post', 'put'])) {
			if ($request->data($this->aliasField('full_day')) == 0) {
				$this->fields['start_time']['visible'] = true;
				$this->fields['end_time']['visible'] = true;
			}
		}
		return $attr;
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, $request) {
		$attr['options'] = $this->getSelectOptions('Absence.types');
		$attr['onChangeReload'] = true;
		if ($request->is(['post', 'put'])) {
			if ($request->data($this->aliasField('type')) == 'UNEXCUSED') {
				$this->fields['staff_absence_reason_id']['type'] = 'hidden';
				$this->fields['staff_absence_reason_id']['value'] = 0;
			}
		}
		return $attr;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->fields['security_user_id']['type'] = 'readonly';
		if ($entity->staff_absence_reason_id == 0) {
			$this->fields['type']['default'] = 'UNEXCUSED';
			$this->fields['staff_absence_reason_id']['type'] = 'hidden';
			$this->fields['staff_absence_reason_id']['value'] = 0;
		}
	}
}
