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
		return $entity->user->name_with_id;
	}

	public function onGetFullday(Event $event, Entity $entity) {
		$fulldayOptions = $this->getSelectOptions('general.yesno');
		return $fulldayOptions[$entity->full_day];
	}

	public function onGetAbsenceType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Absence.types');
		return $entity->student_absence_reason_id == 0 ? $types['UNEXCUSED'] : $types['EXCUSED'];
	}

	public function onGetStudentAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->student_absence_reason_id == 0) {
			return '-';
		}
	}

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
        $this->controller->set('selectedAction', 'Absence'); 
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('date');
		$this->ControllerAction->field('absence_type');

		$this->fields['full_day']['visible'] = false;
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['start_time']['visible'] = false;
		$this->fields['end_time']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$this->_fieldOrder = ['date', 'security_user_id', 'absence_type', 'student_absence_reason_id'];
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		if ($entity->full_day == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['student'] = $entity->security_user_id;
		$this->request->query['fullday'] = $entity->full_day;
		$this->request->query['absence_type'] = $entity->student_absence_reason_id == 0 ? 'UNEXCUSED' : 'EXCUSED';
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('section');
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('full_day');
		$this->ControllerAction->field('start_time', ['type' => 'time']);
		$this->ControllerAction->field('end_time', ['type' => 'time']);
		$this->ControllerAction->field('absence_type');
		$this->ControllerAction->field('student_absence_reason_id', ['type' => 'select']);
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');

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

		if ($request->is(['post', 'put'])) {
			$selectedPeriod = $this->request->data($this->aliasField('academic_period'));
		}
		$request->query['period'] = $selectedPeriod;

		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->request->query('period');

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$sectionOptions = $Sections
			->find('list')
			->where([
				$Sections->aliasField('institution_site_id') => $institutionId,
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();
		$selectedSection = $this->queryString('section', $sectionOptions);
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

		if ($request->is(['post', 'put'])) {
			$selectedSection = $this->request->data($this->aliasField('section'));
		}
		$request->query['section'] = $selectedSection;

		$attr['options'] = $sectionOptions;
		$attr['onChangeReload'] = true;
		if ($action != 'add') {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$students = [];
			$selectedSection = $this->request->query('section');

			$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			if (!is_null($selectedSection)) {
				$students = $Students
					->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
					->where([
						$Students->aliasField('institution_site_section_id') => $selectedSection
					])
					->contain(['Users'])
					->toArray();
			}

			$attr['options'] = $students;
		} else if ($action == 'edit') {
			$Users = TableRegistry::get('User.Users');
			$selectedStudent = $this->request->query('student');

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $Users->get($selectedStudent)->name_with_id;
		}

		return $attr;
	}

	public function onUpdateFieldFullDay(Event $event, array $attr, $action, $request) {
		$fulldayOptions = $this->getSelectOptions('general.yesno');
		$selectedFullday = $this->queryString('fullday', $fulldayOptions);

		if ($request->is(['post', 'put'])) {
			$selectedFullday = $this->request->data($this->aliasField('full_day'));
		}

		if ($selectedFullday == 1) {
			$this->fields['start_time']['visible'] = false;
			$this->fields['end_time']['visible'] = false;
		} else {
			$this->fields['start_time']['visible'] = true;
			$this->fields['end_time']['visible'] = true;
		}

		$attr['options'] = $fulldayOptions;
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldAbsenceType(Event $event, array $attr, $action, $request) {
		$absenceTypeOptions = $this->getSelectOptions('Absence.types');
		$selectedAbsenceType = $this->queryString('absence_type', $absenceTypeOptions);

		if ($request->is(['post', 'put'])) {
			$selectedAbsenceType = $this->request->data($this->aliasField('absence_type'));
		}
		$request->query['absence_type'] = $selectedAbsenceType;

		$attr['options'] = $absenceTypeOptions;
		$attr['default'] = $selectedAbsenceType;
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldStudentAbsenceReasonId(Event $event, array $attr, $action, $request) {
		$selectedAbsenceType = $this->request->query('absence_type');

		if ($selectedAbsenceType == 'UNEXCUSED') {
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = 0;
		}

		return $attr;
	}
}
