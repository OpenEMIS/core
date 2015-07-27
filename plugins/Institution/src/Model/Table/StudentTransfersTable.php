<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;

class StudentTransfersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_transfers');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_site_id']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
    	if ($entity->isNew()) {
			$institutionId = $entity->previous_institution_site_id;
			$selectedStudent = $entity->security_user_id;
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			$status = $StudentStatuses
				->find()
				->where([$StudentStatuses->aliasField('code') => 'PENDING_TRANSFER'])
				->first()
				->id;
	
			$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
			$InstitutionSiteStudents->updateAll(
				['student_status_id' => $status],
				[
					'institution_site_id' => $institutionId,
					'security_user_id' => $selectedStudent
				]
			);

			// Rediect
			$this->Alert->success('StudentTransfers.request');

			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['view']['url'];
			$action['action'] = $Students->alias();
			$action[] = $selectedStudent;
			return $this->controller->redirect($action);
			// End
		}
    }

	public function addOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		list(, $selectedInstitution, , $selectedPeriod, , $selectedProgramme) = array_values($this->_getSelectOptions());

		$this->request->data[$this->alias()]['institution_site_id'] = $selectedInstitution;
		$this->request->data[$this->alias()]['academic_period'] = $selectedPeriod;
		$this->request->data[$this->alias()]['education_programme_id'] = $selectedProgramme;
	}

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->alias().'.security_user_id')) {

			$this->ControllerAction->field('student');
			$this->ControllerAction->field('security_user_id');
			$this->ControllerAction->field('institution_site_id');
			$this->ControllerAction->field('academic_period');
			$this->ControllerAction->field('education_programme_id');
			$this->ControllerAction->field('status');
			$this->ControllerAction->field('previous_institution_site_id');
			// Start Date and End Date
			if ($this->action == 'add') {
				$selectedPeriod = $this->request->data[$this->alias()]['academic_period'];
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

			$this->ControllerAction->setFieldOrder([
				'student',
				'institution_site_id', 'academic_period', 'education_programme_id',
				'status', 'start_date', 'end_date', 'previous_institution_site_id'
			]);
		} else {
			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['index']['url'];
			$action['action'] = $Students->alias();

			return $this->controller->redirect($action);
		}
	}

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		$selectedStudent = $this->Session->read($this->alias().'.security_user_id');

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		$selectedStudent = $this->Session->read($this->alias().'.security_user_id');

		$attr['type'] = 'hidden';
		$attr['attr']['value'] = $selectedStudent;

		return $attr;
	}

	public function onUpdateFieldInstitutionSiteId(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$institutionOptions = $this->Institutions
			->find('list')
			->where([$this->Institutions->aliasField('id <>') => $institutionId])
			->toArray();

		$attr['type'] = 'select';
		$attr['options'] = $institutionOptions;
		$attr['onChangeReload'] = 'changeInstitution';

		return $attr;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$selectedInstitution = $request->data[$this->alias()]['institution_site_id'];
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriods->getList();
		$selectedPeriod = $request->data[$this->alias()]['academic_period'];

		$Programmes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammes')),
			'callable' => function($id) use ($Programmes, $selectedInstitution) {
				return $Programmes
					->find()
					->where([$Programmes->aliasField('institution_site_id') => $selectedInstitution])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);

		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = 'changePeriod';

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$selectedInstitution = $request->data[$this->alias()]['institution_site_id'];
		$selectedPeriod = $request->data[$this->alias()]['academic_period'];
		$selectedProgramme = $request->data[$this->alias()]['education_programme_id'];

		$Programmes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$programmeOptions = $Programmes
			->find('list', ['keyField' => 'education_programme_id', 'valueField' => 'cycle_programme_name'])
			->where([$Programmes->aliasField('institution_site_id') => $selectedInstitution])
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->order('EducationSystems.order', 'EducationLevels.order', 'EducationCycles.order', 'EducationProgrammes.order')
			->toArray();
		$this->advancedSelectOptions($programmeOptions, $selectedProgramme);
		
		$attr['options'] = $programmeOptions;
		$attr['onChangeReload'] = 'changeProgramme';

		return $attr;
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$status = 0; // New

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $status;
		}

		return $attr;
	}

	public function onUpdateFieldPreviousInstitutionSiteId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institutions.id');

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $institutionId;
		}

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$Students = TableRegistry::get('Institution.Students');
		$toolbarButtons['back']['url']['action'] = $Students->alias();
		$toolbarButtons['back']['url'][0] = 'view';
		$toolbarButtons['back']['url'][] = $this->Session->read($this->alias().'.security_user_id');
	}

	public function _getSelectOptions() {
		$periodOptions = [];
		$selectedPeriod = null;
		$programmeOptions = [];
		$selectedProgramme = null;
		$gradeOptions = [];
		$selectedGrade = null;
		$sectionOptions = [];
		$selectedSection = null;

		//Return all required options and their key
		// Institutions
		$institutionId = $this->Session->read('Institutions.id');
		$institutionOptions = $this->Institutions
			->find('list')
			->where([$this->Institutions->aliasField('id <>') => $institutionId])
			->toArray();
		$selectedInstitution = key($institutionOptions);
		// End

		// Academic Period
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriods->getList();
		$selectedPeriod = key($periodOptions);
		// End

		// Institution Site Programme
		$Programmes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$programmeOptions = $Programmes
			->find('list', ['keyField' => 'education_programme_id', 'valueField' => 'cycle_programme_name'])
			->where([$Programmes->aliasField('institution_site_id') => $selectedInstitution])
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->order('EducationSystems.order', 'EducationLevels.order', 'EducationCycles.order', 'EducationProgrammes.order')
			->toArray();
		$selectedProgramme = key($programmeOptions);
		// End

		return compact('institutionOptions', 'selectedInstitution', 'periodOptions', 'selectedPeriod', 'programmeOptions', 'selectedProgramme');
	}
}
