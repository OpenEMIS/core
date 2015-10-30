<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;

class TransferRequestsTable extends AppTable {
	private $selectedAcademicPeriod;
	private $selectedGrade;
	
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
		$this->belongsTo('StudentTransferReasons', ['className' => 'FieldOption.StudentTransferReasons']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
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

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {

    	$StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');

    	$conditions = [
			'student_id' => $entity->student_id, 
			'status' => self::NEW_REQUEST,
			'education_grade_id' => $entity->education_grade_id,
			'institution_id' => $entity->previous_institution_id
		];

		$count = $StudentDropoutTable->find()
			->where($conditions)
			->count();

		if ($count > 0) {
			$process = function ($model, $entity) {
				$this->Alert->error('TransferRequests.hasDropoutApplication');
			};
			return $process;
		} else {
			$process = function($model, $entity) {
				$Students = TableRegistry::get('Institution.Students');
				$id = $this->Session->read('Institution.Students.id');
				$institutionStudentData = $Students->get($id);

				$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
				$statusCodeList = array_flip($StudentStatuses->findCodeList());

				$isPromotedOrGraduated = in_array($statusCodeList[$institutionStudentData->student_status_id], ['GRADUATED', 'PROMOTED']);
				if ($isPromotedOrGraduated) {
					$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
					$targetAcademicPeriodData = $AcademicPeriods->get($entity->academic_period_id);
					$entity->start_date = $targetAcademicPeriodData->start_date->format('Y-m-d');
					$entity->end_date = $targetAcademicPeriodData->end_date->format('Y-m-d');
				}

				$result = $model->save($entity);

				if ($result) {
					$this->Alert->success('TransferRequests.request');
				}
				return $result;
			};
			return $process;
		}
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
    	if ($this->Session->read($this->registryAlias().'.id')) {
	    	$id = $this->Session->read($this->registryAlias().'.id');
	    	// $action = $this->ControllerAction->buttons['add']['url'];
	    	$action = $this->ControllerAction->url('add');
			$action['action'] = 'Students';
			$action[0] = 'view';
			$action[1] = $id;
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
		if ($student->start_date instanceof Time) {
			$entity->start_date = $student->start_date->format('Y-m-d');
		} else {
			$entity->start_date = date('Y-m-d', strtotime($student->start_date));
		}

		if ($student->end_date instanceof Time) {
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
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => false]);
    	$this->Session->delete($this->registryAlias().'.id');
    }

    public function viewBeforeAction(Event $event) {
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => true]);
    	$this->ControllerAction->field('start_date', ['visible' => true]);
    	$this->ControllerAction->field('end_date', ['visible' => true]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => false]);
    	$this->ControllerAction->field('type', ['visible' => false]);
    	$this->ControllerAction->field('comment', ['visible' => true]);
    	$this->ControllerAction->field('student_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id', ['visible' => true]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'readonly']);
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => true]);
    }

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->registryAlias().'.id')) {
			$this->ControllerAction->field('transfer_status');
			$this->ControllerAction->field('student');
			$this->ControllerAction->field('student_id');
			$this->ControllerAction->field('academic_period_id');
			$this->ControllerAction->field('education_grade_id');
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
				'transfer_status', 'student', 'academic_period_id', 'education_grade_id',
				'institution_id', 
				'status', 'start_date', 'end_date',
				'student_transfer_reason_id', 'comment',
				'previous_institution_id'
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
			'created', 'status', 'type', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'student_transfer_reason_id', 'comment'
		]);
	}

	// add viewAfterAction to perform redirect if type is not 2
    // do the same for TransferApproval

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->type != self::TRANSFER) {
			$event->stopPropagation();
			return $this->controller->redirect(['controller' => 'Institutions', 'action' => 'Students', 'plugin'=>'Institution']);
		}
		$this->ControllerAction->field('transfer_status');
		$this->ControllerAction->field('student');
		$this->ControllerAction->field('student_id');
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('end_date');
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('comment');
		$this->ControllerAction->field('previous_institution_id');
		$this->ControllerAction->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);

		$this->ControllerAction->setFieldOrder([
			'transfer_status', 'student', 'education_grade_id',
			'institution_id', 'academic_period_id', 
			'status', 'start_date', 'end_date',
			'student_transfer_reason_id', 'comment',
			'previous_institution_id'
		]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		$this->request->data[$this->alias()]['transfer_status'] = $entity->status;
		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$transferEntity = $this->newEntity($data[$this->alias()]);
		if ($this->save($transferEntity)) {
		} else {
			$this->log($transferEntity->errors(), 'debug');
		}

		if ($this->Session->read($this->registryAlias().'.id')) {
			$Students = TableRegistry::get('Institution.Students');
			$id = $this->Session->read($this->registryAlias().'.id');
			// $action = $this->ControllerAction->buttons['edit']['url'];
			$action = $this->ControllerAction->url('edit');
			$action['action'] = $Students->alias();
			$action[0] = 'view';
			$action[1] = $id;
			$event->stopPropagation();
			return $this->controller->redirect($action);
		}
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
			if ($selectedAcademicPeriodData->start_date instanceof Time) {
				$academicPeriodStartDate = $selectedAcademicPeriodData->start_date->format('Y-m-d');
			} else {
				$academicPeriodStartDate = date('Y-m-d', $selectedAcademicPeriodData->start_date);
			}

			$institutionOptions = $this->Institutions
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->join([
					'table' => $InstitutionGrades->table(),
					'alias' => $InstitutionGrades->alias(),
					'conditions' => [
						$InstitutionGrades->aliasField('institution_site_id =') . $this->Institutions->aliasField('id'),
						$InstitutionGrades->aliasField('education_grade_id') => $this->selectedGrade,
						$InstitutionGrades->aliasField('start_date').' <=' => $academicPeriodStartDate,
						'OR' => [
							$InstitutionGrades->aliasField('end_date').' IS NULL',
							$InstitutionGrades->aliasField('end_date').' >=' => $academicPeriodStartDate
						]
					]
				])
				->where([$this->Institutions->aliasField('id <>') => $institutionId]);


			$attr['type'] = 'select';
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

					if ($studentInfo->academic_period->start_date instanceof Time) {
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
					$attr['onChangeReload'] = true;

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
			$id = $this->Session->read($this->registryAlias().'.id');
			$Students = TableRegistry::get('Institution.Students');
			
			$studentInfo = $Students->find()->contain(['EducationGrades', 'StudentStatuses'])->where([$Students->aliasField($Students->primaryKey()) => $id])->first();

			$studentStatusCode = null;
			if ($studentInfo) {
				$studentStatusCode = $studentInfo->student_status->code;
			}

			switch ($studentStatusCode) {
				case 'GRADUATED': case 'PROMOTED':
					if ($action == 'add') {
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

						$this->selectedGrade = $request->data[$this->alias()]['education_grade_id'];
						if (!array_key_exists($this->selectedGrade, $moreAdvancedEducationGrades)) {
							reset($moreAdvancedEducationGrades);
							$this->selectedGrade = key($moreAdvancedEducationGrades);
						}

						$attr['options'] = $moreAdvancedEducationGrades;
						$attr['onChangeReload'] = true;
					} else if ($action == 'edit') {
						$this->selectedGrade = $request->data[$this->alias()]['education_grade_id'];
						$attr['type'] = 'readonly';
						$attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
					}

					break;
				
				default:
					$this->selectedGrade = $request->data[$this->alias()]['education_grade_id'];
					$attr['type'] = 'readonly';
					$attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
					break;
			}			
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
				$Students = TableRegistry::get('Institution.Students');
				$toolbarButtons['back']['url']['action'] = $Students->alias();
				$toolbarButtons['back']['url'][0] = 'view';
				$toolbarButtons['back']['url'][1] = $this->Session->read($this->registryAlias().'.id');
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
