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

class StaffAbsencesTable extends AppTable {
	use OptionsTrait;
	private $_fieldOrder = [
		'absence_type_id', 'staff_id',
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
		$this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
		$this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
		$this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
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
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
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
			$value = sprintf('%s (%s - %s)', $startDate, $entity->start_time, $entity->end_time);
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
		unset($this->_fieldOrder[0]);// Academic period not in use in view page
		// $this->ControllerAction->setFieldOrder($this->_fieldOrder);
		
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
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('staff_id');
		$absenceTypeOptions = $this->absenceList;
		$this->ControllerAction->field('absence_type_id', [
			'options' => $this->absenceList
		]);
		$this->ControllerAction->field('full_day', [
			'options' => $fullDayOptions
		]);
		// Start Date and End Date
		if ($this->action == 'add') {
			if (!isset($this->request->data[$this->alias()]['academic_period_id'])) {
				$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
				$this->request->data[$this->alias()]['academic_period_id'] = $AcademicPeriodTable->getCurrent();
			}
			$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
			$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriod = $AcademicPeriodTable->get($academicPeriodId);

			$this->ControllerAction->field('start_date', ['startDate' => $academicPeriod->start_date, 'endDate' => $academicPeriod->end_date]);
			$this->ControllerAction->field('end_date', ['startDate' => $academicPeriod->start_date, 'endDate' => $academicPeriod->end_date]);

			list($periodOptions, $selectedPeriod, $staffOptions, $selectedStaff) = array_values($this->_getSelectOptions());
		
			$this->ControllerAction->field('academic_period_id', [
				'options' => $periodOptions
			]);
			$this->ControllerAction->field('staff_id', [
				'options' => $staffOptions
			]);

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
			$this->ControllerAction->field('start_date');
		}
		// End
		$this->ControllerAction->field('end_date');
		$this->ControllerAction->field('start_time', ['type' => 'time']);
		$this->ControllerAction->field('end_time', ['type' => 'time']);
		$this->ControllerAction->field('staff_absence_reason_id', ['type' => 'select']);
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add'){
			$startDate = $attr['startDate'];
			$endDate = $attr['endDate'];
			$attr['value'] = $startDate->format('d-m-Y');
			$attr['default_date'] = false;
			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
			
		}
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add'){
			$startDate = $attr['startDate'];
			$endDate = $attr['endDate'];
			$attr['value'] = $startDate->format('d-m-Y');
			$attr['default_date'] = false;
			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];	
		}
		if ($action == 'edit' || $action == 'add') {
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			if ($this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
				$attr['type'] = 'hidden';
			}
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
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
		}

		if ($action == 'edit' || $action == 'add') {
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			if ($this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
				$attr['type'] = 'hidden';
				$attr['attr']['value'] = 0;
				$this->fields['start_time']['visible'] = true;
				$this->fields['end_time']['visible'] = true;
				$request->data[$this->alias()]['full_day'] = 0;
			}
		}

		$attr['options'] = $fullDayOptions;
		$attr['onChangeReload'] = 'changeFullDay';

		return $attr;
	}

	public function onUpdateFieldAbsenceTypeId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit' || $action == 'add') {
			$absenceTypeOptions = $attr['options'];
			if (!isset($request->data[$this->alias()]['absence_type_id'])) {
				$request->data[$this->alias()]['absence_type_id'] = key($absenceTypeOptions);
			}
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			$attr['options'] = $absenceTypeOptions;
			$attr['default'] = $selectedAbsenceType;
			$attr['onChangeReload'] = 'changeAbsenceType';
		}
		return $attr;
	}

	public function onUpdateFieldStaffAbsenceReasonId(Event $event, array $attr, $action, $request) {
		$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
		if (!empty($selectedAbsenceType)) {
			$absenceType = $this->absenceCodeList[$selectedAbsenceType];
			if ($absenceType == 'UNEXCUSED' || $absenceType == 'LATE') {
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

	public function _getSelectOptions() {
		//Return all required options and their key
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Staff = TableRegistry::get('Institution.Staff');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList(['isEditable'=>true]);

		$selectedPeriod = $this->request->data[$this->alias()]['academic_period_id'];
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStaff')),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->find()
					->where([$Staff->aliasField('institution_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End

		$this->request->data[$this->alias()]['academic_period_id'] = $selectedPeriod;

		// Staff
		$staffOptions = $Staff
			->find()
			->where([$Staff->aliasField('institution_id') => $institutionId])
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->contain(['Users'])
			->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
			->toArray();
		$selectedStaff = !is_null($this->request->query('staff')) ? $this->request->query('staff') : key($staffOptions);
		// End

		return compact('periodOptions', 'selectedPeriod', 'staffOptions', 'selectedStaff');
	}
}
