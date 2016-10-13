<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Inflector;

class TransferRequestsTable extends AppTable {
	private $selectedAcademicPeriod;
	private $selectedGrade;
	private $InstitutionGrades;

	// Type for application
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

	public function initialize(array $config) {
		$this->table('institution_student_admission');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons']);
		$this->belongsTo('NewEducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->addBehavior('OpenEmis.Section');
		$this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
    	return $events;
    }

	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
		$Navigation->substituteCrumb('Transfers', 'TransferRequests', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'TransferRequests']);
		$Navigation->addCrumb(ucfirst($this->ControllerAction->action()));
	}

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$statusToshow = [self::NEW_REQUEST, self::REJECTED];
		$typeToShow = [];

		if ($this->AccessControl->check(['Institutions', 'TransferApprovals', 'view'])) {
			$typeToShow[] = self::TRANSFER;
		}

		$query->where([$this->aliasField('previous_institution_id') => $institutionId], [], true);

		$query->where([$this->aliasField('type').' IN' => $typeToShow, $this->aliasField('status').' IN' => $statusToshow]);
	}



    public function onGetStudentId(Event $event, Entity $entity){
		$urlParams = $this->ControllerAction->url('index');
		$action = $urlParams['action'];
		if ($entity->status == self::NEW_REQUEST) {
			if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				return $event->subject()->Html->link($entity->user->name, [
					'plugin' => $urlParams['plugin'],
					'controller' => $urlParams['controller'],
					'action' => $action,
					'0' => 'edit',
					'1' => $entity->id
				]);
			}
		}
	}

    public function onGetStatus(Event $event, Entity $entity) {
		$statusName = "";
		switch ($entity->status) {
			case self::NEW_REQUEST:
				$statusName = __('New');
				break;
			case self::APPROVED:
				$statusName = __('Approved');
				break;
			case self::REJECTED:
				$statusName = __('Rejected');
				break;
			default:
				$statusName = $entity->status;
				break;
		}
		return __($statusName);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->add('student_id', 'ruleNoNewDropoutRequestInGradeAndInstitution', [
				'rule' => ['noNewDropoutRequestInGradeAndInstitution'],
				'on' => 'create'
			])
			->add('student_id', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
				'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                    'excludeInstitutions' => ['previous_institution_id'],
                    'targetInstitution' => ['previous_institution_id']
                    ]
                ],
				'on' => 'create'
			])
			->add('student_id', 'ruleStudentNotCompletedGrade', [
				'rule' => ['studentNotCompletedGrade', [
					'educationGradeField' => 'new_education_grade_id',
					'studentIdField' => 'student_id'
				]],
				'on' => 'create'
			])
			->requirePresence('new_education_grade_id')
		;

		$this->setValidationCode('student_name.ruleStudentNotCompletedGrade', 'Institution.Students');
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $entityError = $entity->errors();
		if (!empty($entityError)) {
            $entityStudentError = $entity->errors('student_id');
			if (!empty($entityStudentError)) {
				// for 'add' putting the validation message in the correct place (unable to just validate on 'student' as 'notBlank' will trigger and was unable to remove)
				$entity->errors('student', $entity->errors('student_id'));
			}
		} else {
			$Students = TableRegistry::get('Institution.Students');
			$id = $this->Session->read('Institution.Students.id');
			$institutionStudentData = $Students->get($id);

			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			$statusCodeList = array_flip($StudentStatuses->findCodeList());

			$isPromotedOrGraduated = in_array($statusCodeList[$institutionStudentData->student_status_id], ['GRADUATED', 'PROMOTED']);
			if ($isPromotedOrGraduated) {
                // when transfering a $isPromotedOrGraduated, it would be transfering to another academic period, therefore the end_date has to change accordingly
				$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
				$targetAcademicPeriodData = $AcademicPeriods->get($entity->academic_period_id);
				$entity->start_date = $targetAcademicPeriodData->start_date->format('Y-m-d');
				$entity->end_date = $targetAcademicPeriodData->end_date->format('Y-m-d');
			}
		}
	}

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
    	if ($this->Session->read($this->registryAlias().'.id')) {
	    	$id = $this->Session->read('Student.Students.id');
	    	// $action = $this->ControllerAction->buttons['add']['url'];
	    	$action = $this->ControllerAction->url('add');
			$action['action'] = 'StudentUser';
			$action[0] = 'view';
			$action[1] = $id;
			$action['id'] = $this->Session->read($this->registryAlias().'.id');
	    	$event->stopPropagation();
	    	return $this->controller->redirect($action);
    	}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$id = $this->Session->read($this->registryAlias().'.id');

		$Students = TableRegistry::get('Institution.Students');
		$studentData = $Students->get($id);
		$selectedStudent = $studentData->student_id;
		$selectedPeriod = $studentData->academic_period_id;
		$selectedGrade = $studentData->education_grade_id;

		$StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
		$student = $StudentPromotion
			->find()
			->where([
				$StudentPromotion->aliasField('institution_id') => $institutionId,
				$StudentPromotion->aliasField('student_id') => $selectedStudent,
				$StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
				$StudentPromotion->aliasField('education_grade_id') => $selectedGrade
			])
			->first();

		$entity->student_id = $student->student_id;
		$entity->academic_period_id = $student->academic_period_id;
		$entity->education_grade_id = $student->education_grade_id;
		$entity->student_status_id = $studentData->student_status_id;
		if ($student->start_date instanceof Time || $student->start_date instanceof Date) {
			$entity->start_date = $student->start_date->format('Y-m-d');
		} else {
			$entity->start_date = date('Y-m-d', strtotime($student->start_date));
		}

		if ($student->end_date instanceof Time || $student->end_date instanceof Date) {
			$entity->end_date = $student->end_date->format('Y-m-d');
		} else {
			$entity->end_date = date('Y-m-d', strtotime($student->end_date));
		}

		$entity->previous_institution_id = $institutionId;

		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
		$this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['previous_institution_id'] = $entity->previous_institution_id;
		$this->request->data[$this->alias()]['student_status_id'] = $entity->student_status_id;
	}

	public function beforeAction(Event $event) {
    	$this->ControllerAction->field('institution_class_id', ['visible' => false]);
    }

	public function indexBeforeAction(Event $event) {
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => true]);
    	$this->ControllerAction->field('start_date', ['visible' => false]);
    	$this->ControllerAction->field('end_date', ['visible' => false]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => false]);
    	$this->ControllerAction->field('type', ['visible' => false]);
    	$this->ControllerAction->field('comment', ['visible' => false]);
    	$this->ControllerAction->field('student_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id', ['visible' => false]);
    	$this->ControllerAction->field('academic_period_id');
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('new_education_grade_id');
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => false]);
    	$this->Session->delete($this->registryAlias().'.id');
    }

    private function addSections()
    {
		$this->ControllerAction->field('transfer_status_header', ['type' => 'section', 'title' => __('Transfer Status')]);
		$this->ControllerAction->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
		$this->ControllerAction->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
		$this->ControllerAction->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
    }

    public function viewBeforeAction(Event $event) {
    	$this->addSections();
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => true]);
    	$this->ControllerAction->field('start_date', ['visible' => true]);
    	$this->ControllerAction->field('end_date', ['visible' => true]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => false]);
    	$this->ControllerAction->field('type', ['visible' => false]);
    	$this->ControllerAction->field('comment', ['visible'
    	 => true]);
    	$this->ControllerAction->field('student_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id', ['visible' => true]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'readonly']);
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('new_education_grade_id');
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => true]);
    }

    public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->registryAlias().'.id')) {
			$this->addSections();
			$this->ControllerAction->field('transfer_status');
			$this->ControllerAction->field('student');
			$this->ControllerAction->field('student_id');
			$this->ControllerAction->field('academic_period_id');
			$this->ControllerAction->field('education_grade_id');
			$this->ControllerAction->field('new_education_grade_id');
			$this->ControllerAction->field('institution_id');
			$this->ControllerAction->field('status');
			$this->ControllerAction->field('start_date');
			$this->ControllerAction->field('end_date');
			$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
			$this->ControllerAction->field('comment');
			$this->ControllerAction->field('previous_institution_id');
			$this->ControllerAction->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);
			$this->ControllerAction->field('student_status_id', ['type' => 'hidden']);

			$this->ControllerAction->setFieldOrder([
				'transfer_status_header', 'transfer_status',
				'existing_information_header', 'student', 'previous_institution_id', 'education_grade_id',
				'new_information_header', 'new_education_grade_id', 'institution_id',
				'academic_period_id',
				'status', 'start_date', 'end_date',
				'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
			]);
		} else {
			$Students = TableRegistry::get('Institution.Students');
			// $action = $this->ControllerAction->buttons['index']['url'];
			$action = $this->ControllerAction->url('index');
			$action['action'] = $Students->alias();

			return $this->controller->redirect($action);
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
    	$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->ControllerAction->setFieldOrder([
			'transfer_status_header', 'created', 'status', 'type',
			'existing_information_header', 'student_id', 'academic_period_id', 'education_grade_id', 'start_date', 'end_date',
			'new_information_header', 'new_education_grade_id', 'institution_id',
			'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
		]);
	}

	// add viewAfterAction to perform redirect if type is not 2
    // do the same for TransferApproval

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->type != self::TRANSFER) {
			$event->stopPropagation();
			return $this->controller->redirect(['controller' => 'Institutions', 'action' => 'Students', 'plugin'=>'Institution']);
		}
		$this->addSections();
		$this->ControllerAction->field('transfer_status');
		$this->ControllerAction->field('student');
		$this->ControllerAction->field('student_id');
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('new_education_grade_id', [
			'type' => 'readonly',
			'attr' => [
				'value' => $this->NewEducationGrades->get($entity->new_education_grade_id)->programme_grade_name
			]
		]);
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('end_date');
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('comment');
		$this->ControllerAction->field('previous_institution_id');
		$this->ControllerAction->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);

		$this->ControllerAction->setFieldOrder([
			'transfer_status_header', 'transfer_status',
			'existing_information_header', 'student', 'previous_institution_id', 'education_grade_id',
			'new_information_header', 'new_education_grade_id', 'institution_id',
			'academic_period_id',
			'status', 'start_date', 'end_date',
			'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
		]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		$this->request->data[$this->alias()]['transfer_status'] = $entity->status;
		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['new_education_grade_id'] = $entity->new_education_grade_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$transferEntity = $this->newEntity($data[$this->alias()]);
		if ($this->save($transferEntity)) {
			$this->Alert->success('general.edit.success');
		} else {
			$this->log($transferEntity->errors(), 'debug');
			$this->Alert->error('general.edit.failed');
		}

		if ($this->Session->read($this->registryAlias().'.id')) {
			$Students = TableRegistry::get('Institution.StudentUser');
			$id = $this->Session->read('Student.Students.id');
			// $action = $this->ControllerAction->buttons['edit']['url'];
			$action = $this->ControllerAction->url('edit');
			$action['action'] = $Students->alias();
			$action[0] = 'view';
			$action[1] = $id;
			$action['id'] = $this->Session->read($this->registryAlias().'.id');
			$event->stopPropagation();
			return $this->controller->redirect($action);
		}
    }



	/* to be implemented with custom autocomplete
	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true,
				'css' => ['OpenEmis.lib/jquery/jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.lib/jquery/jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}
	*/

	public function onUpdateFieldTransferStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = __('New');
		} else if ($action == 'edit') {
			$transferStatus = $request->data[$this->alias()]['transfer_status'];

			$attr['type'] = 'readonly';
			if ($transferStatus == 0) {
				$attr['attr']['value'] = __('New');
			} else if ($transferStatus == 1) {
				$attr['attr']['value'] = __('Approve');
			} else if ($transferStatus == 2) {
				$attr['attr']['value'] = __('Reject');
			}
		}

		return $attr;
	}

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['student_id'];

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$selectedStudent = $request->data[$this->alias()]['student_id'];
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $selectedStudent;
			return $attr;
		}
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
			$institutionId = $this->Session->read('Institution.Institutions.id');

			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$selectedAcademicPeriodData = $AcademicPeriods->get($this->selectedAcademicPeriod);

			if ($selectedAcademicPeriodData->start_date instanceof Time || $selectedAcademicPeriodData->start_date instanceof Date) {
				$academicPeriodStartDate = $selectedAcademicPeriodData->start_date->format('Y-m-d');
			} else {
				$academicPeriodStartDate = date('Y-m-d', $selectedAcademicPeriodData->start_date);
			}

			if ($selectedAcademicPeriodData->end_date instanceof Time || $selectedAcademicPeriodData->end_date instanceof Date) {
				$academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
			} else {
				$academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
			}

			$institutionOptions = $this->Institutions
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->join([
					'table' => $InstitutionGrades->table(),
					'alias' => $InstitutionGrades->alias(),
					'conditions' => [
						$InstitutionGrades->aliasField('institution_id =') . $this->Institutions->aliasField('id'),
						$InstitutionGrades->aliasField('institution_id') . ' <> ' . $institutionId,
						$InstitutionGrades->aliasField('education_grade_id') => $this->selectedGrade,
						$InstitutionGrades->aliasField('start_date') . ' <= ' => $academicPeriodEndDate,
						'OR' => [
							$InstitutionGrades->aliasField('end_date') . ' IS NULL',
							$InstitutionGrades->aliasField('end_date') . ' >=' => $academicPeriodStartDate
						]
					]
				])
				->order([$this->Institutions->aliasField('code')]);

			$attr['type'] = 'chosenSelect';
			$attr['attr']['multiple'] = false;
			$attr['select'] = true;
			// if ($this->selectedGrade == $request->data[$this->alias()]['education_grade_id']) {
				// $institutionOptions->where([$this->Institutions->aliasField('id').' <> ' => $institutionId]);
			// }

			$attr['options'] = $institutionOptions->toArray();

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
			$attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->code_name;
		}

		return $attr;
	}

	public function onUpdateFieldNewEducationGradeId(Event $event, array $attr, $action, $request)
	{
		if ($action == 'add') {
			$id = $this->Session->read($this->registryAlias().'.id');
			$Students = TableRegistry::get('Institution.Students');

			$studentInfo = $Students->find()->contain(['EducationGrades', 'StudentStatuses'])->where([$Students->aliasField($Students->primaryKey()) => $id])->first();

			$studentStatusCode = null;
			if ($studentInfo) {
				$studentStatusCode = $studentInfo->student_status->code;
			}

			switch ($studentStatusCode) {
				case 'GRADUATED': case 'PROMOTED':
						$moreAdvancedEducationGrades = [];
						$currentProgrammeGrades = $this->EducationGrades
							->find('list', [
								'keyField' => 'id',
								'valueField' => 'programme_grade_name'
							])
							->find('visible')
							->where([
								$this->EducationGrades->aliasField('order').' > ' => $studentInfo->education_grade->order,
								$this->EducationGrades->aliasField('education_programme_id') => $studentInfo->education_grade->education_programme_id
							])
							->toArray();

						$EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
						$educationProgrammeId = $studentInfo->education_grade->education_programme_id;
						$nextEducationGradeList = $EducationProgrammesNextProgrammesTable->getNextGradeList($educationProgrammeId);
						$moreAdvancedEducationGrades = $currentProgrammeGrades + $nextEducationGradeList;
						if (isset($request->data[$this->alias()]['new_education_grade_id'])) {
							$this->selectedGrade = $request->data[$this->alias()]['new_education_grade_id'];
							if (!array_key_exists($this->selectedGrade, $moreAdvancedEducationGrades)) {
								reset($moreAdvancedEducationGrades);
								$this->selectedGrade = key($moreAdvancedEducationGrades);
							}
						}

						$attr['options'] = $moreAdvancedEducationGrades;
						$attr['onChangeReload'] = true;

					break;

				default:
					// $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
					// $educationGradeId = $request->data[$this->alias()]['education_grade_id'];
					// $requestInstitution = $request->data[$this->alias()]['previous_institution_id'];
					// $InstitutionGrades = $this->InstitutionGrades;
					// $grades = $this->EducationGrades
					// 	->find()
					// 	->contain(['EducationProgrammes'])
					// 	->select([
					// 		'EducationGrades.id',
					// 		'EducationGrades.name',
					// 		'EducationGrades.education_programme_id',
					// 		'EducationProgrammes.name',
					// 	])
					// 	->order(['EducationProgrammes.order', 'EducationGrades.order']);

					// $gradeOptions = [];
					// foreach ($grades as $grade) {
					// 	$gradeOptions[$grade->education_programme->name][$grade->id] = $grade->programme_grade_name;
					// }
					// $selectedGrade = key($gradeOptions);
					// if (!isset($request->data[$this->alias()]['new_education_grade_id'])) {
					// 	$request->data[$this->alias()]['new_education_grade_id'] = $educationGradeId;
					// 	$selectedGrade = $educationGradeId;
					// }
					// $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
					// 	'message' => '{{label}} - ' . $this->getMessage('StudentTransfer.noInstitutions'),
					// 	'callable' => function($id) use ($InstitutionGrades, $academicPeriodId) {
					// 		return $InstitutionGrades
					// 			->find()
					// 			->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
					// 			->where([
					// 				$InstitutionGrades->aliasField('education_grade_id') => $id
					// 			])
					// 			->count();
					// 	}
					// ]);
					// $attr['type'] = 'select';
					// $attr['options'] = $gradeOptions;
					// $attr['onChangeReload'] = true;
					// $this->selectedGrade = $request->data[$this->alias()]['new_education_grade_id'];
					// break;


					$this->selectedGrade = $request->data[$this->alias()]['education_grade_id'];
					$request->data[$this->alias()]['new_education_grade_id'] = $this->selectedGrade;
					$attr['type'] = 'readonly';
					$attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
					break;
			}
		} elseif ($action == 'edit') {
			$this->selectedGrade = $request->data[$this->alias()]['new_education_grade_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
			$status = $StudentStatusesTable->findCodeList();
			$studentStatus = $request->data[$this->alias()]['student_status_id'];
			switch ($studentStatus) {
				case $status['PROMOTED']:
				case $status['GRADUATED']:
					$id = $this->Session->read($this->registryAlias().'.id');
					$Students = TableRegistry::get('Institution.Students');
					$studentInfo = $Students->find()->contain(['AcademicPeriods'])->where([$Students->aliasField($Students->primaryKey()) => $id])->first();

					$academicPeriodStartDate = $studentInfo->academic_period->start_date;

					if ($studentInfo->academic_period->start_date instanceof Time || $studentInfo->academic_period->start_date instanceof Date) {
						$academicPeriodStartDate = $studentInfo->academic_period->start_date->format('Y-m-d');
					} else {
						$academicPeriodStartDate = date('Y-m-d', strtotime($studentInfo->academic_period->start_date));
					}
					$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
					$academicPeriodsAfter = $AcademicPeriods
						->find('list', ['keyField' => 'id', 'valueField' => 'name'])
						->where([$AcademicPeriods->aliasField('start_date') .' > '  => $academicPeriodStartDate])
						->where([$AcademicPeriods->aliasField('academic_period_level_id') => $studentInfo->academic_period->academic_period_level_id])
						->order($AcademicPeriods->aliasField('start_date').' asc')
						->toArray()
						;

					$this->selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
					if (!array_key_exists($this->selectedAcademicPeriod, $academicPeriodsAfter)) {
						reset($academicPeriodsAfter);
						$this->selectedAcademicPeriod = key($academicPeriodsAfter);
					}

					$attr['options'] = $academicPeriodsAfter;

					break;

				case $status['CURRENT']:
					$this->selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
					$attr['type'] = 'hidden';
					break;
			}
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$educationGradeId = $request->data[$this->alias()]['education_grade_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
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
			$institutionId = $this->Session->read('Institution.Institutions.id');

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $institutionId;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$startDate = $request->data[$this->alias()]['start_date'];

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];

			$attr['type'] = 'hidden';
			$attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
		}

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add' || $action == 'edit') {
			if ($this->Session->read($this->registryAlias().'.id')) {
				$Students = TableRegistry::get('Institution.StudentUser');
				$toolbarButtons['back']['url']['action'] = $Students->alias();
				$toolbarButtons['back']['url'][0] = 'view';
				$toolbarButtons['back']['url'][1] = $this->Session->read('Student.Students.id');
			} else {
				if ($action == 'edit') {
					$toolbarButtons['back']['url'][0] = 'index';
					if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
						$toolbarButtons['back']['url']['action']= 'index';
						unset($toolbarButtons['back']['url'][0]);
					}
					unset($toolbarButtons['back']['url'][1]);
				}
			}
		} else if ($action == 'index') {
			if (isset($toolbarButtons['add'])) {
				unset($toolbarButtons['add']);
			}
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['plugin'] = 'Institution';
			$toolbarButtons['back']['url']['controller'] = 'Institutions';
			$toolbarButtons['back']['url']['action'] = 'Students';
			$toolbarButtons['back']['url'][0] = 'index';
			$toolbarButtons['back']['attr'] = $attr;
		} else if ($action == 'view') {
			if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST) {
				unset($toolbarButtons['edit']);
			}
			$toolbarButtons['back']['url']['action'] = 'StudentUser';
			$toolbarButtons['back']['url'][0] = 'view';
			$toolbarButtons['back']['url'][1] = $this->Session->read('Student.Students.id');
		}
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
