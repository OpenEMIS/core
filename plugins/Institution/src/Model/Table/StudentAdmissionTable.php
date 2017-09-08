<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;

use App\Model\Traits\MessagesTrait;

class StudentAdmissionTable extends AppTable {
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

    use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_student_admission');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('NewEducationGrades', ['className' => 'Education.EducationGrades']);

		$this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
        	'Dashboard' => ['index'],
        	'Students' => ['index', 'add'],

        ]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period_id', [
			])
			->allowEmpty('student_name')
			->add('student_name', 'ruleCheckPendingAdmissionExist', [
				'rule' => ['checkPendingAdmissionExist'],
				'on' => 'create'
			])
			->add('student_name', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
				'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
				'on' => 'create',
				'last' => true
			])
			->add('student_name', 'ruleStudentNotCompletedGrade', [
				'rule' => ['studentNotCompletedGrade', []],
				'on' => 'create',
				'last' => true
			])
			->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
				'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
				'on' => 'create'
			])
			->add('gender_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
            ->add('institution_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
			->allowEmpty('class')
			->add('class', 'ruleClassMaxLimit', [
				'rule' => ['checkInstitutionClassMaxLimit'],
				'on' => 'create'
			])
            ->add('start_date', 'ruleCheckProgrammeEndDateAgainstStudentStartDate', [
                'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
            ])
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id']
            ])
			;
		return $validator;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['Model.Students.afterSave'] = 'studentsAfterSave';
		$events['Model.Students.afterDelete'] = 'studentsAfterDelete';
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
		return $events;
	}

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        //this is meant to force gender_id validation
        if ($data->offsetExists('student_id')) {
            $studentId = $data['student_id'];

            if (!$data->offsetExists('gender_id')) {
                $query = $this->Users->get($studentId);
                $data['gender_id'] = $query->gender_id;
            }
        }
    }

	public function studentsAfterSave(Event $event, $student)
	{
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$statusList = $StudentStatuses->findCodeList();
		$Enrolled = $statusList['CURRENT'];
		$Promoted = $statusList['PROMOTED'];
        $Graduated = $statusList['GRADUATED'];
        $Withdraw = $statusList['WITHDRAWN'];

		if ($student->isNew()) { // add
			if ($student->student_status_id == $Enrolled) {
				// the logic below is to set all pending admission applications to rejected status once the student is successfully enrolled in a school
				$educationSystemId = $this->EducationGrades->getEducationSystemId($student->education_grade_id);
				$educationGradesToUpdate = $this->EducationGrades->getEducationGradesBySystem($educationSystemId);

				$conditions = [
					'student_id' => $student->student_id,
					'status' => 0, // pending status
					'education_grade_id IN' => $educationGradesToUpdate
				];

				// set to rejected status
				$this->updateAll(['status' => 2], $conditions);
			}
		} else { // edit
            // to cater logic if during undo promoted / graduate (without immediate enrolled record), there is still pending admission / transfer
            if ($student->dirty('student_status_id')) {
                $oldStatus = $student->getOriginal('student_status_id');
                $newStatus = $student->student_status_id;
                $UndoPromotion = $oldStatus == $Promoted && $newStatus == $Enrolled;
                $UndoGraduation = $oldStatus == $Graduated && $newStatus == $Enrolled;
                $UndoWithdraw = $oldStatus == $Withdraw && $newStatus == $Enrolled;

                if ($UndoPromotion || $UndoGraduation || $UndoWithdraw) {
                    $this->removePendingAdmission($student->student_id, $student->institution_id);
                }
            }
        }
	}

	public function studentsAfterDelete(Event $event, Entity $student)
	{
        // check for enrolled status and delete admission record
        $this->removePendingAdmission($student->student_id, $student->institution_id);
	}

    protected function removePendingAdmission($studentId, $institutionId)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statusList = $StudentStatuses->findCodeList();

        //remove pending transfer request.
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and transfer/admission can be done on different grade / academic period)
        $conditions = [
            'student_id' => $studentId,
            'previous_institution_id' => $institutionId,
            'status' => 0, //pending status
            'type' => 2 //transfer
        ];

        $entity = $this
                ->find()
                ->where(
                    $conditions
                )
                ->first();

        if (!empty($entity)) {
            $this->delete($entity);
        }

        //remove pending admission request.
        //no institution_id because in the pending admission, the value will be (0)
        $conditions = [
            'student_id' => $studentId,
            'status' => 0, //pending status
            'type' => 1 //admission
        ];

        $entity = $this
                ->find()
                ->where(
                    $conditions
                )
                ->first();

        if (!empty($entity)) {
            $this->delete($entity);
        }
    }

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_search'] = false;

		$statusToshow = [self::NEW_REQUEST, self::REJECTED];
		$typeToShow = [];

		if ($this->AccessControl->check(['Institutions', 'TransferApprovals', 'view'])) {
			$typeToShow[] = self::TRANSFER;
		}

		if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'view'])) {
			$typeToShow[] = self::ADMISSION;
		}

		$query->where([$this->aliasField('type').' IN' => $typeToShow, $this->aliasField('status').' IN' => $statusToshow]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['type'] = $entity->type;
	}

	public function afterAction($event) {
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('start_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('end_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('type');
    	$this->ControllerAction->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    	if ($this->action == 'index') {
    		$this->ControllerAction->field('openemis_no');
    	}
    	$this->ControllerAction->field('student_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id', ['visible' => ['index' => false, 'edit' => true, 'view' => 'true']]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'readonly']);
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    	$this->ControllerAction->field('new_education_grade_id', ['visible' => false]);
    }

    public function editAfterAction($event, Entity $entity) {
    	$selectedClassId = $entity->institution_class_id;// it will check if the students have class

    	if (!is_null($selectedClassId)) {
	    	try {
				$selectedClassName = $this->InstitutionClasses->get($selectedClassId)->name;
			} catch (RecordNotFoundException $ex) {
				Log::write('debug', $ex->getMessage());
				Log::write('debug', $selectedClassId);
				$selectedClassId = NULL;
				$entity->institution_class_id = null;
				$this->save($entity, ['validate' => false]);
			}
		}

		if (is_null($selectedClassId)) {
    		$selectedClassName = $this->getMessage($this->aliasField('noClass'));
    	}

		$this->ControllerAction->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
		$this->ControllerAction->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
		$this->ControllerAction->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
		$this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
		$this->ControllerAction->field('institution_class_id', ['type' => 'readonly', 'attr' => ['value' => $selectedClassName]]);
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'hidden']);
		$this->ControllerAction->field('previous_institution_id', ['type' => 'hidden']);
		$this->ControllerAction->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
  		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'type', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id',
			'start_date', 'end_date', 'comment',
		]);

		$urlParams = $this->ControllerAction->url('edit');
		if ($urlParams['controller'] == 'Dashboard') {
			$this->Navigation->addCrumb('Student Admission', $urlParams);
		}
    }

    public function viewAfterAction($event, Entity $entity) {
    	$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'type', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'comment'
		]);
    }

	public function onGetStatus(Event $event, Entity $entity) {
		$statusName = "";
		switch ($entity->status) {
			case self::NEW_REQUEST:
				$statusName = "New";
				break;
			case self::APPROVED:
				$statusName = "Approved";
				break;
			case self::REJECTED:
				$statusName = "Rejected";
				break;
			default:
				$statusName = $entity->status;
				break;
		}
		return __($statusName);
	}

	public function onGetType(Event $event, Entity $entity) {
		$typeName = "";
		switch ($entity->type) {
			case self::ADMISSION:
				$typeName = "Admission";
				break;
			case self::TRANSFER:
				$typeName = "Transfer";
				break;
			default:
				$typeName = $entity->status;
				break;
		}
		return __($typeName);
	}
	public function onGetOpenemisNo(Event $event, Entity $entity){
		return $entity->user->openemis_no;
	}

	public function onGetStudentId(Event $event, Entity $entity){
		$urlParams = $this->ControllerAction->url('index');
		$action = $urlParams['action'];
		if ($entity->type == self::TRANSFER) {
			$action = 'TransferApprovals';
		}
		if ($entity->status == self::NEW_REQUEST) {
			if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				return $event->subject()->Html->link($entity->user->name, [
					'plugin' => $urlParams['plugin'],
					'controller' => $urlParams['controller'],
					'action' => $action,
					'0' => 'edit',
					'1' => $this->paramsEncode(['id' => $entity->id])
				]);
			}
		}
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$status = $request->data[$this->alias()]['status'];
			$attr['type'] = 'readonly';
			if ($status == self::NEW_REQUEST) {
				$attr['attr']['value'] = __('New');
			} else if ($status == self::APPROVED) {
				$attr['attr']['value'] = __('Approved');
			} else if ($status == self::REJECTED) {
				$attr['attr']['value'] = __('Rejected');
			}
			return $attr;
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button,
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST && ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Approve');

				$buttons[1] = [
					'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
					'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
				];
			} else {
				unset($buttons[0]);
				unset($buttons[1]);
			}
		}
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];
			$attr['type'] = 'readonly';
			$attr['value'] = $endDate->format('d-m-Y');
			$attr['attr']['value'] = $endDate->format('d-m-Y');
			return $attr;
		}
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
            if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
				$startDate = $request->data[$this->alias()]['start_date'];
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $startDate->format('d-m-Y');
			} else {
				$endDate = $request->data[$this->alias()]['end_date'];

				if (!empty($endDate)) {
    				$endDate = new Date(date('Y-m-d', strtotime($endDate)));
    				$request->data[$this->alias()]['end_date'] = $endDate;
    			}

    			if (array_key_exists('startDate', $request->query)) {
	                $attr['value'] = $request->query['startDate'];
	                $attr['attr']['value'] = $request->query['startDate'];
	            }
			}

            return $attr;
		}
	}

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
				$attr['type'] = 'readonly';
			}
			return $attr;
		}
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$type = $request->data[$this->alias()]['type'];
			$attr['type'] = 'readonly';
			$typeName = "";
			switch ($type) {
				case self::ADMISSION:
					$typeName = "Admission";
					break;
				case self::TRANSFER:
					$typeName = "Transfer";
					break;
			}
			$attr['attr']['value'] = $typeName;
			return $attr;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$newItem = [];

		$status = $this->get($entity->id)->status;
		if ($status == self::NEW_REQUEST) {
			if (isset($buttons['view'])) {
				$newItem['view'] = $buttons['view'];
			}
			if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				if (isset($buttons['edit'])) {
					$newItem['edit'] = $buttons['edit'];
				}
			}
		} else {
			if (isset($buttons['view'])) {
				$newItem['view'] = $buttons['view'];
			}
		}
		$buttons = $newItem;

		return $buttons;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'index') {
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['plugin'] = 'Institution';
			$toolbarButtons['back']['url']['controller'] = 'Institutions';
			$toolbarButtons['back']['url']['action'] = 'Students';
			$toolbarButtons['back']['url'][0] = 'index';
			$toolbarButtons['back']['attr'] = $attr;
		}

		if ($action == 'edit') {
			$toolbarButtons['back']['url'][0] = 'index';
			if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
				$toolbarButtons['back']['url']['action']= 'index';
				unset($toolbarButtons['back']['url'][0]);
			}
			unset($toolbarButtons['back']['url'][1]);
		} else if ($action == 'view') {
			if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST) {
				unset($toolbarButtons['edit']);
			}
		}
	}

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $errors = $entity->errors();

        if (empty($errors)) {
            
            $this->approveAdmission($entity, $data, 'Approval');

			// To redirect back to the student admission if it is not access from the workbench
			$urlParams = $this->ControllerAction->url('index');
			$plugin = false;
			$controller = 'Dashboard';
			$action = 'index';
			if ($urlParams['controller'] == 'Institutions') {
				$plugin = 'Institution';
				$controller = 'Institutions';
				$action = 'StudentAdmission';
			}

			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
        } else {
			// required for validation to work
			$process = function($model, $entity) {
				return false;
			};

			return $process;
		}
	}

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //this logic is meant for auto approve admission and add student into the institution when the creator has 'Student Admission -> Execute' permission
        if ($entity->isNew()) {
            
            $errors = $entity->errors();
            if (empty($errors)) {
                
                $admissionExecutePermission = true;    
                //check for super admin
                $superAdmin = Hash::check($_SESSION['Auth'], 'User.super_admin');

                if (!$superAdmin) {
                    $admissionExecutePermission = Hash::check($_SESSION['Permissions'], 'Institutions.StudentAdmission.execute');
                }
                
                if ($admissionExecutePermission){
                    $this->approveAdmission($entity, [], 'AutoApprove');
                }
            }
        } 
    }

    private function approveAdmission($entity, $data, $caller)
    {
        $selectedClassId = $entity->institution_class_id;

        if ($caller == 'Approval') {
            $entity->comment = $data['StudentAdmission']['comment'];
        }
        
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $newSchoolId = $entity->institution_id;
        $previousSchoolId = $entity->previous_institution_id;
        $studentId = $entity->student_id;
        $periodId = $entity->academic_period_id;
        $gradeId = $entity->education_grade_id;
        $newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);

        if (!is_null($selectedClassId)) {
            $classData = [];
            $classData['student_id'] = $studentId;
            $classData['education_grade_id'] = $gradeId;
            $classData['institution_class_id'] = $selectedClassId;
            $classData['student_status_id'] = $statuses['CURRENT'];
            $classData['institution_id'] = $newSchoolId;
            $classData['academic_period_id'] = $periodId;
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $InstitutionClassStudents->autoInsertClassStudent($classData);
        }

        $studentChecking = true;
        if ($caller == 'Approval') {
            $validateEnrolledInAnyInstitutionResult = $Students->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['targetInstitutionId' => $newSchoolId]);
            if (!empty($validateEnrolledInAnyInstitutionResult)) {
                $this->Alert->error($validateEnrolledInAnyInstitutionResult, ['type' => 'message']);
                $studentChecking = false;
            } else if ($Students->completedGrade($gradeId, $studentId)) {
                $this->Alert->error('Institution.Students.student_name.ruleStudentNotCompletedGrade');
                $studentChecking = false;
            }
        }

        if ($caller == 'AutoApprove' || $studentChecking) {

            if (!empty($data)) {
                $startDate = $data[$this->alias()]['start_date']; 
            } else {
                $startDate = $entity->start_date; 
            }
            
            $startDate = date('Y-m-d', strtotime($startDate));

            // add the student to the new school
            $entityData = [
                'institution_id' => $newSchoolId,
                'student_id' => $studentId,
                'academic_period_id' => $periodId,
                'education_grade_id' => $gradeId,
                'student_status_id' => $statuses['CURRENT']
            ];
            $entityData['start_date'] = $startDate;
            $entityData['end_date'] = $entity->end_date->format('Y-m-d');
            $newEntity = $Students->newEntity($entityData);
            if ($Students->save($newEntity)) {
                if ($caller == 'Approval') {
                    $this->Alert->success('StudentAdmission.approve');
                }

                $EducationGradesTable = TableRegistry::get('Education.EducationGrades');

                $educationSystemId = $EducationGradesTable->getEducationSystemId($entity->education_grade_id);
                $educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

                $conditions = [
                    'student_id' => $entity->student_id,
                    'status' => self::NEW_REQUEST,
                    'education_grade_id IN' => $educationGradesToUpdate
                ];

                // Reject all other new pending admission / transfer application entry of the
                // same student for the same academic period
                $this->updateAll(
                    ['status' => self::REJECTED],
                    [$conditions]
                );

                // Update the status of the admission to be approved
                $entity->start_date = $startDate;
                $entity->status = self::APPROVED;
                if (!$this->save($entity, ['validate' => false])) {
                    $this->log($entity->errors(), 'debug');
                }
            } else {
                if ($caller == 'Approval') {
                    $this->Alert->error('general.edit.failed');
                }
                $this->log($newEntity->errors(), 'debug');
            }
        }
    }

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

		$entity->comment = $data['StudentAdmission']['comment'];
		$this->updateAll(
			['status' => self::REJECTED, 'comment' => $entity->comment],
			['id' => $entity->id]);

		$this->Alert->success('StudentAdmission.reject');

		// To redirect back to the student admission if it is not access from the workbench
		$urlParams = $this->ControllerAction->url('index');
		$plugin = false;
		$controller = 'Dashboard';
		$action = 'index';
		if ($urlParams['controller'] == 'Institutions') {
			$plugin = 'Institution';
			$controller = 'Institutions';
			$action = 'StudentAdmission';
		}

		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
	}

	public function findWorkbench(Query $query, array $options) {
		$controller = $options['_controller'];
		$controller->loadComponent('AccessControl');

		$session = $controller->request->session();
		$AccessControl = $controller->AccessControl;

		$isAdmin = $session->read('Auth.User.super_admin');
		$userId = $session->read('Auth.User.id');

		$where = [
			$this->aliasField('status') => self::NEW_REQUEST,
			$this->aliasField('type') => self::ADMISSION
		];

		if (!$isAdmin) {
			if ($AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
				$institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

				if (empty($institutionIds)) {
					// return empty list if the user does not have access to any schools
					return $query->where([$this->aliasField('id') => -1]);
				} else {
					$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
				}
			} else {
			// 	// return empty list if the user does not permission to approve Student Admission
				return $query->where([$this->aliasField('id') => -1]);
			}
		}

		$query
			->select([
				$this->aliasField('id'),
				$this->aliasField('institution_id'),
				$this->aliasField('modified'),
				$this->aliasField('created'),
				$this->Users->aliasField('openemis_no'),
				$this->Users->aliasField('first_name'),
				$this->Users->aliasField('middle_name'),
				$this->Users->aliasField('third_name'),
				$this->Users->aliasField('last_name'),
				$this->Users->aliasField('preferred_name'),
				$this->Institutions->aliasField('code'),
				$this->Institutions->aliasField('name'),
				$this->CreatedUser->aliasField('openemis_no'),
				$this->CreatedUser->aliasField('first_name'),
				$this->CreatedUser->aliasField('middle_name'),
				$this->CreatedUser->aliasField('third_name'),
				$this->CreatedUser->aliasField('last_name'),
				$this->CreatedUser->aliasField('preferred_name')
			])
			->contain([$this->Users->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
			->where($where)
			->order([$this->aliasField('created') => 'DESC'])
			->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
					$url = [
						'plugin' => false,
						'controller' => 'Dashboard',
						'action' => 'StudentAdmission',
						'edit',
						$this->paramsEncode(['id' => $row->id])
					];

					if (is_null($row->modified)) {
						$receivedDate = $this->formatDate($row->created);
					} else {
						$receivedDate = $this->formatDate($row->modified);
					}

					$row['url'] = $url;
	    			$row['status'] = __('Pending For Approval');
                    $row['request_title'] = sprintf(__('Admission of student %s'), $row->user->name_with_id);
	    			$row['institution'] = $row->institution->code_name;
	    			$row['received_date'] = $receivedDate;
	    			$row['requester'] = $row->created_user->name_with_id;

					return $row;
				});
			});

		return $query;
	}

    public function getPendingRecords($institutionId = null)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('status') => self::NEW_REQUEST,
                $this->aliasField('institution_id') => $institutionId,
            ])
            ->count()
        ;

        return $count;
    }
}
