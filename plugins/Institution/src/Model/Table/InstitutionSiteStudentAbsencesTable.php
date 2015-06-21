<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionSiteStudentAbsencesTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		// $this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
		return $validator;
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Absence.types');
		return $entity->student_absence_reason_id == 0 ? $types['UNEXCUSED'] : $types['EXCUSED'];
	}

	public function onGetStudentAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->student_absence_reason_id == 0) {
			return '-';
		}
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		return $entity->user->name_with_id;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('section');
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('start_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('end_time', ['type' => 'time', 'visible' => false]);
		$this->ControllerAction->field('full_day');
		$this->ControllerAction->field('student_absence_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('type');
		$this->ControllerAction->setFieldOrder([
			'academic_period', 'security_user_id', 'full_day', 'start_date', 'end_date', 
			'start_time', 'end_time', 'type', 'student_absence_reason_id'
		]);

		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendance'],
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

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$periodId = key($this->fields['academic_period']['options']);

		if ($request->is('post')) {
			$periodId = $this->request->data($this->aliasField('academic_period'));
		}

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$sectionOptions = $Sections
			->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)
			->find('list')
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();

		$attr['options'] = $sectionOptions;
		$attr['onChangeReload'] = 'changeSection';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$students = [];

			$sectionId = key($this->fields['section']['options']);
			if ($request->is('post')) {
				if (isset($request->data[$this->alias()]['section'])) {
					$sectionId = $request->data[$this->alias()]['section'];
				}
				if (!empty($sectionId)) {
					$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
					$students = $Students
						->findAllByInstitutionSiteSectionId($sectionId)
						->contain(['Users'])
						->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
						->toArray();
				}
			}
			
			$attr['options'] = $students;
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
				$this->fields['student_absence_reason_id']['type'] = 'hidden';
				$this->fields['student_absence_reason_id']['value'] = 0;
			}
		}
		return $attr;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->fields['security_user_id']['type'] = 'readonly';
		if ($entity->student_absence_reason_id == 0) {
			$this->fields['type']['default'] = 'UNEXCUSED';
			$this->fields['student_absence_reason_id']['type'] = 'hidden';
			$this->fields['student_absence_reason_id']['value'] = 0;
		}
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, array $data, array $options) {
		$institutionId = $this->Session->read('Institutions.id');
		$periodId = $data[$this->alias()]['academic_period'];

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$sectionOptions = $Sections
			->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)
			->find('list')
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();

		$this->fields['section']['options'] = $sectionOptions;

		$sectionId = key($sectionOptions);
		$students = [];
		if (!empty($sectionId)) {
			$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$students = $Students
				->findAllByInstitutionSiteSectionId($sectionId)
				->contain(['Users'])
				->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
				->toArray();
		}
		$this->fields['security_user_id']['options'] = $students;
	}

	public function addEditOnChangeSection(Event $event, Entity $entity, array $data, array $options) {
		$sectionId = $data[$this->alias()]['section'];
		
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$students = $Students
			->findAllByInstitutionSiteSectionId($sectionId)
			->contain(['Users'])
			->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
			->toArray();

		$this->fields['security_user_id']['options'] = $students;
	}
}
