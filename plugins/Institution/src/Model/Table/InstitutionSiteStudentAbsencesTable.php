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
		return $validator;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->addField('academic_period');
		$this->ControllerAction->addField('section');
		$this->ControllerAction->addField('type', ['options' => $this->getSelectOptions('Absence.types'), 'onChangeReload' => 'changeType']);
		$this->ControllerAction->updateField('security_user_id');
		$this->ControllerAction->updateField('full_day', ['options' => $this->getSelectOptions('general.yesno')]);
		$this->ControllerAction->updateField('student_absence_reason_id', ['type' => 'select']);
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period', 'security_user_id', 'start_date', 'end_date', 
			'full_day', 'start_time', 'end_time', 'type', 'student_absence_reason_id'
		]);
	}

	public function onAddFieldAcademicPeriod(Event $event) {
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		$this->fields['academic_period']['type'] = 'select';
		$this->fields['academic_period']['options'] = $periodOptions;
		$this->fields['academic_period']['onChangeReload'] = 'changePeriod';
	}

	public function onAddFieldSection(Event $event) {
		$institutionId = $this->Session->read('Institutions.id');
		$periodId = key($this->fields['academic_period']['options']);

		if ($this->request->is('post')) {
			$periodId = $this->request->data($this->aliasField('academic_period'));
		}

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$sectionOptions = $Sections
			->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)
			->find('list')
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();

		$this->fields['section']['type'] = 'select';
		$this->fields['section']['options'] = $sectionOptions;
		$this->fields['section']['onChangeReload'] = 'changeSection';
	}

	public function onUpdateFieldSecurityUserId(Event $event) {
		$sectionId = key($this->fields['section']['options']);

		$students = [];
		if (!empty($sectionId)) {
			$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$students = $Students
				->findAllByInstitutionSiteSectionId($sectionId)
				->contain(['Users'])
				->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
				->toArray();
		}
		$this->fields['security_user_id']['type'] = 'select';
		$this->fields['security_user_id']['options'] = $students;
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

	public function addEditOnChangeType(Event $event, Entity $entity, array $data, array $options) {
		if ($data[$this->alias()]['type'] == 'UNEXCUSED') {
			$this->fields['student_absence_reason_id']['type'] = 'hidden';
			$this->fields['student_absence_reason_id']['value'] = 0;
		}
	}
}
