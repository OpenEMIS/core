<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionSiteStudentAbsencesTable extends AppTable {
	use OptionsTrait;
	private $_fieldOrder = [
		'academic_period', 'section', 'security_user_id',
		'full_day', 'start_date', 'end_date', 'start_time', 'end_time',
		'absence_type', 'student_absence_reason_id'
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
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
		if (isset($entity->user->name_with_id)) {
			return $entity->user->name_with_id;
		}
	}

	public function onGetFullday(Event $event, Entity $entity) {
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		return $fullDayOptions[$entity->full_day];
	}

	public function onGetAbsenceType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Absence.types');
		return $entity->student_absence_reason_id == 0 ? $types['UNEXCUSED'] : $types['EXCUSED'];
	}

	public function onGetStudentAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->student_absence_reason_id == 0) {
			return '<i class="fa fa-minus"></i>';
		}
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
		$absenceTypeOptions = $this->getSelectOptions('Absence.types');

		$this->ControllerAction->field('date');
		$this->ControllerAction->field('absence_type', [
			'options' => $absenceTypeOptions
		]);

		$this->fields['full_day']['visible'] = false;
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['start_time']['visible'] = false;
		$this->fields['end_time']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$this->_fieldOrder = ['date', 'security_user_id', 'absence_type', 'student_absence_reason_id'];
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['student'] = $entity->security_user_id;
		$this->request->query['full_day'] = $entity->full_day;
		$this->request->query['absence_type'] = $entity->student_absence_reason_id == 0 ? 'UNEXCUSED' : 'EXCUSED';
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// Temporary fix for error on view page
		unset($this->_fieldOrder[0]);
		unset($this->_fieldOrder[1]);
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
		// End fix

		$absenceTypeOptions = $this->getSelectOptions('Absence.types');
		$this->ControllerAction->field('absence_type', [
			'options' => $absenceTypeOptions
		]);

		if ($entity->full_day == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		list($periodOptions, $selectedPeriod, $sectionOptions, $selectedSection, $studentOptions, $selectedStudent) = array_values($this->_getSelectOptions());
		$fullDayOptions = $this->getSelectOptions('general.yesno');
		$absenceTypeOptions = $this->getSelectOptions('Absence.types');

		$this->ControllerAction->field('academic_period', [
			'options' => $periodOptions
		]);
		$this->ControllerAction->field('section', [
			'options' => $sectionOptions
		]);
		$this->ControllerAction->field('security_user_id', [
			'options' => $studentOptions
		]);
		$this->ControllerAction->field('full_day', [
			'options' => $fullDayOptions
		]);
		// Start Date and End Date
		if ($this->action == 'add') {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
			$endDate = $AcademicPeriod->get($selectedPeriod)->end_date;

			$this->ControllerAction->field('start_date', [
				'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
			]);
			$this->ControllerAction->field('end_date', [
				'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
			]);

			$todayDate = date("Y-m-d");
			if ($todayDate >= $startDate->format('Y-m-d') && $todayDate <= $endDate->format('Y-m-d')) {
				$entity->start_date = $todayDate;
				$entity->end_date = $todayDate;
			} else {
				$entity->start_date = $startDate->format('Y-m-d');
				$entity->end_date = $startDate->format('Y-m-d');
			}
		} else if ($this->action == 'edit') {
			$this->ControllerAction->field('start_date');
			$this->ControllerAction->field('end_date');
		}
		// End
		$this->ControllerAction->field('start_time', ['type' => 'time']);
		$this->ControllerAction->field('end_time', ['type' => 'time']);
		$this->ControllerAction->field('absence_type', [
			'options' => $absenceTypeOptions
		]);
		$this->ControllerAction->field('student_absence_reason_id', ['type' => 'select']);
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changeSection';
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
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
		$selectedFullDay = !is_null($request->query('full_day')) ? $request->query('full_day') : key($fullDayOptions);
		$this->advancedSelectOptions($fullDayOptions, $selectedFullDay);

		if ($selectedFullDay == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		} else {
			$this->fields['start_time']['visible'] = true;
			$this->fields['end_time']['visible'] = true;
		}

		$attr['options'] = $fullDayOptions;
		$attr['onChangeReload'] = 'changeFullDay';

		return $attr;
	}

	public function onUpdateFieldAbsenceType(Event $event, array $attr, $action, $request) {
		$absenceTypeOptions = $attr['options'];
		$selectedAbsenceType = !is_null($request->query('absence_type')) ? $request->query('absence_type') : key($absenceTypeOptions);

		$attr['options'] = $absenceTypeOptions;
		$attr['default'] = $selectedAbsenceType;
		$attr['onChangeReload'] = 'changeAbsenceType';

		return $attr;
	}

	public function onUpdateFieldStudentAbsenceReasonId(Event $event, array $attr, $action, $request) {
		$absenceTypeOptions = $this->fields['absence_type']['options'];
		$selectedAbsenceType = !is_null($request->query('absence_type')) ? $request->query('absence_type') : key($absenceTypeOptions);

		if ($selectedAbsenceType == 'UNEXCUSED') {
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = 0;
		}

		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['section']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period'];
				}
			}
		}
	}

	public function addEditOnChangeSection(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['section']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period'];
				}
				if (array_key_exists('section', $request->data[$this->alias()])) {
					$request->query['section'] = $request->data[$this->alias()]['section'];
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
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList();
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections
					->find()
					->where([
						$Sections->aliasField('institution_site_id') => $institutionId,
						$Sections->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);
		// End

		// Section
		$userId = $this->Auth->user('id');
		$AccessControl = $this->AccessControl;
		$sectionOptions = $Sections
			->find('list')
			->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]) // restrict user to see own class if permission is set
			->where([
				$Sections->aliasField('institution_site_id') => $institutionId,
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();
		$selectedSection = !is_null($this->request->query('section')) ? $this->request->query('section') : key($sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $selectedSection, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($Students) {
				return $Students
					->find()
					->where([
						$Students->aliasField('institution_site_section_id') => $id
					])
					->count();
			}
		]);
		// End
		
		// Student
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$studentOptions = $Students
			->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
			->where([
				$Students->aliasField('institution_site_section_id') => $selectedSection
			])
			->contain(['Users'])
			->toArray();
		$selectedStudent = !is_null($this->request->query('student')) ? $this->request->query('student') : key($studentOptions);
		// End

		return compact('periodOptions', 'selectedPeriod', 'sectionOptions', 'selectedSection', 'studentOptions', 'selectedStudent');
	}
}
