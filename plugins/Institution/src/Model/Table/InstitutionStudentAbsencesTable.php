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

class InstitutionStudentAbsencesTable extends AppTable {
	use OptionsTrait;
	private $_fieldOrder = [
		'absence_type_id', 'academic_period_id', 'class', 'student_id',
		'full_day', 'start_date', 'end_date', 'start_time', 'end_time',
		'student_absence_reason_id'
	];
	private $absenceList;
	private $absenceCodeList;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
		$this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Excel', [
			'excludes' => [
				'start_year',
				'end_year',
				'institution_id',
				'student_id',
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
			'key' => 'InstitutionStudentAbsences.student_id',
			'field' => 'student_id',
			'type' => 'integer',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'InstitutionStudentAbsences.absences',
			'field' => 'absences',
			'type' => 'string',
			'label' => __('Absences')
		];
		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}

	public function validationDefault(Validator $validator) {
		$this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
		$this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
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
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			])
			->add('end_time', 'ruleCompareAbsenceTimeReverse', [
				'rule' => ['compareAbsenceTimeReverse', 'start_time', $codeList['LATE']]
			])
			;
		return $validator;
	}

	public function onExcelGetStudentAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->student_absence_reason_id == 0) {
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

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		if (isset($entity->user->name_with_id)) {
			return $entity->user->name_with_id;
		}
	}

	public function onGetFullday(Event $event, Entity $entity) {
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		return $fullDayOptions[$entity->full_day];
	}

	public function onGetAbsenceTypeId(Event $event, Entity $entity) {
		return __($entity->absence_type->name);
	}

	public function onGetStudentAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->student_absence_reason_id == 0) {
			return '<i class="fa fa-minus"></i>';
		}
	}

	public function onGetStudentId(Event $event, Entity $entity) {
		if (isset($entity->user->name_with_id)) {
			if ($this->action == 'view') {
				return $event->subject()->Html->link($entity->user->name_with_id , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StudentUser',
					'view',
					$entity->user->id
				]);
			} else {
				return $entity->user->name_with_id;
			}
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['student'] = $entity->student_id;
		$this->request->query['full_day'] = $entity->full_day;
		$this->request->data[$this->alias()]['full_day'] = $entity->full_day;
		$this->request->data[$this->alias()]['absence_type_id'] = $entity->absence_type_id;
	}

	public function beforeAction(Event $event) {
		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentAttendances'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentAbsences'],
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
		$absenceTypeOptions = $this->absenceList;

		$this->ControllerAction->field('date');
		$this->ControllerAction->field('absence_type_id', [
			'options' => $absenceTypeOptions
		]);

		$this->fields['full_day']['visible'] = false;
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['start_time']['visible'] = false;
		$this->fields['end_time']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$this->_fieldOrder = ['date', 'student_id', 'absence_type_id', 'student_absence_reason_id'];
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// Temporary fix for error on view page
		unset($this->_fieldOrder[1]); // Academic period not in use in view page
		unset($this->_fieldOrder[2]); // Class not in use in view page
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
		// End fix

		$absenceTypeOptions = $this->absenceList;
		$this->ControllerAction->field('absence_type_id', [
			'options' => $absenceTypeOptions
		]);

		if ($entity->full_day == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		}
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$StudentTable = TableRegistry::get('Institution.Students');
		$studentId = $entity->student_id;
		$institutionId = $entity->institution_id;
		if (!is_null($studentId)) {
			if(! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
				$process = function ($model, $entity) {
					return false;
				};
				$this->Alert->error('InstitutionStudentAbsences.notEnrolled');
				return $process;
			}
		}
	}

	public function addEditBeforePatch(Event $event, $entity, $requestData, $patchOptions) {
		$absenceTypeId = $requestData[$this->alias()]['absence_type_id'];
		if ($this->absenceCodeList[$absenceTypeId] == 'LATE') {
			$requestData[$this->alias()]['end_date'] = $requestData[$this->alias()]['start_date'];
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		list($periodOptions, $selectedPeriod, $classOptions, $selectedClass, $studentOptions, $selectedStudent) = array_values($this->_getSelectOptions());
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		$absenceTypeOptions = $this->absenceList;
		$this->ControllerAction->field('absence_type_id', [
			'options' => $absenceTypeOptions
		]);
		$this->ControllerAction->field('academic_period_id', [
			'options' => $periodOptions
		]);
		$this->ControllerAction->field('class', [
			'options' => $classOptions
		]);
		$this->ControllerAction->field('student_id', [
			'options' => $studentOptions
		]);
		$this->ControllerAction->field('full_day', [
			'options' => $fullDayOptions
		]);
		// Start Date and End Date
		if ($this->action == 'add') {
			// Malcolm discussed with Umairah and Thed - will revisit this when default date of htmlhelper is capable of setting 'defaultViewDate' ($entity->start_date = $todayDate; was: causing validation error to disappear)
			// $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			// $startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
			// $endDate = $AcademicPeriod->get($selectedPeriod)->end_date;

			// $this->ControllerAction->field('start_date', [
			// 	'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
			// ]);
			// $this->ControllerAction->field('end_date', [
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
		$this->ControllerAction->field('student_absence_reason_id', ['type' => 'select']);
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldClass(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changeClass';
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit' || $action == 'add') {
			$selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
			if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
				$attr['type'] = 'hidden';
			}
		}
		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$Users = TableRegistry::get('User.Users');
			$selectedStudent = $request->query('student');

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $Users->get($selectedStudent)->name_with_id;
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
			if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
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
		if ($action == 'add' || $action == 'edit') {
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
		return $attr;
	}

	public function onUpdateFieldStudentAbsenceReasonId(Event $event, array $attr, $action, $request) {
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

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['class']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function addEditOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['class']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
				if (array_key_exists('class', $request->data[$this->alias()])) {
					$request->query['class'] = $request->data[$this->alias()]['class'];
				}
			}
		}
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
		$Classes = TableRegistry::get('Institution.InstitutionClasses');
		$Students = TableRegistry::get('Institution.InstitutionClassStudents');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList(['isEditable'=>true]);
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes
					->find()
					->where([
						$Classes->aliasField('institution_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);
		// End

		// Class
		$userId = $this->Auth->user('id');
		$AccessControl = $this->AccessControl;
		$classOptions = $Classes
			->find('list')
			->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller]) // restrict user to see own class if permission is set
			->where([
				$Classes->aliasField('institution_id') => $institutionId,
				$Classes->aliasField('academic_period_id') => $selectedPeriod
			])
			->order([$Classes->aliasField('class_number') => 'ASC'])
			->toArray();
		$selectedClass = !is_null($this->request->query('class')) ? $this->request->query('class') : key($classOptions);
		$this->advancedSelectOptions($classOptions, $selectedClass, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($Students) {
				return $Students
					->find()
					->where([
						$Students->aliasField('institution_class_id') => $id
					])
					->count();
			}
		]);
		// End
		
		// Student
		$Students = TableRegistry::get('Institution.InstitutionClassStudents');
		$studentOptions = $Students
			->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
			->where([
				$Students->aliasField('institution_class_id') => $selectedClass
			])
			->contain(['Users'])
			->toArray();
		$selectedStudent = !is_null($this->request->query('student')) ? $this->request->query('student') : key($studentOptions);
		// End

		return compact('periodOptions', 'selectedPeriod', 'classOptions', 'selectedClass', 'studentOptions', 'selectedStudent');
	}
}
