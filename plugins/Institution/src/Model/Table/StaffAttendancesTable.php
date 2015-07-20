<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class StaffAttendancesTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;
	private $allDayOptions = [];

	public function initialize(array $config) {
		$this->table('institution_site_staff');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses', ['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('InstitutionSitePositions', ['className' => 'Institution.InstitutionSitePositions']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);

		$this->addBehavior('AcademicPeriod.Period');
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('security_user_id', ['order' => 2]);

		$this->ControllerAction->field('FTE', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('staff_type_id', ['visible' => false]);
		$this->ControllerAction->field('staff_status_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_position_id', ['visible' => false]);

		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAttendances'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAbsences'],
				'text' => __('Absence')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Attendance');
	}

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	// Event: ControllerAction.Model.onGetType
	public function onGetType(Event $event, Entity $entity) {
		if (!is_null($this->request->query('mode'))) {
		} else {
			$types = $this->getSelectOptions('Absence.types');
			$type = $types['EXCUSED'];

			if (empty($entity->StaffAbsences['staff_absence_reason_id'])) {
				$type = $types['UNEXCUSED'];
			}
		}

		return $type;
	}

	// Event: ControllerAction.Model.onGetReason
	public function onGetReason(Event $event, Entity $entity) {
		$reasonId = $entity->StaffAbsences['staff_absence_reason_id'];
		$StaffAbsenceReasons = TableRegistry::get('FieldOption.StaffAbsenceReasons');

		if (!empty($reasonId)) {
			$obj = $StaffAbsenceReasons->findById($reasonId)->first();
			return $obj['name'];
		} else {
			return '-';
		}
	}

	public function onGetSunday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'sunday');
	}

	public function onGetMonday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'monday');
	}

	public function onGetTuesday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'tuesday');
	}

	public function onGetWednesday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'wednesday');
	}

	public function onGetThursday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'thursday');
	}

	public function onGetFriday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'friday');
	}

	public function onGetSaturday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'saturday');
	}

	public function getAbsenceData(Event $event, Entity $entity, $key) {
		$value = '<i class="fa fa-check"></i>';

		if (isset($entity->StaffAbsences['id'])) {
			$startDate = $entity->StaffAbsences['start_date'];
			$endDate = $entity->StaffAbsences['end_date'];
			$currentDay = $this->allDayOptions[$key]['date'];
			if ($currentDay >= $startDate && $currentDay <= $endDate) {
				$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
				$absenceQuery = $StaffAbsences
					->findById($entity->StaffAbsences['id'])
					->contain('StaffAbsenceReasons');
				$absenceResult = $absenceQuery->first();

				$typeOptions = $this->getSelectOptions('Absence.types');
				if (empty($absenceResult->staff_absence_reason)) {
					$absenceType = 'EXCUSED';
				} else {
					$absenceType = 'UNEXCUSED';
				}
				if ($absenceResult->full_day == 0) {
					$urlLink = sprintf(__('Absent') . ' - ' . $typeOptions[$absenceType]. ' (%s - %s)' , $absenceResult->start_time, $absenceResult->end_time);
				} else {
					$urlLink = __('Absent') . ' - ' . $typeOptions[$absenceType]. ' (full day)';
				}

				$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
				$value = $event->subject()->Html->link($urlLink, [
					'plugin' => $this->controller->plugin,
					'controller' => $this->controller->name,
					'action' => $StaffAbsences->alias(),
					'view',
					$entity->StaffAbsences['id']
				]);
			}
		}

		return $value;
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$toolbarElements = [
			['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();

		$Staff = $this;
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->queryString('period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->findByInstitutionSiteId($institutionId)
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End setup periods

		if ($selectedPeriod != 0) {
			$this->controller->set(compact('periodOptions', 'selectedPeriod'));

			// Setup week options
			$weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
			$weekStr = 'Week %d (%s - %s)';
			$weekOptions = [];
			foreach ($weeks as $index => $dates) {
				$weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
			}
			$selectedWeek = $this->queryString('week', $weekOptions);
			$this->advancedSelectOptions($weekOptions, $selectedWeek);
			$this->controller->set(compact('weekOptions', 'selectedWeek'));
			// end setup weeks

			// Setup day options
			$ConfigItems = TableRegistry::get('ConfigItems');
			$firstDayOfWeek = $ConfigItems->value('first_day_of_week');
			$daysPerWeek = $ConfigItems->value('days_per_week');
			$schooldays = [];

			for($i=0; $i<$daysPerWeek; $i++) {
				$schooldays[] = ($firstDayOfWeek + $i) % 7;
			}

			$week = $weeks[$selectedWeek];
			$dayOptions = [-1 => ['value' => -1, 'text' => __('All Days')]];
			$firstDayOfWeek = $week[0]->copy();
			$firstDay = -1;
			$today = null;

			do {
				if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
					if ($firstDay == -1) {
						$firstDay = $firstDayOfWeek->dayOfWeek;
					}
					if ($firstDayOfWeek->isToday()) {
						$today = $firstDayOfWeek->dayOfWeek;
					}
					$dayOptions[$firstDayOfWeek->dayOfWeek] = [
						'value' => $firstDayOfWeek->dayOfWeek,
						'text' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ')',
					];
					$this->allDayOptions[strtolower($firstDayOfWeek->format('l'))] = [
						'date' => $firstDayOfWeek->format('Y-m-d'),
						'text' => __($firstDayOfWeek->format('l'))
					];
				}
				$firstDayOfWeek->addDay();
			} while($firstDayOfWeek->lte($week[1]));

			$selectedDay = -1;
			if (isset($this->request->query['day'])) {
				$selectedDay = $this->request->query('day');
				if (!array_key_exists($selectedDay, $dayOptions)) {
					$selectedDay = $firstDay;
				}
			} else {
				if (!is_null($today)) {
					$selectedDay = $today;
				} else {
					$selectedDay = $firstDay;
				}
			}
			$dayOptions[$selectedDay][] = 'selected';
			
			$currentDay = $week[0]->copy();
			if ($selectedDay != -1) {
				if ($currentDay->dayOfWeek != $selectedDay) {
					$selectedDate = $currentDay->next($selectedDay);
				} else {
					$selectedDate = $currentDay;
				}
			} else {
				$selectedDate = $week;
			}
			$this->controller->set(compact('dayOptions', 'selectedDay'));
			// End setup days

			$settings['pagination'] = false;
			$query
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->contain(['Users'])
				->find('withAbsence', ['date' => $selectedDate])
				->where([$this->aliasField('institution_site_id') => $institutionId])
				;

			if ($selectedDay == -1) {
				foreach ($this->allDayOptions as $key => $obj) {
					$this->ControllerAction->addField($key);
				}
			} else {
				$this->ControllerAction->field('type');
				$this->ControllerAction->field('reason');
			}
		} else {
			$settings['pagination'] = false;
			$query
				->where([$this->aliasField('security_user_id') => 0]);

			$this->ControllerAction->field('type');
			$this->ControllerAction->field('reason');

			$this->Alert->warning('StaffAttendances.noStaff');
		}
	}

	public function findWithAbsence(Query $query, array $options) {
		$date = $options['date'];

		$conditions = ['StaffAbsences.security_user_id = StaffAttendances.security_user_id'];
		if (is_array($date)) {
			$startDate = $date[0]->format('Y-m-d');
			$endDate = $date[1]->format('Y-m-d');

			$conditions['OR'] = [
				'OR' => [
					[
						'StaffAbsences.end_date IS NOT NULL',
						'StaffAbsences.start_date >=' => $startDate,
						'StaffAbsences.start_date <=' => $endDate
					],
					[
						'StaffAbsences.end_date IS NOT NULL',
						'StaffAbsences.start_date <=' => $startDate,
						'StaffAbsences.end_date >=' => $startDate
					],
					[
						'StaffAbsences.end_date IS NOT NULL',
						'StaffAbsences.start_date <=' => $endDate,
						'StaffAbsences.end_date >=' => $endDate
					],
					[
						'StaffAbsences.end_date IS NOT NULL',
						'StaffAbsences.start_date >=' => $startDate,
						'StaffAbsences.end_date <=' => $startDate
					]
				],
				[
					'StaffAbsences.end_date IS NULL',
					'StaffAbsences.start_date <=' => $endDate
				]
			];
		} else {
			$conditions['StaffAbsences.start_date <= '] = $date->format('Y-m-d');
			$conditions['StaffAbsences.end_date >= '] = $date->format('Y-m-d');
		}
    	return $query
    		->select([
    			$this->aliasField('security_user_id'), 
    			'Users.openemis_no', 'Users.first_name', 'Users.last_name',
    			'StaffAbsences.id',
    			'StaffAbsences.start_date',
    			'StaffAbsences.end_date',
    			'StaffAbsences.staff_absence_reason_id'
    		])
			->join([
				[
					'table' => 'institution_site_staff_absences',
					'alias' => 'StaffAbsences',
					'type' => 'LEFT',
					'conditions' => $conditions
				]
			])
			->order(['Users.openemis_no'])
			;
    }
}
