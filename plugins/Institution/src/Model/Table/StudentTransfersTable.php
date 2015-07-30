<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class StudentTransfersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_student_transfers');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
	}

	public function validationDefault(Validator $validator) {
		return $validator
 	        ->add('end_date', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'start_date', false]
	    	    ])
	        ;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
    	return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$institutionId = $entity->previous_institution_id;
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

			$this->Alert->success('StudentTransfers.request');

			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['add']['url'];
			$action['action'] = $Students->alias();
			$action[0] = 'view';
			$action[1] = $selectedStudent;

			return $this->controller->redirect($action);
		}
    }

	public function addOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		list(, $selectedInstitution, , $selectedPeriod, , $selectedProgramme) = array_values($this->_getSelectOptions());

		$this->request->data[$this->alias()]['security_user_id'] = $this->Session->read($this->alias().'.security_user_id');
		$this->request->data[$this->alias()]['institution_id'] = $selectedInstitution;
		$this->request->data[$this->alias()]['academic_period'] = $selectedPeriod;
		$this->request->data[$this->alias()]['education_programme_id'] = $selectedProgramme;
	}

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->alias().'.security_user_id')) {

			$this->ControllerAction->field('student');
			$this->ControllerAction->field('security_user_id');
			$this->ControllerAction->field('institution_id');
			$this->ControllerAction->field('academic_period');
			$this->ControllerAction->field('education_programme_id');
			$this->ControllerAction->field('status');
			$this->ControllerAction->field('previous_institution_id');
			// Start Date and End Date
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
			// End

			$this->ControllerAction->setFieldOrder([
				'student',
				'institution_id', 'academic_period', 'education_programme_id',
				'status', 'start_date', 'end_date', 'previous_institution_id'
			]);
		} else {
			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->buttons['index']['url'];
			$action['action'] = $Students->alias();

			return $this->controller->redirect($action);
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		$this->request->data[$this->alias()]['security_user_id'] = $entity->security_user_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['education_programme_id'] = $entity->education_programme_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('student');
		$this->ControllerAction->field('security_user_id');
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('education_programme_id');
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('previous_institution_id');
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('end_date');

		$this->ControllerAction->setFieldOrder([
			'student',
			'institution_id', 'education_programme_id',
			'status', 'start_date', 'end_date', 'previous_institution_id'
		]);
	}

	/* to be implemented with custom autocomplete
	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}
	*/

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['security_user_id'];

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['security_user_id'];

		$attr['type'] = 'hidden';
		$attr['attr']['value'] = $selectedStudent;

		return $attr;
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institutions.id');
			$institutionOptions = $this->Institutions
				->find('list')
				->where([$this->Institutions->aliasField('id <>') => $institutionId])
				->toArray();

			$attr['type'] = 'select';
			$attr['options'] = $institutionOptions;
			$attr['onChangeReload'] = 'changeInstitution';

			/* to be implemented with custom autocomplete
			$attr['type'] = 'string';
			$attr['attr'] = [
				'class' => 'autocomplete',
				'autocomplete-url' => '/core_v3/Institutions/Transfers/ajaxInstitutionAutocomplete',
				'autocomplete-class' => 'error-message',
				'autocomplete-no-results' => __('No Institution found.'),
				'value' => ''
			];
			*/
		} else if ($action == 'edit') {
			$selectedInstitution = $request->data[$this->alias()]['institution_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->name;
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$selectedInstitution = $request->data[$this->alias()]['institution_id'];
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
		if ($action == 'add') {
			$selectedInstitution = $request->data[$this->alias()]['institution_id'];
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
		} else if ($action == 'edit') {
			$selectedProgramme = $request->data[$this->alias()]['education_programme_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationProgrammes->get($selectedProgramme)->cycle_programme_name;
		}

		return $attr;
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$status = 0; // New

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $status;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institutions.id');

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $institutionId;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$startDate = $request->data[$this->alias()]['start_date'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
		}
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
		}
		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add') {
			$Students = TableRegistry::get('Institution.Students');
			$toolbarButtons['back']['url']['action'] = $Students->alias();
			$toolbarButtons['back']['url'][0] = 'view';
			$toolbarButtons['back']['url'][] = $this->Session->read($this->alias().'.security_user_id');
		} else if ($action == 'edit') {
			unset($toolbarButtons['back']);
		}
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

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
    	if ($AccessControl->check(['Dashboard', 'Transfers', 'edit'])) {
    		// $institutionIds = $AccessControl->getInstitutionsByUser(null, ['Dashboard', 'Transfers', 'edit']);
			$resultSet = $this
				->find()
				->where([
					$this->aliasField('status') => 0
				])
				->contain(['Users', 'Institutions', 'EducationProgrammes', 'PreviousInstitutions', 'ModifiedUser', 'CreatedUser'])
				->order([
					$this->aliasField('created')
				])
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Transfer of student (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => 'Transfers',
					'edit',
					$obj->id
				];

				$data[] = [
					'request_title' => ['title' => $requestTitle, 'url' => $url],
					'receive_date' => date('Y-m-d', strtotime($obj->modified)),
					'due_date' => '<i class="fa fa-minus"></i>',
					'requester' => $obj->created_user->username,
					'type' => __('Student Transfer')
				];
			}
    	}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			$buttons[0] = [
				'name' => '<i class="fa fa-check"></i> ' . __('Approve'),
				'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'approve']
			];

			$buttons[1] = [
				'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
				'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
			];
		}
	}

	public function editOnApprove(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Update status to Transferred in previous school
    	$institutionId = $entity->previous_institution_id;
		$selectedStudent = $entity->security_user_id;
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

		$status = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'TRANSFERRED'])
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
		// End

		// Update status to 1 => approve
		$this->updateAll(
			['status' => 1],
			[
				'id' => $entity->id
			]
		);
		// End

		// Add Student to new school
		$currentStatus = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
			->first()
			->id;

		$requestData = [
			'start_date' => $entity->start_date,
			'start_year' => date("Y", strtotime($entity->start_date)),
			'end_date' => $entity->end_date,
			'end_year' => date("Y", strtotime($entity->end_date)),
			'security_user_id' => $entity->security_user_id,
			'student_status_id' => $currentStatus,
			'institution_site_id' => $entity->institution_id,
			'education_programme_id' => $entity->education_programme_id
		];

		$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
		$studentEntity = $InstitutionSiteStudents->newEntity($requestData);

		if ($InstitutionSiteStudents->save($studentEntity)) {
		} else {
			$this->log($studentEntity->errors(), 'debug');
		}
		// End

		$this->Alert->success('StudentTransfers.approve');
		$event->stopPropagation();

		return $this->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
	}

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Update status to Current in previous school
    	$institutionId = $entity->previous_institution_id;
		$selectedStudent = $entity->security_user_id;
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

		$status = $StudentStatuses
			->find()
			->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
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
		// End

		// Update status to 2 => reject
		$this->updateAll(
			['status' => 2],
			[
				'id' => $entity->id
			]
		);
		// End

		$this->Alert->success('StudentTransfers.reject');
		$event->stopPropagation();

		return $this->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
	}

	/* to be implemented with custom autocomplete
	public function ajaxInstitutionAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Institutions->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}
	*/
}
