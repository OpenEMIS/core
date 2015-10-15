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

class StudentAttendancesTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;
	private $allDayOptions = [];
	private $selectedDate = [];
	private $typeOptions = [];
	private $reasonOptions = [];
	private $_fieldOrder = ['openemis_no', 'student_id'];
	private $dataCount = null;
	private $_absenceData = [];

	public function initialize(array $config) {
		$this->table('institution_site_section_students');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Excel', [
			'excludes' => ['status', 'education_grade_id'],
			'pages' => ['index']
		]);
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$sectionId = $this->request->query['section_id'];
		$query->where([$this->aliasField('institution_site_section_id') => $sectionId]);
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$academicPeriodId = $this->request->query['academic_period_id'];
		$startDate = $AcademicPeriodTable->get($academicPeriodId)->start_date->format('Y-m-d');
		$endDate = $AcademicPeriodTable->get($academicPeriodId)->end_date->format('Y-m-d');
		$months = $AcademicPeriodTable->generateMonthsByDates($startDate, $endDate);
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$sectionId = $this->request->query['section_id'];

		foreach ($months as $month) {
			$year = $month['year'];
			$sheetName = $month['month']['inString'].' '.$year;
			$monthInNumber = $month['month']['inNumber'];
 			$sheets[] = [
 				'name' => $sheetName,
 				'table' => $this,
 				'query' => $this
 					->find()
 					->select(['openemis_no' => 'Users.openemis_no']),
				'month' => $monthInNumber,
				'year' => $year,
				'startDate' => $startDate,
				'endDate' => $endDate,
				'institutionId' => $institutionId,
				'sectionId' => $sectionId,
 			];
		}
	}

	// To select another one more field from the containable data
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
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
		$dayIndex = [];
		$workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
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
		$startDate = $dayIndex[0];
		$endDate = $dayIndex[count($dayIndex)-1];

		// Set data into a temporary variable
		$this->_absenceData = $this->getData($startDate, $endDate, $sheet['institutionId'], $sheet['sectionId']);
	}

	public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr) {
		// Get the data from the temporary variable
		$absenceData = $this->_absenceData;

		if (isset($absenceData[$entity->student_id][$attr['date']])) {
			$absenceObj = $absenceData[$entity->student_id][$attr['date']];
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

	public function getData($monthStartDay, $monthEndDay, $institutionId, $sectionId) {		
		$StudentAbsenceTable = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');

		$absenceData = $StudentAbsenceTable->find('all')
			->contain(['StudentAbsenceReasons'])
			->where([
				$StudentAbsenceTable->aliasField('institution_site_id') => $institutionId,
				$StudentAbsenceTable->aliasField('start_date').' >= ' => $monthStartDay,
				$StudentAbsenceTable->aliasField('end_date').' <= ' => $monthEndDay,
			])
			->innerJoin(
				['InstitutionSiteSectionStudents' => 'institution_site_section_students'],
				[
					'InstitutionSiteSectionStudents.student_id = '.$StudentAbsenceTable->aliasField('security_user_id'),
					'InstitutionSiteSectionStudents.institution_site_section_id' => $sectionId
				]
			)
			->select([
					'security_user_id' => $StudentAbsenceTable->aliasField('security_user_id'),
					'start_date' => $StudentAbsenceTable->aliasField('start_date'),
					'end_date' => $StudentAbsenceTable->aliasField('end_date'),
					'full_day' => $StudentAbsenceTable->aliasField('full_day'),
					'start_time' => $StudentAbsenceTable->aliasField('start_time'),
					'end_time' => $StudentAbsenceTable->aliasField('end_time'),
					'absence_type' => 'StudentAbsenceReasons.name'
				])
			->toArray();
		$absenceCheckList = [];
		foreach ($absenceData AS $absenceUnit) {
			$studentId = $absenceUnit['security_user_id'];
			$indexAbsenceDate = date('Y-m-d', strtotime($absenceUnit['start_date']));

			$absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;

			if ($absenceUnit['full_day'] && !empty($absenceUnit['end_date']) && $absenceUnit['end_date'] > $absenceUnit['start_date']) {
				$tempStartDate = date("Y-m-d", strtotime($absenceUnit['start_date']));
				$formatedLastDate = date("Y-m-d", strtotime($absenceUnit['end_date']));
				
				while ($tempStartDate <= $formatedLastDate) {
					$stampTempDate = strtotime($tempStartDate);
					$tempIndex = date('Y-m-d', $stampTempDate);
					$absenceCheckList[$studentId][$tempIndex] = $absenceUnit;
					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
					$tempStartDate = date("Y-m-d", $stampTempDateNew);
				}
			}
		}
		return $absenceCheckList;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	// Event: ControllerAction.Model.beforeAction
	public function beforeAction(Event $event) {
		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendances'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAbsences'],
				'text' => __('Absence')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Attendance');

		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('student_id');
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('education_grade_id', ['visible' => false]);
		$this->ControllerAction->field('status', ['visible' => false]);
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
		$studentId = $entity->student_id;
		$StudentTable = TableRegistry::get('Institution.Students');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		if (!is_null($this->request->query('mode')) && $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
			$Form = $event->subject()->Form;

			$institutionId = $this->Session->read('Institution.Institutions.id');
			$id = $entity->student_id;
			$StudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
			
			$alias = Inflector::underscore($StudentAbsences->alias());
			$fieldPrefix = $StudentAbsences->Users->alias() . '.'.$alias.'.' . $id;
			$options = ['type' => 'select', 'label' => false, 'options' => $this->typeOptions, 'onChange' => '$(".type_'.$id.'").hide();$("#type_'.$id.'_"+$(this).val()).show();'];
			if (empty($entity->StudentAbsences['id'])) {
				$options['value'] = 'PRESENT';
			} else {
				$html .= $Form->hidden($fieldPrefix.".id", ['value' => $entity->StudentAbsences['id']]);
				if (empty($entity->StudentAbsences['student_absence_reason_id'])) {
					$options['value'] = 'UNEXCUSED';
				} else {
					$options['value'] = 'EXCUSED';
				}
			}

			$html .= $Form->input($fieldPrefix.".absence_type", $options);
			$html .= $Form->hidden($fieldPrefix.".institution_site_id", ['value' => $institutionId]);
			$html .= $Form->hidden($fieldPrefix.".security_user_id", ['value' => $id]);

			$selectedDate = $this->selectedDate->format('d-m-Y');
			$html .= $Form->hidden($fieldPrefix.".full_day", ['value' => 1]);	//full day
			$html .= $Form->hidden($fieldPrefix.".start_date", ['value' => $selectedDate]);
			$html .= $Form->hidden($fieldPrefix.".end_date", ['value' => $selectedDate]);
		} else {
			$types = $this->getSelectOptions('Absence.types');
			$type = $types['EXCUSED'];

			if (empty($entity->StudentAbsences['id'])) {
				$type = '<i class="fa fa-check"></i>';
			} else {
				if (empty($entity->StudentAbsences['student_absence_reason_id'])) {
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
		$studentId = $entity->student_id;
		$StudentTable = TableRegistry::get('Institution.Students');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		if (!is_null($this->request->query('mode')) && $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
			$Form = $event->subject()->Form;

			$id = $entity->student_id;
			$StudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');

			$alias = Inflector::underscore($StudentAbsences->alias());
			$fieldPrefix = $StudentAbsences->Users->alias() . '.'.$alias.'.' . $id;

			$presentDisplay = 'display: none;';
			$excusedDisplay = 'display: none;';
			$unexcusedDisplay = 'display: none;';
			$reasonId = 0;
			if (empty($entity->StudentAbsences['id'])) {
				$presentDisplay = '';	// PRESENT
			} else {
				if (empty($entity->StudentAbsences['student_absence_reason_id'])) {
					$unexcusedDisplay = '';	// UNEXCUSED
				} else {
					$excusedDisplay = '';	// EXCUSED
					$reasonId = $entity->StudentAbsences['student_absence_reason_id'];
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
							$html .= $Form->input($fieldPrefix.".student_absence_reason_id", $options);
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
			$reasonId = $entity->StudentAbsences['student_absence_reason_id'];
			$StudentAbsenceReasons = TableRegistry::get('FieldOption.StudentAbsenceReasons');

			if (!empty($reasonId)) {
				$obj = $StudentAbsenceReasons->findById($reasonId)->first();
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

		if (isset($entity->StudentAbsences['id'])) {
			$startDate = $entity->StudentAbsences['start_date'];
			$endDate = $entity->StudentAbsences['end_date'];
			$currentDay = $this->allDayOptions[$key]['date'];
			if ($currentDay >= $startDate && $currentDay <= $endDate) {
				$InstitutionSiteStudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
				$absenceQuery = $InstitutionSiteStudentAbsences
					->findById($entity->StudentAbsences['id'])
					->contain('StudentAbsenceReasons');
				$absenceResult = $absenceQuery->first();

				$typeOptions = $this->getSelectOptions('Absence.types');
				if (empty($absenceResult->student_absence_reason)) {
					$absenceType = 'UNEXCUSED';
				} else {
					$absenceType = 'EXCUSED';
				}
				if ($absenceResult->full_day == 0) {
					$urlLink = sprintf(__('Absent') . ' - ' . $typeOptions[$absenceType]. ' (%s - %s)' , $absenceResult->start_time, $absenceResult->end_time);
				} else {
					$urlLink = __('Absent') . ' - ' . $typeOptions[$absenceType]. ' (full day)';
				}

				$StudentAbsences = TableRegistry::get('Institution.StudentAbsences');
				$value = $event->subject()->Html->link($urlLink, [
					'plugin' => $this->controller->plugin,
					'controller' => $this->controller->name,
					'action' => $StudentAbsences->alias(),
					'view',
					$entity->StudentAbsences['id']
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
		
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
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

			// Setup section options
			$userId = $this->Auth->user('id');
			$AccessControl = $this->AccessControl;
			$sectionOptions = $Sections
				->find('list')
				->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]) // restrict user to see own class if permission is set
				->where([
					$Sections->aliasField('institution_site_id') => $institutionId, 
					$Sections->aliasField('academic_period_id') => $selectedPeriod
				])
				->toArray();

			$selectedSection = $this->queryString('section_id', $sectionOptions);
			$this->advancedSelectOptions($sectionOptions, $selectedSection);
			$this->controller->set(compact('sectionOptions', 'selectedSection'));
			// End setup sections

			$settings['pagination'] = false;
			$query
				->contain(['Users'])
				->find('withAbsence', ['date' => $this->selectedDate])
				->where([$this->aliasField('institution_site_section_id') => $selectedSection]);

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

				$StudentAbsenceReasons = TableRegistry::get('FieldOption.StudentAbsenceReasons');
				$this->reasonOptions = $StudentAbsenceReasons->getList()->toArray();
			}
		} else {
			$settings['pagination'] = false;
			$query
				->where([$this->aliasField('student_id') => 0]);

			$this->ControllerAction->field('type');
			$this->ControllerAction->field('reason');

			$this->Alert->warning('StudentAttendances.noSections');
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$this->dataCount = $data->count();
	}

	public function findWithAbsence(Query $query, array $options) {
		$date = $options['date'];

		$conditions = ['StudentAbsences.security_user_id = StudentAttendances.student_id'];
		if (is_array($date)) {
			$startDate = $date[0]->format('Y-m-d');
			$endDate = $date[1]->format('Y-m-d');

			$conditions['OR'] = [
				'OR' => [
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date >=' => $startDate,
						'StudentAbsences.start_date <=' => $endDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date <=' => $startDate,
						'StudentAbsences.end_date >=' => $startDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date <=' => $endDate,
						'StudentAbsences.end_date >=' => $endDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date >=' => $startDate,
						'StudentAbsences.end_date <=' => $endDate
					]
				],
				[
					'StudentAbsences.end_date IS NULL',
					'StudentAbsences.start_date <=' => $endDate
				]
			];
		} else {
			$conditions['StudentAbsences.start_date <= '] = $date->format('Y-m-d');
			$conditions['StudentAbsences.end_date >= '] = $date->format('Y-m-d');
		}
    	return $query
    		->select([
    			$this->aliasField('student_id'), 
    			'Users.openemis_no', 'Users.first_name', 'Users.last_name',
    			'StudentAbsences.id',
    			'StudentAbsences.start_date',
    			'StudentAbsences.end_date',
    			'StudentAbsences.student_absence_reason_id'
    		])
			->join([
				[
					'table' => 'institution_site_student_absences',
					'alias' => 'StudentAbsences',
					'type' => 'LEFT',
					'conditions' => $conditions
				]
			])
			->order(['Users.openemis_no'])
			;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	if ($this->AccessControl->check(['Institutions', 'StudentAttendances', 'indexEdit'])) {
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

		// if ($action == 'index') {
		// 	$toolbarButtons['export']['url']['action'] = 'AttendanceExport';
		// }
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestQuery = $this->request->query;
			$requestData = $this->request->data;
			$StudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
			$alias = Inflector::underscore($StudentAbsences->alias());

			if (array_key_exists($StudentAbsences->Users->alias(), $requestData)) {
				if (array_key_exists($alias, $requestData[$StudentAbsences->Users->alias()])) {
					foreach ($requestData[$StudentAbsences->Users->alias()][$alias] as $key => $obj) {
						$obj['academic_period_id'] = $requestQuery['academic_period_id'];
						if ($obj['absence_type'] == 'UNEXCUSED') {
							$obj['student_absence_reason_id'] = 0;
						}

						if ($obj['absence_type'] == 'PRESENT') {
							if (isset($obj['id'])) {
								$StudentAbsences->deleteAll([
									$StudentAbsences->aliasField('id') => $obj['id']
								]);
							}
						} else if ($obj['absence_type'] == 'EXCUSED' || $obj['absence_type'] == 'UNEXCUSED') {
							$entity = $StudentAbsences->newEntity($obj);
							if ($StudentAbsences->save($entity)) {
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
