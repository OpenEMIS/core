<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class TransferApprovalsTable extends ControllerActionTable
{
    const NEW_REQUEST = 0;
    const APPROVED = 1;
    const REJECTED = 2;

    // Type status for admission
    const TRANSFER = 2;
    const ADMISSION = 1;

    use MessagesTrait;

    public function initialize(array $config)
    {
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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', false]
            ])
            ->add('start_date', 'ruleCheckProgrammeEndDateAgainstStudentStartDate', [
                'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
            ])
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id']
            ])
            ->add('institution_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
        ;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['ControllerAction.Model.associated'] = 'associated';

        return $events;
    }

    public function associated(Event $event, ArrayObject $extra)
    {
        // back button
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $this->url('edit');
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back button

        $this->Alert->error('TransferRequests.unableToTransfer', ['reset' => true]);
        $sessionKey = $this->registryAlias() . '.associated';

        $currentEntity = $this->Session->read($sessionKey);
        $dataBetweenDate = $this->Session->read($sessionKey.'Data');

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $this->fields = []; // reset all the fields

        $this->field('student');
        $this->field('requested_date');
        $this->field('associated_records', ['type' => 'readonly']);

        $entity = $this->newEntity();

        $this->controller->set('data', $entity);
        return $entity;
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        //set current date to start date
        //$entity->start_date = new Date(); //remove the wrong logic that automate the start date as today's date.

        // Set all selected values only
        $this->request->data[$this->alias()]['transfer_status'] = $entity->status;
        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        $this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
        $this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
        $this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
        $this->request->data[$this->alias()]['new_education_grade_id'] = $entity->new_education_grade_id;
        $this->request->data[$this->alias()]['start_date'] = ''; //index start_date is needed when the value being given later on.
        $this->request->data[$this->alias()]['end_date'] = $entity->end_date;
        $this->request->data[$this->alias()]['requested_date'] = $entity->requested_date;
        $this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
        $this->request->data[$this->alias()]['status'] = $entity->status;
    }

    private function updateStudentStatus($entity, $oldStatus, $newStatus, $newEndDate)
    {
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $entity->end_date = $newEndDate;
        $entity->student_status_id = $statuses[$newStatus];
        $Students->save($entity);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $errors = $entity->errors();

        if (empty($errors)) {
            $Students = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $EducationGradesTable = TableRegistry::get('Education.EducationGrades');
            $TransferRequests = TableRegistry::get('Institution.TransferRequests');

            $dataBetweenDate = $TransferRequests->getDataBetweenDate($data, $this->alias());
            $statuses = $StudentStatuses->findCodeList();

            $newSchoolId = $entity->institution_id;
            $previousSchoolId = $entity->previous_institution_id;
            $studentId = $entity->student_id;
            $periodId = $entity->academic_period_id;
            $prevGradeId = $entity->education_grade_id; //get the previous grade of the student.
            $gradeId = $entity->new_education_grade_id;
            $newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);

            $validateEnrolledInAnyInstitutionResult = $Students->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['excludeInstitutions' => [$previousSchoolId], 'targetInstitutionId' => $newSchoolId]);
            if (!empty($validateEnrolledInAnyInstitutionResult)) {
                $this->Alert->error($validateEnrolledInAnyInstitutionResult, ['type' => 'message']);
            } else if ($Students->completedGrade($gradeId, $studentId)) {
                $this->Alert->error('Institution.Students.student_name.ruleStudentNotCompletedGrade');
            } else if (count($dataBetweenDate)) {
                $url = $this->url('associated');
                $session = $this->Session;
                $session->write($this->registryAlias().'.associated', $entity);
                $session->write($this->registryAlias().'.associatedData', $dataBetweenDate);

                return $this->controller->redirect($url);
            } else { // if not exists
                $startDate = $data[$this->alias()]['start_date'];
                $startDate = date('Y-m-d', strtotime($startDate));

                $newEndDate = (new Date($startDate))->modify('-1 day'); //set the end date of the 'transfered' record to a day before the start date of the 'enrolled' record.
                $newEndDate = date('Y-m-d', strtotime($newEndDate));

                // add the student to the new school
                $newData = [
                    'institution_id' => $newSchoolId,
                    'student_id' => $studentId,
                    'academic_period_id' => $periodId,
                    'education_grade_id' => $gradeId,
                    'student_status_id' => $statuses['CURRENT']
                ];

                $existingStudentEntity = $Students->find()->where([
                        $Students->aliasField('institution_id') => $previousSchoolId,
                        $Students->aliasField('student_id') => $studentId,
                        $Students->aliasField('academic_period_id') => $periodId,
                        $Students->aliasField('education_grade_id') => $prevGradeId,
                        $Students->aliasField('student_status_id') => $statuses['CURRENT']
                    ])
                    ->first();

                // if cannot be found (perhaps is a promoted/graduated transfer record). then dont change the record
                if (!empty($existingStudentEntity)) {
                    $prevEndDate = $existingStudentEntity->end_date;
                    $newData['previous_institution_student_id'] = $existingStudentEntity->id;
                    $this->updateStudentStatus($existingStudentEntity, 'CURRENT', 'TRANSFERRED', $newEndDate);
                }

                $newData['start_date'] = $startDate;
                $newData['end_date'] = $entity->end_date->format('Y-m-d');
                $newEntity = $Students->newEntity($newData);
                if ($Students->save($newEntity)) {
                    $classId = $data[$this->alias()]['institution_class'];
                    if (!empty($classId)) {
                        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
                        $institutionClassStudentObj = [
                            'student_id' => $newEntity->student_id,
                            'student_status_id' => $newEntity->student_status_id,
                            'institution_class_id' => $classId,
                            'education_grade_id' => $newEntity->education_grade_id,
                            'student_status_id' => $newEntity->student_status_id,
                            'institution_id' => $newEntity->institution_id,
                            'academic_period_id' => $newEntity->academic_period_id
                        ];
                        $InstitutionClassStudentsTable->autoInsertClassStudent($institutionClassStudentObj);
                    }

                    $this->Alert->success('TransferApprovals.approve');

                    $EducationGradesTable = TableRegistry::get('Education.EducationGrades');

                    $educationSystemId = $EducationGradesTable->getEducationSystemId($gradeId);
                    $educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

                    $conditions = [
                        'student_id' => $studentId,
                        'status' => self::NEW_REQUEST,
                        'education_grade_id IN' => $educationGradesToUpdate
                    ];

                    // Reject all other new pending admission / transfer application entry of the
                    // same student for the same academic period
                    $this->updateAll(
                        ['status' => self::REJECTED],
                        [$conditions]
                    );

                    // finally update the transfer request to become approved
                    $entity->start_date = $startDate;
                    $entity->status = self::APPROVED;
                    if (!$this->save($entity)) {
                        $this->Alert->error('general.edit.failed');
                        $this->log($entity->errors(), 'debug');
                    }
                } else {
                    if (!empty($existingStudentEntity)) {
                        $this->updateStudentStatus($existingStudentEntity, 'TRANSFERRED', 'CURRENT', $prevEndDate);
                    }
                    $this->Alert->error('general.edit.failed');
                    $this->log($newEntity->errors(), 'debug');
                }
            }

            // To redirect back to the student admission if it is not access from the workbench
            $urlParams = $this->url('index');
            $plugin = false;
            $controller = 'Dashboard';
            $action = 'index';
            if ($urlParams['controller'] == 'Institutions') {
                $plugin = 'Institution';
                $controller = 'Institutions';
                $action = 'StudentAdmission';
            }

            return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
        } else {
            // required for validation to work
            $process = function ($model, $entity) {
                return false;
            };

            return $process;
        }
    }

    private function addSections()
    {
        $this->field('transfer_status_header', ['type' => 'section', 'title' => __('Transfer Status')]);
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->type != self::TRANSFER) {
            $event->stopPropagation();
            return $this->controller->redirect(['controller' => 'Institutions', 'action' => 'Students', 'plugin'=>'Institution']);
        }
        $this->addSections();
        $this->field('transfer_status');
        $this->field('requested_date');
        $this->field('student');
        $this->field('student_id');
        $this->field('institution_id');
        $this->field('academic_period_id', ['type' => 'hidden']);
        $this->field('education_grade_id');
        $this->field('new_education_grade_id', [
            'type' => 'readonly',
            'attr' => [
                'value' => $this->NewEducationGrades->get($entity->new_education_grade_id)->programme_grade_name
            ]]);
        $this->field('status', ['type' => 'hidden']);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date');
        $this->field('student_transfer_reason_id', ['type' => 'select']);
        $this->field('comment');
        $this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->previous_institution_id)->code_name]]);
        $this->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);
        $this->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
        $this->field('institution_class', ['type' => 'select']);
        $this->field('institution_class_id', ['visible' => false]);

        $this->setFieldOrder([
            'transfer_status_header', 'created', 'transfer_status', 'requested_date',
            'existing_information_header', 'student', 'previous_institution_id', 'academic_period_id', 'education_grade_id',
            'new_information_header', 'institution_id', 'new_education_grade_id', 'institution_class',
            'status', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
        $urlParams = $this->url('edit');
        if ($urlParams['controller'] == 'Dashboard') {
            $this->Navigation->addCrumb('Transfer Approvals', $urlParams);
        }
    }

    public function onUpdateFieldInstitutionClass(Event $event, array $attr, $action, $request)
    {
        $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
        $institutionId = $this->request->data[$this->alias()]['institution_id'];
        $newEducationGradeId = $this->request->data[$this->alias()]['new_education_grade_id'];
        $listOfClasses = $this->Institutions->find('list', [
                'keyField' => 'class_id',
                'valueField' => 'class_name'
            ])
            ->matching('InstitutionClasses.ClassGrades')
            ->where([
                'ClassGrades.education_grade_id' => $newEducationGradeId,
                'InstitutionClasses.academic_period_id' => $academicPeriodId,
                $this->Institutions->aliasField('id') => $institutionId
            ])
            ->select(['class_id' => 'InstitutionClasses.id', 'class_name' => 'InstitutionClasses.name'])
            ->hydrate(false)
            ->toArray()
            ;
        if (count($listOfClasses) == 0) {
            $options = ['' => __('No Available Classes')];
        } else {
            $options = $listOfClasses;
        }
        $attr['options'] = $options;
        return $attr;
    }

    public function viewAfterAction($event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['status'] = $entity->status;
        $this->field('type', ['visible'=>     false]);
        $this->setFieldOrder([
            'created', 'status', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'start_date', 'end_date', 'comment'
        ]);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
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

    public function onUpdateFieldTransferStatus(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $transferStatus = $request->data[$this->alias()]['transfer_status'];

            $attr['type'] = 'readonly';
            $attr['value'] = $transferStatus;
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

    public function onUpdateFieldStudent(Event $event, array $attr, $action, $request)
    {
        if ($action == 'associated') {
            $sessionKey = $this->registryAlias() . '.associated';
            $entity = $this->Session->read($sessionKey);
            $selectedStudent = $entity->student_id;
        } else {
            $selectedStudent = $request->data[$this->alias()]['student_id'];
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        $selectedStudent = $request->data[$this->alias()]['student_id'];

        $attr['type'] = 'hidden';
        $attr['attr']['value'] = $selectedStudent;

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $selectedInstitution = $request->data[$this->alias()]['institution_id'];

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->code_name;
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $selectedGrade = $request->data[$this->alias()]['education_grade_id'];

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->EducationGrades->get($selectedGrade)->programme_grade_name;
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            // If it is not a new request, disable this field
            if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST || (!$this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
                $startDate = $request->data[$this->alias()]['start_date'];
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $startDate->format('d-m-Y');
                return $attr;
            }

            $selectedPeriod = $request->data[$this->alias()]['academic_period_id'];

            $academicPeriod = $this->AcademicPeriods->get($selectedPeriod);
            $periodStartDate = $academicPeriod->start_date;
            $periodEndDate = $academicPeriod->end_date;

            $studentStartDate = $attr['entity']->start_date;
            $studentEndDate = $attr['entity']->end_date;

            $optionStartDate = $periodStartDate;
            $optionEndDate = $periodEndDate;

            if ($studentStartDate >= $periodStartDate) {
                $optionStartDate = $studentStartDate;
            }

            if ($studentEndDate <= $periodEndDate) {
                $optionEndDate = $studentEndDate;
            }

            // set the date options
            $attr['date_options'] = ['startDate' => $periodStartDate->format('d-m-Y'), 'endDate' => $periodEndDate->format('d-m-Y')];
            $attr['date_options']['todayBtn'] = false; //since we limit the start date, should as well hide the 'today' button so no extra checking function needed
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $endDate = $request->data[$this->alias()]['end_date'];
            $attr['type'] = 'readonly';
            $attr['value'] = $endDate->format('d-m-Y');
            $attr['attr']['value'] = $endDate->format('d-m-Y');
        }

        return $attr;
    }

    public function onUpdateFieldRequestedDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $requestedDate = $request->data[$this->alias()]['requested_date'];
        } else if ($action == 'associated') {
            $sessionKey = $this->registryAlias() . '.associated';
            $entity = $this->Session->read($sessionKey);
            $requestedDate = $entity->start_date;
        }

        $attr['type'] = 'readonly';
        $attr['value'] = $requestedDate->format('d-m-Y');
        $attr['attr']['value'] = $requestedDate->format('d-m-Y');

        return $attr;
    }

    public function onUpdateFieldAssociatedRecords(Event $event, array $attr, $action, $request)
    {
        $dataBetweenDate = [];
        switch ($action) {
            case 'associated':
                $sessionKey = $this->registryAlias() . '.associatedData';
                if ($this->Session->check($sessionKey)) {
                    $dataBetweenDate = $this->Session->read($sessionKey);
                }
                break;
        }

        $attr['type'] = 'element';
        $attr['element'] = 'Institution.StudentTransfer/associatedRecords';
        $attr['data'] = $dataBetweenDate;

        return $attr;
    }

    public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $selectedReason = $request->data[$this->alias()]['student_transfer_reason_id'];

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->StudentTransferReasons->get($selectedReason)->name;
        }

        return $attr;
    }

    public function onUpdateFieldComment(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['attr']['disabled'] = 'disabled';
        }

        return $attr;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'edit' || $action == 'view') {
            if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
                $toolbarButtons['back']['url']['action']= 'index';
                unset($toolbarButtons['back']['url'][0]);
                unset($toolbarButtons['back']['url'][1]);
            } else if ($toolbarButtons['back']['url']['controller']=='Institutions') {
                $toolbarButtons['back']['url']['action']= 'StudentAdmission';
                unset($toolbarButtons['back']['url'][0]);
                unset($toolbarButtons['back']['url'][1]);
            }
        }

        if ($action == 'view') {
            if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST || (!$this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
                unset($toolbarButtons['edit']);
            }
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'edit') {
            // If the status is new application then display the approve and reject button,
            // if not remove the button just in case the user gets to access the edit page
            if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Approve');

                $buttons[1] = [
                    'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
                    'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
                ];
            } else {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        } else if ($this->action == 'associated') {
            $sessionKey = $this->registryAlias() . '.associatedData';
            if ($this->Session->check($sessionKey) && !empty($this->Session->read($sessionKey))) {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        }
    }

    public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // Update status to Current in previous school
        $institutionId = $entity->previous_institution_id;
        $selectedStudent = $entity->student_id;
        $selectedPeriod = $entity->academic_period_id;
        $selectedGrade = $entity->education_grade_id;

        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $currentStatus = $StudentStatuses->getIdByCode('CURRENT');

        $Students = TableRegistry::get('Institution.Students');
        $Students->updateAll(
            ['student_status_id' => $currentStatus],
            [
                'institution_id' => $institutionId,
                'student_id' => $selectedStudent,
                'academic_period_id' => $selectedPeriod,
                'education_grade_id' => $selectedGrade
            ]
        );
        // End

        // Update status to 2 => reject
        $this->updateAll(['status' => self::REJECTED], ['id' => $entity->id]);
        // End

        $this->Alert->success('TransferApprovals.reject');

        // To redirect back to the student admission if it is not access from the workbench
        $urlParams = $this->url('index');
        $plugin = false;
        $controller = 'Dashboard';
        $action = 'index';
        if ($urlParams['controller'] == 'Institutions') {
            $plugin = 'Institution';
            $controller = 'Institutions';
            $action = 'StudentAdmission';
        }

        $entity = $this->newEntity();
        $this->controller->set('data', $entity);

        $event->stopPropagation();
        return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $controller->loadComponent('AccessControl');
        $controller->loadComponent('ControllerAction.ControllerAction');

        $session = $controller->request->session();
        $AccessControl = $controller->AccessControl;

        $isAdmin = $session->read('Auth.User.super_admin');
        $userId = $session->read('Auth.User.id');

        $where = [
            $this->aliasField('status') => self::NEW_REQUEST,
            $this->aliasField('type') => self::TRANSFER
        ];

        if (!$isAdmin) {
            if ($AccessControl->check(['Institutions', 'TransferApprovals', 'edit'])) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

                if (empty($institutionIds)) {
                    // return empty list if the user does not have access to any schools
                    return $query->where([$this->aliasField('id') => -1]);
                } else {
                    $where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
                }
            } else {
                // return empty list if the user does not permission to do Transfer Approvals
                return $query->where([$this->aliasField('id') => -1]);
            }
        }

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('previous_institution_id'),
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
                $this->PreviousInstitutions->aliasField('code'),
                $this->PreviousInstitutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->Institutions->alias(), $this->PreviousInstitutions->alias(), $this->CreatedUser->alias()])
            ->where($where)
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => false,
                        'controller' => 'Dashboard',
                        'action' => 'TransferApprovals',
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
                    $row['request_title'] = sprintf(__('Transfer of student %s from %s'), $row->user->name_with_id, $row->previous_institution->code_name);
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
