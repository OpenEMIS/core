<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Inflector;

class StaffAttendancesTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;
	private $allDayOptions = [];
	private $selectedDate = [];
	private $typeOptions = [];
	private $reasonOptions = [];
	private $_fieldOrder = ['openemis_no', 'staff_id'];
	private $dataCount = null;
	private $_absenceData = [];

	public function initialize(array $config) {
		$this->table('institution_staff');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses', ['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Excel', [
			'excludes' => [
				'start_date', 
				'end_date',
				'start_year',
				'end_year',
				'FTE',
				'staff_type_id',
				'staff_status_id',
				'institution_id',
				'institution_position_id'
			],
			'pages' => ['index']
		]);
		$this->addBehavior('Import.ImportLink');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$academicPeriodId = $this->request->query['academic_period_id'];
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query
			->where([$this->aliasField('institution_id') => $institutionId])
			->distinct([$this->aliasField('staff_id')])
			->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
	}

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$startDate = $AcademicPeriodTable->get($this->request->query['academic_period_id'])->start_date->format('Y-m-d');
		$endDate = $AcademicPeriodTable->get($this->request->query['academic_period_id'])->end_date->format('Y-m-d');
		$months = $AcademicPeriodTable->generateMonthsByDates($startDate, $endDate);
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$academicPeriodId = $this->request->query['academic_period_id'];
		foreach ($months as $month) {
			$year = $month['year'];
			$sheetName = $month['month']['inString'].' '.$year;
			$monthInNumber = $month['month']['inNumber'];
			$days = $AcademicPeriodTable->generateDaysOfMonth($year, $monthInNumber, $startDate, $endDate);
			$dates = [];
			foreach($days as $item){
				$dates[] = $item['date'];
			}
			$monthStartDay = $dates[0];
			$monthEndDay = $dates[count($dates) - 1];

			$sheets[] = [
				'name' => $sheetName,
				'table' => $this,
				'query' => $this
					->find()
					->select(['openemis_no' => 'Users.openemis_no'])
					->find('InDateRange', ['start_date' => $monthStartDay, 'end_date' => $monthEndDay])
					,
				'month' => $monthInNumber,
				'year' => $year,
				'startDate' => $monthStartDay,
				'endDate' => $monthEndDay,
				'institutionId' => $institutionId,
			];
		}
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
    	$newArray = [];
		$newArray[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];
		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
		$sheet = $settings['sheet'];
		$year = $sheet['year'];
		$month = $sheet['month'];
		$startDate = $sheet['startDate'];
		$endDate = $sheet['endDate'];
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$days = $AcademicPeriodTable->generateDaysOfMonth($year, $month, $startDate, $endDate);
		$workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
		$dayIndex = [];
		foreach($days as $item) {
			$dayIndex[] = $item['date'];
			if (in_array($item['weekDay'], $workingDays)) {
				$fields[] = [
					'key' => 'AcademicPeriod.days',
					'field' => 'attendance_field',
					'type' => 'attendance',
					'label' => sprintf('%s (%s)', $item['day'], $item['weekDay']),
					'date' => $item['date']
				];
			}
		}
		// Set the data into the temporary variable
		$this->_absenceData = $this->getData($startDate, $endDate, $sheet['institutionId']);
	}

	public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr) {
		// get the data from the temporary variable
		$absenceData = $this->_absenceData;
		if (isset($absenceData[$entity->staff_id][$attr['date']])) {
			$absenceObj = $absenceData[$entity->staff_id][$attr['date']];
			if (! $absenceObj['full_day']) {
				$startTimeAbsent = $absenceObj['start_time'];
				$endTimeAbsent = $absenceObj['end_time'];
				$timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_type']. ' (%s - %s)' , $startTimeAbsent, $endTimeAbsent);
				return $timeStr;
			} else{
				return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
			}
		}else{
			return '';
		}
	}

	public function getData($monthStartDay, $monthEndDay, $institutionId) {
		$StaffAbsencesTable = TableRegistry::get('Institution.StaffAbsences');
		$absenceData = $StaffAbsencesTable->find('all')
				->contain(['StaffAbsenceReasons'])
				->where([
					$StaffAbsencesTable->aliasField('institution_id') => $institutionId,
					$StaffAbsencesTable->aliasField('start_date').' >= ' => $monthStartDay,
					$StaffAbsencesTable->aliasField('end_date').' <= ' => $monthEndDay,
				])
				->select([
					'staff_id' => $StaffAbsencesTable->aliasField('staff_id'),
					'start_date' => $StaffAbsencesTable->aliasField('start_date'),
					'end_date' => $StaffAbsencesTable->aliasField('end_date'),
					'full_day' => $StaffAbsencesTable->aliasField('full_day'),
					'start_time' => $StaffAbsencesTable->aliasField('start_time'),
					'end_time' => $StaffAbsencesTable->aliasField('end_time'),
					'absence_type' => 'StaffAbsenceReasons.name'
				])
				->toArray();
		$absenceCheckList = [];
		foreach ($absenceData as $absenceUnit) {
			$staffId = $absenceUnit['staff_id'];
			$indexAbsenceDate = date('Y-m-d', strtotime($absenceUnit['start_date']));
			$absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;

			if ($absenceUnit['full_day'] && !empty($absenceUnit['end_date']) && $absenceUnit['end_date'] > $absenceUnit['start_date']) {
				$tempStartDate = date("Y-m-d", strtotime($absenceUnit['start_date']));
				$formatedLastDate = date("Y-m-d", strtotime($absenceUnit['end_date']));
				
				while ($tempStartDate <= $formatedLastDate) {
					$stampTempDate = strtotime($tempStartDate);
					$tempIndex = date('Y-m-d', $stampTempDate);

					$absenceCheckList[$staffId][$tempIndex] = $absenceUnit;

					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
					$tempStartDate = date("Y-m-d", $stampTempDateNew);
				}
			}
		}

		return $absenceCheckList;
	}

	public function beforeAction(Event $event) {
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

		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('staff_id', ['order' => 2]);

		$this->ControllerAction->field('FTE', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('staff_type_id', ['visible' => false]);
		$this->ControllerAction->field('staff_status_id', ['visible' => false]);
		$this->ControllerAction->field('institution_position_id', ['visible' => false]);
	}

	// Event: ControllerAction.Model.afterAction
	public function afterAction(Event $event, ArrayObject $config) {
		if (!is_null($this->request->query('mode'))) {
			if ($this->dataCount > 0) {
				$config['formButtons'] = true;
				$config['url'] = $config['buttons']['index']['url'];
				$config['url'][0] = 'indexEdit';
			}
		}
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	// Event: ControllerAction.Model.onGetType
	public function onGetType(Event $event, Entity $entity) {
		$html = '';

		if (!is_null($this->request->query('mode'))) {
			$Form = $event->subject()->Form;

			$institutionId = $this->Session->read('Institution.Institutions.id');
			$id = $entity->staff_id;
			$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
			
			$alias = Inflector::underscore($StaffAbsences->alias());
			$fieldPrefix = $StaffAbsences->Users->alias() . '.'.$alias.'.' . $id;
			$options = ['type' => 'select', 'label' => false, 'options' => $this->typeOptions, 'onChange' => '$(".type_'.$id.'").hide();$("#type_'.$id.'_"+$(this).val()).show();'];
			if (empty($entity->StaffAbsences['id'])) {
				$options['value'] = 'PRESENT';
			} else {
				$html .= $Form->hidden($fieldPrefix.".id", ['value' => $entity->StaffAbsences['id']]);
				if (empty($entity->StaffAbsences['staff_absence_reason_id'])) {
					$options['value'] = 'UNEXCUSED';
				} else {
					$options['value'] = 'EXCUSED';
				}
			}			

			$html .= $Form->input($fieldPrefix.".absence_type", $options);
			$html .= $Form->hidden($fieldPrefix.".institution_id", ['value' => $institutionId]);
			$html .= $Form->hidden($fieldPrefix.".staff_id", ['value' => $id]);

			$selectedDate = $this->selectedDate->format('d-m-Y');
			$html .= $Form->hidden($fieldPrefix.".full_day", ['value' => 1]);	//full day
			$html .= $Form->hidden($fieldPrefix.".start_date", ['value' => $selectedDate]);
			$html .= $Form->hidden($fieldPrefix.".end_date", ['value' => $selectedDate]);
		} else {
			$types = $this->getSelectOptions('Absence.types');
			$type = $types['EXCUSED'];

			if (empty($entity->StaffAbsences['id'])) {
				$type = '<i class="fa fa-check"></i>';
			} else {
				if (empty($entity->StaffAbsences['staff_absence_reason_id'])) {
					$type = $types['UNEXCUSED'];
				}
			}
			$html .= $type;
		}

		return $html;
	}

	// Event: ControllerAction.Model.onGetReason
	public function onGetReason(Event $event, Entity $entity) {
		$html = '';

		if (!is_null($this->request->query('mode'))) {
			$Form = $event->subject()->Form;

			$id = $entity->staff_id;
			$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');

			$alias = Inflector::underscore($StaffAbsences->alias());
			$fieldPrefix = $StaffAbsences->Users->alias() . '.'.$alias.'.' . $id;

			$presentDisplay = 'display: none;';
			$excusedDisplay = 'display: none;';
			$unexcusedDisplay = 'display: none;';
			$reasonId = 0;
			if (empty($entity->StaffAbsences['id'])) {
				$presentDisplay = '';	// PRESENT
			} else {
				if (empty($entity->StaffAbsences['staff_absence_reason_id'])) {
					$unexcusedDisplay = '';	// UNEXCUSED
				} else {
					$excusedDisplay = '';	// EXCUSED
					$reasonId = $entity->StaffAbsences['staff_absence_reason_id'];
				}
			}
			foreach ($this->typeOptions as $key => $value) {
				switch($key) {
					case 'PRESENT':
						$html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$presentDisplay.'">';
							$html .= '<i class="fa fa-minus"></i>';
						$html .= '</span>';
						break;
					case 'EXCUSED':
						$html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$excusedDisplay.'">';
							$options = ['type' => 'select', 'label' => false, 'options' => $this->reasonOptions];
							if ($reasonId != 0) {
								$options['value'] = $reasonId;
							}
							$html .= $Form->input($fieldPrefix.".staff_absence_reason_id", $options);
						$html .= '</span>';
						break;
					case 'UNEXCUSED':
						$html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$unexcusedDisplay.'">';
							$html .= '<i class="fa fa-minus"></i>';
						$html .= '</span>';
						break;
				}
			}
		} else {
			$reasonId = $entity->StaffAbsences['staff_absence_reason_id'];
			$StaffAbsenceReasons = TableRegistry::get('FieldOption.StaffAbsenceReasons');

			if (!empty($reasonId)) {
				$obj = $StaffAbsenceReasons->findById($reasonId)->first();
				$html .= $obj['name'];
			} else {
				$html .= '<i class="fa fa-minus"></i>';
			}
		}

		return $html;
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
					$absenceType = 'UNEXCUSED';
				} else {
					$absenceType = 'EXCUSED';
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

		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
		}

		$Staff = $this;
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->request->query['academic_period_id'];

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->findByInstitutionId($institutionId)
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End setup periods
		$this->request->query['academic_period_id'] = $selectedPeriod;

		if ($selectedPeriod != 0) {
			$todayDate = date("Y-m-d");
			$this->controller->set(compact('periodOptions', 'selectedPeriod'));

			// Setup week options
			$weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
			$weekStr = 'Week %d (%s - %s)';
			$weekOptions = [];
			$currentWeek = null;
			foreach ($weeks as $index => $dates) {
				if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
					$weekStr = __('Current Week') . ' %d (%s - %s)';
					$currentWeek = $index;
				} else {
					$weekStr = 'Week %d (%s - %s)';
				}
				$weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
			}
			$selectedYear = $AcademicPeriod->get($selectedPeriod)->start_year;
			if ($selectedYear == date("Y") && !is_null($currentWeek)) {
				$selectedWeek = !is_null($this->request->query('week')) ? $this->request->query('week') : $currentWeek;
			} else {
				$selectedWeek = $this->queryString('week', $weekOptions);
			}
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
					$this->selectedDate = $currentDay->next($selectedDay);
				} else {
					$this->selectedDate = $currentDay;
				}
			} else {
				$this->selectedDate = $week;
			}
			$this->controller->set(compact('dayOptions', 'selectedDay'));
			// End setup days

			$settings['pagination'] = false;
			$query
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->contain(['Users'])
				->find('withAbsence', ['date' => $this->selectedDate])
				->where([$this->aliasField('institution_id') => $institutionId])
				;

			if ($selectedDay == -1) {
				foreach ($this->allDayOptions as $key => $obj) {
					$this->ControllerAction->addField($key);
					$this->_fieldOrder[] = $key;
				}
			} else {
				$this->ControllerAction->field('type');
				$this->ControllerAction->field('reason');
				$this->_fieldOrder[] = 'type';
				$this->_fieldOrder[] = 'reason';

				$this->typeOptions = [
					'PRESENT' => __('Present'),
					'EXCUSED' => __('Absent - Excused'),
					'UNEXCUSED' => __('Absent - Unexcused')
				];

				$StaffAbsenceReasons = TableRegistry::get('FieldOption.StaffAbsenceReasons');
				$this->reasonOptions = $StaffAbsenceReasons->getList()->toArray();
			}
		} else {
			$settings['pagination'] = false;
			$query
				->where([$this->aliasField('staff_id') => 0]);

			$this->ControllerAction->field('type');
			$this->ControllerAction->field('reason');

			$this->Alert->warning('StaffAttendances.noStaff');
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$this->dataCount = $data->count();
	}

	public function findWithAbsence(Query $query, array $options) {
		$date = $options['date'];

		$conditions = ['StaffAbsences.staff_id = StaffAttendances.staff_id'];
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
						'StaffAbsences.end_date <=' => $endDate
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
    			$this->aliasField('staff_id'), 
    			'Users.openemis_no', 'Users.first_name', 'Users.last_name',
    			'StaffAbsences.id',
    			'StaffAbsences.start_date',
    			'StaffAbsences.end_date',
    			'StaffAbsences.staff_absence_reason_id'
    		])
			->join([
				[
					'table' => 'institution_staff_absences',
					'alias' => 'StaffAbsences',
					'type' => 'LEFT',
					'conditions' => $conditions
				]
			])
			->order(['Users.openemis_no'])
			;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	if ($this->AccessControl->check(['Institutions', 'StaffAttendances', 'indexEdit'])) {
	    	if ($this->request->query('day') != -1) {
	    		if (!is_null($this->request->query('mode'))) {
	    			$toolbarButtons['back'] = $buttons['back'];
					if ($toolbarButtons['back']['url']['mode']) {
						unset($toolbarButtons['back']['url']['mode']);
					}
					$toolbarButtons['back']['type'] = 'button';
					$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
					$toolbarButtons['back']['attr'] = $attr;
					$toolbarButtons['back']['attr']['title'] = __('Back');

					if (isset($toolbarButtons['export'])) {
						unset($toolbarButtons['export']);
					}
				} else {
					$toolbarButtons['back'] = $buttons['back'];
					$toolbarButtons['back']['type'] = null;
				}
			}
		}
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestQuery = $this->request->query;
			$requestData = $this->request->data;
			$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
			$alias = Inflector::underscore($StaffAbsences->alias());

			if (array_key_exists($StaffAbsences->Users->alias(), $requestData)) {
				if (array_key_exists($alias, $requestData[$StaffAbsences->Users->alias()])) {
					foreach ($requestData[$StaffAbsences->Users->alias()][$alias] as $key => $obj) {
						$obj['academic_period_id'] = $requestQuery['academic_period_id'];
						if ($obj['absence_type'] == 'UNEXCUSED') {
							$obj['staff_absence_reason_id'] = 0;
						}

						if ($obj['absence_type'] == 'PRESENT') {
							if (isset($obj['id'])) {
								$StaffAbsences->deleteAll([
									$StaffAbsences->aliasField('id') => $obj['id']
								]);
							}
						} else if ($obj['absence_type'] == 'EXCUSED' || $obj['absence_type'] == 'UNEXCUSED') {
							$entity = $StaffAbsences->newEntity($obj);
							if ($StaffAbsences->save($entity)) {
							} else {
								$this->log($entity->errors(), 'debug');
							}
						}
					}
				}
			}
		}
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => $this->alias];
		$url = array_merge($url, $this->request->query, $this->request->pass);
		$url[0] = 'index';
		if (isset($url['mode'])) {
			unset($url['mode']);
		}

		return $this->controller->redirect($url);
	}
}
