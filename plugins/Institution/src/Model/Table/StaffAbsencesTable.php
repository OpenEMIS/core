<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;

class StaffAbsencesTable extends AppTable {
	use OptionsTrait;
	private $_fieldOrder = [
		'absence_type_id', 'academic_period_id', 'staff_id',
		'full_day', 'start_date', 'end_date', 'start_time', 'end_time',
		'staff_absence_reason_id'
	];
	private $absenceList;
	private $absenceCodeList;

	public function initialize(array $config) {
		$this->table('institution_staff_absences');
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
		$this->belongsTo('StaffAbsenceReasons', ['className' => 'FieldOption.StaffAbsenceReasons']);
		$this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Excel', [
			'excludes' => [
				'start_year',
				'end_year',
				'institution_id',
				'staff_id',
				'full_day',
				'start_date',
				'start_time',
				'end_time',
				'end_date'
			],
			'pages' => ['index']
		]);

		$this->absenceList = $this->AbsenceTypes->getAbsenceTypeList();
		$this->absenceCodeList = $this->AbsenceTypes->getCodeList();
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
		$this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
		$this->setValidationCode('end_date.ruleInAcademicPeriod', 'Institution.Absences');
		$this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
		$codeList = array_flip($this->absenceCodeList);
		$validator
			->add('start_date', [
				'ruleNoOverlappingAbsenceDate' => [
					'rule' => ['noOverlappingAbsenceDate', $this]
				],
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id'],
					'on' => 'create'
				]
			])
			->add('end_date', [
				'ruleCompareDateReverse' => [
					'rule' => ['compareDateReverse', 'start_date', true]
				],
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id'],
					'on' => 'create'
				]
			])
			->requirePresence('start_time', function ($context) {
			    if (array_key_exists('full_day', $context['data'])) {
			        return !$context['data']['full_day'];
			    }
			    return false;
			})
			->add('start_time', [
				'ruleInInstitutionShift' => [
					'rule' => ['inInstitutionShift', 'academic_period_id'],
					'on' => 'create'
				]
			])
			->requirePresence('end_time', function ($context) {
			    if (array_key_exists('full_day', $context['data'])) {
			        return !$context['data']['full_day'];
			    }
			    return false;
			})
			->add('end_time', [
				'ruleCompareAbsenceTimeReverse' => [
					'rule' => ['compareAbsenceTimeReverse', 'start_time', true]
				],
				'ruleInInstitutionShift' => [
					'rule' => ['inInstitutionShift', 'academic_period_id'],
					'on' => 'create'
				]
			])
			;
		return $validator;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query
			->where([$this->aliasField('institution_id') => $institutionId])
			->select(['openemis_no' => 'Users.openemis_no']);
	}

	// To select another one more field from the containable data
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$newArray = [];
		$newArray[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'StaffAbsences.staff_id',
			'field' => 'staff_id',
			'type' => 'integer',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'StaffAbsences.absences',
			'field' => 'absences',
			'type' => 'string',
			'label' => __('Absences')
		];
		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}

	public function onExcelGetStaffAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->staff_absence_reason_id == 0) {
			return __('Unexcused');
		}
	}

	public function onExcelGetAbsences(Event $event, Entity $entity) {
		$startDate = "";
		$endDate = "";

		if (!empty($entity->start_date)) {
			$startDate = $this->formatDate($entity->start_date);
		} else {
			$startDate = $entity->start_date;
		}

		if (!empty($entity->end_date)) {
			$endDate = $this->formatDate($entity->end_date);
		} else {
			$endDate = $entity->end_date;
		}

		if ($entity->full_day) {
			return sprintf('%s %s (%s - %s)', __('Full'), __('Day'), $startDate, $endDate);
		} else {
			$startTime = $entity->start_time;
			$endTime = $entity->end_time;
			return sprintf('%s (%s - %s) %s (%s - %s)', __('Non Full Day'), $startDate, $endDate, __('Time'), $startTime, $endTime);
		}
	}

	public function onGetDate(Event $event, Entity $entity) {
		$startDate = $this->formatDate($entity->start_date);
		$endDate = $this->formatDate($entity->end_date);
		if ($entity->full_day == 1) {
			if (!empty($entity->end_date) && $entity->end_date > $entity->start_date) {
				$value = sprintf('%s - %s (%s)', $startDate, $endDate, __('full day'));
			} else {
				$value = sprintf('%s (%s)', $startDate, __('full day'));
			}
		} else {
			if ($this->absenceCodeList[$entity->absence_type_id] == 'LATE') {
				$endTime = $entity->end_time;
				$startTime = $entity->start_time;
				$secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
				$minutesLate = $secondsLate / 60;
				$hoursLate = floor($minutesLate / 60);
				if ($hoursLate > 0) {
					$minutesLate = $minutesLate - ($hoursLate * 60);
					$lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
				} else {
					$lateString = $minutesLate.' '.__('Minute');
				}
				$value = sprintf('%s (%s)', $startDate, $lateString);
			} else {
				$value = sprintf('%s (%s - %s)', $startDate, $this->formatTime($entity->start_time), $this->formatTime($entity->end_time));
			}
		}

		return $value;
	}

	public function onGetStaffId(Event $event, Entity $entity) {
		if (isset($entity->user->name_with_id)) {
			if ($this->action == 'view') {
				return $event->subject()->Html->link($entity->user->name_with_id , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StaffUser',
					'view',
					$entity->user->id
				]);
			} else {
				return $entity->user->name_with_id;
			}
		}
	}

	public function onGetFullday(Event $event, Entity $entity) {
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		return $fullDayOptions[$entity->full_day];
	}

	public function onGetAbsenceTypeId(Event $event, Entity $entity) {
		return __($entity->absence_type->name);
	}

	public function onGetStaffAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->staff_absence_reason_id == 0) {
			return '<i class="fa fa-minus"></i>';
		}
	}

	public function addEditBeforePatch(Event $event, $entity, $requestData, $patchOptions) {
		$absenceTypeId = $requestData[$this->alias()]['absence_type_id'];
		if ($this->absenceCodeList[$absenceTypeId] == 'LATE') {
			$requestData[$this->alias()]['end_date'] = $requestData[$this->alias()]['start_date'];
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['staff'] = $entity->staff_id;
		$this->request->query['full_day'] = $entity->full_day;
		$this->request->data[$this->alias()]['full_day'] = $entity->full_day;
		$this->request->data[$this->alias()]['absence_type_id'] = $entity->absence_type_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
	}

	public function beforeAction(Event $event) {
		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffAttendances'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffAbsences'],
				'text' => __('Absence')
			]
		];
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absence');
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('date');
		$this->ControllerAction->field('absence_type_id', [
			'options' => $this->absenceList
		]);

		$this->fields['full_day']['visible'] = false;
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['start_time']['visible'] = false;
		$this->fields['end_time']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$this->_fieldOrder = ['date', 'staff_id', 'absence_type_id', 'staff_absence_reason_id'];
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// Academic period not in use in view page
		foreach ($this->_fieldOrder as $key => $value) {
			if ($value == 'academic_period_id') {
				unset($this->_fieldOrder[$key]);
			}
		}
		// pr($this->_fieldOrder);
		// unset($this->_fieldOrder[0]);
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);


		$absenceTypeOptions = $this->absenceList;
		$this->ControllerAction->field('absence_type_id', [
			'options' => $this->absenceList
		]);

		if ($entity->full_day == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		list($periodOptions, $selectedPeriod, $newPeriodOptions) = array_values($this->_getSelectOptions());
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		$this->ControllerAction->field('staff_id');
		$absenceTypeOptions = $this->absenceList;
		$this->ControllerAction->field('absence_type_id', [
			'options' => $this->absenceList
		]);
		$this->ControllerAction->field('academic_period_id', [
			'options' => $newPeriodOptions
		]);
		$this->ControllerAction->field('full_day', [
			'options' => $fullDayOptions
		]);
		// Start Date and End Date
		if ($this->action == 'add') {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
			$endDate = $AcademicPeriod->get($selectedPeriod)->end_date;


			$StaffTable = TableRegistry::get('Institution.Staff');
			$staffRecord = $StaffTable->find()->where([
					$StaffTable->aliasField('staff_id') => $this->request->data[$this->alias()]['staff_id'],
					$StaffTable->aliasField('end_date').' IS NULL'
				])
				->first();

			if (empty($staffRecord)) {
				$staffRecord = $StaffTable->find()
					->where([
						$StaffTable->aliasField('staff_id') => $this->request->data[$this->alias()]['staff_id'],
					])
					->order([$StaffTable->aliasField('end_date')])
					->first();
			}
			// $dateAttr = ['startDate' => Time::now(), 'endDate' => Time::now()];
			// if (!empty($staffRecord)) {
			// 	$dateAttr['startDate'] = $staffRecord->start_date;
			// 	$dateAttr['endDate'] = $staffRecord->end_date;
			// }

			// $this->ControllerAction->field('start_date', $dateAttr);
			// $this->ControllerAction->field('end_date', $dateAttr);

			$this->fields['start_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
			$this->fields['start_date']['date_options']['endDate'] = $endDate->format('d-m-Y');
			$this->fields['end_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
			$this->fields['end_date']['date_options']['endDate'] = $endDate->format('d-m-Y');

			// Malcolm discussed with Umairah and Thed - will revisit this when default date of htmlhelper is capable of setting 'defaultViewDate' ($entity->start_date = $todayDate; was: causing validation error to disappear)
			// $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			// $startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
			// $endDate = $AcademicPeriod->get($selectedPeriod)->end_date;

			// $this->field('start_date', [
			// 	'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
			// ]);
			// $this->field('end_date', [
			// 	'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
			// ]);

			// $todayDate = date("Y-m-d");
			// if ($todayDate >= $startDate->format('Y-m-d') && $todayDate <= $endDate->format('Y-m-d')) {
			// 	$entity->start_date = $todayDate;
			// 	$entity->end_date = $todayDate;
			// } else {
			// 	$entity->start_date = $startDate->format('Y-m-d');
			// 	$entity->end_date = $startDate->format('Y-m-d');
			// }


		} else if ($this->action == 'edit') {
			$this->ControllerAction->field('start_date', ['value' => date('Y-m-d', strtotime($entity->start_date))]);
			$this->ControllerAction->field('end_date', ['value' => date('Y-m-d', strtotime($entity->end_date))]);
		}
		// End
		$this->ControllerAction->field('start_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->start_time))]]);
		$this->ControllerAction->field('end_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->end_time))]]);
		$this->ControllerAction->field('staff_absence_reason_id', ['type' => 'select']);
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add'){
			$startDate = $attr['startDate'];
			$endDate = $attr['endDate'];
			$attr['default_date'] = Time::now()->format('d-m-Y');
			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y')];
			if (!empty($endDate)) {
				$attr['date_options']['endDate'] = $endDate->format('d-m-Y');
			}
		}

		if ($action == 'edit') {
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
	{
		if ($action == 'add') {
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
				$attr['type'] = 'hidden';
			}
		}

		if ($action == 'edit') {
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
		}

		return $attr;
	}

	public function onUpdateFieldStartTime(Event $event, array $attr, $action, $request)
	{
		if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function onUpdateFieldEndTime(Event $event, array $attr, $action, $request)
	{
		if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		$attr['select'] = false;
		$attr['default'] = $request->query['period'];

		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$Staff = TableRegistry::get('Institution.Staff');
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$periodOptionsData = $AcademicPeriodTable->getList(['isEditable'=>true]);
			$periodOptions = $periodOptionsData[key($periodOptionsData)];
			$selectedPeriod = $this->queryString('period', $periodOptions);
			$startDate = $AcademicPeriodTable->get($selectedPeriod)->start_date;
			$endDate = $AcademicPeriodTable->get($selectedPeriod)->end_date;
			// $todayDate = Time::now();
			$activeStaffOptions = $Staff
				->find()
				->where([$Staff->aliasField('institution_id') => $institutionId])
				->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate])
				->contain(['Users'])
				->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name']);

			$activeStaffOptionsClone = clone $activeStaffOptions;
			$inactiveStaffOptions = $Staff->find()
				->where([$Staff->aliasField('institution_id').' = '.$institutionId])
				->where([$Staff->aliasField('id').' NOT IN ' => $activeStaffOptionsClone->select(['id'])])
				->contain(['Users'])
				->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
				->toArray();

			$activeStaffOptions = $activeStaffOptions->toArray();
			$newActiveStaffOptions = [];
			foreach ($activeStaffOptions as $key => $value) {
				$newActiveStaffOptions[$key] = [
					'value' => $key,
					'text' => $value
				];
			}

			$newInactiveStaffOptions = [];
			foreach ($inactiveStaffOptions as $key => $value) {
				$newInactiveStaffOptions[$key] = [
					'value' => $key,
					'text' => $value,
					'disabled'
				];
			}

			$staffOptions = [__('Active Staff') => $newActiveStaffOptions, __('Inactive Staff') => $newInactiveStaffOptions];
			$attr['options'] = $staffOptions;

			if (!isset($request->data[$this->alias()]['staff_id'])) {
				$optionList = $activeStaffOptions + $inactiveStaffOptions;
				$request->data[$this->alias()]['staff_id'] = key($optionList);
			}
			$attr['onChangeReload'] = true;
		} else if ($action == 'edit') {
			$Users = TableRegistry::get('User.Users');
			$selectedStaff = $request->query('staff');

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $Users->get($selectedStaff)->name_with_id;
		}

		return $attr;
	}

	public function onUpdateFieldFullDay(Event $event, array $attr, $action, $request) {
		$fullDayOptions = $attr['options'];
		$selectedFullDay = isset($request->data[$this->alias()]['full_day']) ? $request->data[$this->alias()]['full_day'] : 1;
		$this->advancedSelectOptions($fullDayOptions, $selectedFullDay);

		if ($selectedFullDay == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		} else {
			$this->fields['start_time']['visible'] = true;
			$this->fields['end_time']['visible'] = true;

			// to on the mandatory field asterick, using timepicker_input.ctp
			// timepicker_input.ctp, have the form helper error message, turn off the form helper error message.
			$this->fields['start_time']['null'] = false;
			$this->fields['end_time']['null'] = false;
		}

		if ($action == 'add') {
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
				$attr['type'] = 'hidden';
				$attr['attr']['value'] = 0;
				$this->fields['start_time']['visible'] = true;
				$this->fields['end_time']['visible'] = true;
				$request->data[$this->alias()]['full_day'] = 0;
			}
		}

		if ($action == 'edit') {
			$attr['type'] = 'readonly';
			if ($this->request->query['full_day']) {
				$attr['attr']['value'] = 'Yes';
			} else {
				$attr['attr']['value'] = 'No';
			}
		}

		$attr['select'] = false;
		$attr['options'] = $fullDayOptions;
		$attr['onChangeReload'] = 'changeFullDay';

		return $attr;
	}

	public function onUpdateFieldAbsenceTypeId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			foreach ($attr['options'] as $key => $value) {
				$absenceTypeOptions[$key] = __($value);
			}
			if (!isset($request->data[$this->alias()]['absence_type_id'])) {
				$request->data[$this->alias()]['absence_type_id'] = key($absenceTypeOptions);
			}
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];

			$attr['options'] = $absenceTypeOptions;
			$attr['default'] = $selectedAbsenceType;
			$attr['onChangeReload'] = 'changeAbsenceType';
		}

		if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}

		return $attr;
	}

	public function onUpdateFieldStaffAbsenceReasonId(Event $event, array $attr, $action, $request) {
		$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
		if (!empty($selectedAbsenceType)) {
			$absenceType = $this->absenceCodeList[$selectedAbsenceType];
			if ($absenceType == 'UNEXCUSED') {
				$attr['type'] = 'hidden';
				$attr['attr']['value'] = 0;
			}
		}

		return $attr;
	}

	public function addEditOnChangeFullDay(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

		$request = $this->request;
		unset($request->query['full_day']);
		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('full_day', $request->data[$this->alias()])) {
					$request->query['full_day'] = $request->data[$this->alias()]['full_day'];
				}
			}
		}
	}

	public function addEditOnChangeAbsenceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['absence_type']);
		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('absence_type', $request->data[$this->alias()])) {
					$request->query['absence_type'] = $request->data[$this->alias()]['absence_type'];
				}
			}
		}
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		// unset($request->query['class']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Staffs = TableRegistry::get('Institution.Staff');

		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptionsData = $AcademicPeriod->getList(['isEditable'=>true]);
		$periodOptions = $periodOptionsData[key($periodOptionsData)];
		$selectedPeriod = $this->queryString('period', $periodOptions);


		// count staff on the academic period, if its empty the period will be disabled.
		$newPeriodOptions = [];
		foreach ($periodOptions as $key => $value) {
			$startDate = $AcademicPeriod->get($key)->start_date;
			$endDate = $AcademicPeriod->get($key)->end_date;

			$activeStaff = $Staffs
				->find()
				->where([$Staffs->aliasField('institution_id') => $institutionId])
				->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate])
				->count();

			$newPeriodOptions[$key] = [
				'value' => $key,
				'text' => $value
			];

			if ($activeStaff == 0) {
				$newPeriodOptions[$key] = [
					'value' => $key,
					'text' => $value,
					'disabled'
				];
			}
		}
		return compact('periodOptions', 'selectedPeriod', 'newPeriodOptions');
	}
}
