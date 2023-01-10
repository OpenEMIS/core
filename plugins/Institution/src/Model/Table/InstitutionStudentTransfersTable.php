<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;
use Cake\Network\Session;
use Cake\Log\Log;

// This file serves as an abstract class for StudentTransferIn and StudentTransferOut
class InstitutionStudentTransfersTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    public function initialize(array $config)
    {
        $this->table('institution_student_transfers');
        parent::initialize($config);

        // Mandatory data
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        // New institution data
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        // Previous institution data
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('PreviousAcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'previous_academic_period_id']);
        $this->belongsTo('PreviousEducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'previous_education_grade_id']);
        $this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons', 'foreignKey' => 'student_transfer_reason_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('User.AdvancedNameSearch');
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onTransferStudent',
            'text' => 'Approval of Student Transfer',
            'description' => 'Performing this action will transfer the student to the receiving institution.',
            'method' => 'onTransferStudent',
            'unique' => true
        ],
        [
            'value' => 'Workflow.onCancel',
            'text' => 'Cancellation of Student Transfer',
            'description' => 'Performing this action will return the student to the sending institution.',
            'method' => 'onCancel',
            'unique' => true
        ]
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Workflow.checkIfCanAddButtons'] = 'checkIfCanAddButtons';
        $events['Workflow.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['UpdateAssignee.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['Workflow.setAutoAssignAssigneeFlag'] = 'setAutoAssignAssigneeFlag';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onTransferStudent(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        // find previous student record (could be enrolled/promoted/graduated status)
        $previousStudentRecord = $Students->find()
            ->where([
                $Students->aliasField('student_id') => $entity->student_id,
                $Students->aliasField('institution_id') => $entity->previous_institution_id,
                $Students->aliasField('academic_period_id') => $entity->previous_academic_period_id,
                $Students->aliasField('education_grade_id') => $entity->previous_education_grade_id,
                $Students->aliasField('student_status_id IN ') => [$statuses['CURRENT'], $statuses['PROMOTED'], $statuses['GRADUATED']]
            ])
            ->first();
        if (!empty($previousStudentRecord)) {
            //POCOR-6230 Starts
            $session = new Session();
            $userId = $session->read('Auth.User.id');
            //first check the student has already enrolled in same/other institution in any academic period
            $enrolledStudentRecord = $Students->find()
                ->where([
                    $Students->aliasField('student_id') => $entity->student_id,
                    $Students->aliasField('student_status_id') => $statuses['CURRENT']
                ])
                ->first();
            if(!empty($enrolledStudentRecord)){
                //change existing record enrolled student status into transfered
                $Students->updateAll(
                    ['student_status_id' => $statuses['TRANSFERRED'], 'modified_user_id'=> $userId, 'modified'=> Time::now()],
                    ['id' => $enrolledStudentRecord->id]
                );
            } //POCOR-6230 Ends
            
            //POCOR-6362 starts
            if($previousStudentRecord->student_status_id == $statuses['PROMOTED'] || $previousStudentRecord->student_status_id == $statuses['GRADUATED'] || $previousStudentRecord->student_status_id == $statuses['CURRENT']){
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriod = $AcademicPeriods
                        ->find()
                        ->where([
                           $AcademicPeriods->aliasField('id') => $entity->academic_period_id
                        ])
                        ->first();
                
            }//POCOR-6362 ends
            // add new student record in the new institution
            $newStudent = [
                'student_status_id' => $statuses['CURRENT'],
                'student_id' => $entity->student_id,
                'education_grade_id' => $entity->education_grade_id,
                'academic_period_id' => $entity->academic_period_id,
                'start_date' => (($previousStudentRecord->student_status_id == $statuses['PROMOTED'] || $previousStudentRecord->student_status_id == $statuses['GRADUATED'] || $previousStudentRecord->student_status_id == $statuses['CURRENT']) && empty($entity->start_date)) ? $academicPeriod->start_date : $entity->start_date,//POCOR-6362 
                'end_date' => (($previousStudentRecord->student_status_id == $statuses['PROMOTED'] || $previousStudentRecord->student_status_id == $statuses['GRADUATED'] || $previousStudentRecord->student_status_id == $statuses['CURRENT']) && empty($entity->end_date)) ? $academicPeriod->end_date : $entity->end_date,//POCOR-6362
                'institution_id' => $entity->institution_id,
                'previous_institution_student_id' => $previousStudentRecord->id
            ];
            if (!empty($entity->institution_class_id)) {
                $newStudent['class'] = $entity->institution_class_id;
            }
            $newStudentEntity = $Students->newEntity($newStudent);
            if ($Students->save($newStudentEntity)) {
                // end previous student record (if not promoted/graduated status)
                //POCOR-6362 apply condition for PROMOTED and GRADUATED status
                if ($previousStudentRecord->student_status_id == $statuses['CURRENT']) {
                    $previousStudentRecord->end_date = $entity->requested_date;
                    $previousStudentRecord->student_status_id = $statuses['TRANSFERRED'];
                    $Students->save($previousStudentRecord);
                } else if($previousStudentRecord->student_status_id == $statuses['PROMOTED']){
                    $previousStudentRecord->end_date = $entity->requested_date;
                    $previousStudentRecord->student_status_id = $statuses['PROMOTED'];
                    $Students->save($previousStudentRecord);
                } /*POCOR-6542 starts*/ else if ($previousStudentRecord->student_status_id == $statuses['GRADUATED']) {
                    $previousStudentRecord->end_date = $entity->requested_date;
                    $previousStudentRecord->student_status_id = $statuses['GRADUATED'];
                    $Students->save($previousStudentRecord);
                } /*POCOR-6542 ends*/
            }
        } 
    }

    public function onCancel(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $newStudentRecord = $Students->find()
            ->where([
                $Students->aliasField('institution_id') => $entity->institution_id,
                $Students->aliasField('student_id') => $entity->student_id,
                $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                $Students->aliasField('education_grade_id') => $entity->education_grade_id,
                $Students->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->first();

        $prevStudentId = null;
        if (!empty($newStudentRecord)) {
            // delete student record in the new institution
            $prevStudentId = $newStudentRecord->previous_institution_student_id;
            $Students->delete($newStudentRecord);

            if (!is_null($prevStudentId)) {
                $previousStudentRecord = $Students->get($prevStudentId);
            } else {
                // find previous student record (could be enrolled/promoted/graduated status)
                $previousStudentRecord = $Students->find()
                    ->where([
                        $Students->aliasField('student_id') => $entity->student_id,
                        $Students->aliasField('institution_id') => $entity->previous_institution_id,
                        $Students->aliasField('academic_period_id') => $entity->previous_academic_period_id,
                        $Students->aliasField('education_grade_id') => $entity->previous_education_grade_id,
                        $Students->aliasField('student_status_id IN ') => [$statuses['TRANSFERRED'], $statuses['PROMOTED'], $statuses['GRADUATED']]
                    ])
                    ->first();
            }

            if ($previousStudentRecord) {
                if ($previousStudentRecord->student_status_id == $statuses['TRANSFERRED']) {
                    // update previous student record back to enrolled status (if status is transferred)
                    $academicPeriod = $this->AcademicPeriods->get($entity->academic_period_id);
                    if (!empty($academicPeriod)) {
                        $previousStudentRecord->end_date = $academicPeriod->end_date;
                    }
                    $previousStudentRecord->student_status_id = $statuses['CURRENT'];
                    $Students->save($previousStudentRecord);
                }
            }
        }
    }

    // to determine if workflow buttons should be shown in view page
    public function checkIfCanAddButtons(Event $event, Entity $entity)
    {
        $canAddButtons = false;
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        if ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) {
            $canAddButtons = $this->Institutions->isActive($entity->institution_id);
        } else if ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id) {
            $canAddButtons = $this->Institutions->isActive($entity->previous_institution_id);
        }
        return $canAddButtons;
    }

    // to get the correct list of assignees in modal and in UpdateAssigneeShell
    public function onSetCustomAssigneeParams(Event $event, Entity $entity, $params)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');

        if ($institutionOwner == self::INCOMING) {
            $params['institution_id'] = $entity->institution_id;
        } else if ($institutionOwner == self::OUTGOING) {
            $params['institution_id'] = $entity->previous_institution_id;
        }
        return $params;
    }

    // to determine if assignee list or 'Auto Assign' should be shown
    public function setAutoAssignAssigneeFlag(Event $event, Entity $action)
    {
        $currentInstitutionOwner = $this->getWorkflowStepsParamValue($action->workflow_step_id, 'institution_owner');
        $nextInstitutionOwner = $this->getWorkflowStepsParamValue($action->next_workflow_step_id, 'institution_owner');
        return $currentInstitutionOwner != $nextInstitutionOwner ? 1 : 0;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
        $studentsUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $this->paramsEncode(['id' => $institutionId]), 'action' => 'Students'];
        $previousTitle = Inflector::humanize(Inflector::underscore($this->alias()));

        $Navigation->substituteCrumb($previousTitle, 'Students', $studentsUrl);
        $Navigation->addCrumb($previousTitle);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('all_visible', ['type' => 'hidden']);
    }

    // for index
    public function onGetStatusId(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->status->name . '</span>';
        } else {
            return '<span class="status past">' . $entity->status->name . '</span>';
        }
    }

    // for view
    public function onGetWorkflowStatus(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->workflow_status . '</span>';
        } else {
            return '<span class="status past">' . $entity->workflow_status . '</span>';
        }
    }

    public function onGetPreviousInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution')) {
            $value = $entity->institution->code_name;
        }
        return $value;
    }

    public function onGetPreviousEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_education_grade')) {
            $value = $entity->previous_education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_grade')) {
            $value = $entity->education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_id') {
            return __('New Institution');
        } else if ($field == 'previous_institution_id') {
            return __('Current Institution');
        } else if ($field == 'previous_education_grade_id') {
            return __('Education Grade');
        } else if ($field == 'previous_academic_period_id') {
            return __('Academic Period');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // if the record changes institution_owner at least once, both institutions should be able to see the record
        if (!$entity->isNew() && $entity->dirty('status_id')) {
            if (!$entity->all_visible) {
                $currentInstitutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
                $previousInstitutionOwner = $this->getWorkflowStepsParamValue($entity->getOriginal('status_id'), 'institution_owner');

                if ($currentInstitutionOwner != $previousInstitutionOwner) {
                    $this->updateAll(['all_visible' => 1], ['id' => $entity->id]);
                }
            }
        }
        else{
            $this->updateAll(['all_visible' => 1], ['id' => $entity->id]);            
        }

        //POCOR-6995 start
        $classId = $entity->institution_class_id;
        $institutionClass =  TableRegistry::get('Institution.InstitutionClasses');
        if(!empty($classId)){
            $bodyData = $institutionClass->find('all',
                            [ 'contain' => [
                                'Institutions',
                                'EducationGrades',
                                'Staff',
                                'AcademicPeriods',
                                'InstitutionShifts',
                                'InstitutionShifts.ShiftOptions',
                                'ClassesSecondaryStaff.SecondaryStaff',
                                'Students'
                            ],
                    ])->where([
                        $institutionClass->aliasField('id') => $classId
                    ]);

                $grades = $gradeId = $secondaryTeachers = $students = [];

                if (!empty($bodyData)) {
                    foreach ($bodyData as $key => $value) {
                        $capacity = $value->capacity;
                        $shift = $value->institution_shift->shift_option->name;
                        $academicPeriod = $value->academic_period->name;
                        $homeRoomteacher = $value->staff->openemis_no;
                        $institutionId = $value->institution->id;
                        $institutionName = $value->institution->name;
                        $institutionCode = $value->institution->code;
                        $institutionClassId = $value->id;
                        $institutionClassName = $value->name;

                        if(!empty($value->education_grades)) {
                            foreach ($value->education_grades as $key => $gradeOptions) {
                                $grades[] = $gradeOptions->name;
                                $gradeId[] = $gradeOptions->id;
                            }
                        }

                        if(!empty($value->classes_secondary_staff)) {
                            foreach ($value->classes_secondary_staff as $key => $secondaryStaffs) {
                                $secondaryTeachers[] = $secondaryStaffs->secondary_staff->openemis_no;
                            }
                        }

                        $maleStudents = 0;
                        $femaleStudents = 0;
                        if(!empty($value->students)) {
                            foreach ($value->students as $key => $studentsData) {
                                $students[] = $studentsData->openemis_no;
                                if($studentsData->gender->code == 'M') {
                                    $maleStudents = $maleStudents + 1;
                                }
                                if($studentsData->gender->code == 'F') {
                                    $femaleStudents = $femaleStudents + 1;
                                }
                            }
                        }

                    }
                }

                $body = array();

                $body = [
                    'institutions_id' => !empty($institutionId) ? $institutionId : NULL,
                    'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                    'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                    'institutions_classes_id' => $institutionClassId,
                    'institutions_classes_name' => $institutionClassName,
                    'academic_periods_name' => !empty($academicPeriod) ? $academicPeriod : NULL,
                    'shift_options_name' => !empty($shift) ? $shift : NULL,
                    'institutions_classes_capacity' => !empty($capacity) ? $capacity : NULL,
                    'education_grades_id' => !empty($gradeId) ? $gradeId :NULL,
                    'education_grades_name' => !empty($grades) ? $grades : NULL,
                    'institution_classes_total_male_students' => !empty($maleStudents) ? $maleStudents : 0,
                    'institution_classes_total_female_studentss' => !empty($femaleStudents) ? $femaleStudents : 0,
                    'total_students' => !empty($students) ? count($students) : 0,
                    'institution_classes_staff_openemis_no' => !empty($homeRoomteacher) ? $homeRoomteacher : NULL,
                    'institution_classes_secondary_staff_openemis_no' => !empty($secondaryTeachers) ? $secondaryTeachers : NULL,
                    'institution_class_students_openemis_no' => !empty($students) ? $students : NULL
                ];
                    $Webhooks = TableRegistry::get('Webhook.Webhooks');
                    if ($this->Auth->user()) {
                        $Webhooks->triggerShell('class_update', ['username' => $username], $body);
                    }
            }

            //POCOR-6995 end

    }

    public function addSections()
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
    }

    public function getStudentTransferWorkflowStatuses($statusCode)
    {
        $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
        $transferInStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentTransferIn', $statusCode);
        $transferOutStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentTransferOut', $statusCode);
        return $transferInStatus + $transferOutStatus;
    }

    public function rejectPendingTransferRequests($registryAlias, $student)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        if ($student->student_status_id == $enrolled) {
            $educationSystemId = $this->EducationGrades->getEducationSystemId($student->education_grade_id);
            $educationGradesToUpdate = $this->EducationGrades->getEducationGradesBySystem($educationSystemId);

            $workflowEntity = $this->getWorkflow($registryAlias);
            $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
            $pendingStatuses = $WorkflowModelsTable->getWorkflowStatusSteps($registryAlias, 'PENDING');

            // get the first step in 'REJECTED' workflow statuses
            $rejectedStatuses = $WorkflowModelsTable->getWorkflowStatusSteps($registryAlias, 'REJECTED');
            ksort($rejectedStatuses);
            $rejectedStepId = key($rejectedStatuses);
            $rejectedStepEntity = $this->Statuses->get($rejectedStepId);

            if (!empty($rejectedStepEntity)) {
                $doneStatus = self::DONE;
                $pendingTransfers = $this->find()
                    ->innerJoinWith($this->Statuses->alias(), function ($q) use ($doneStatus) {
                        return $q->where(['category <> ' => $doneStatus]);
                    })
                    ->where([
                        $this->aliasField('student_id') => $student->student_id,
                        $this->aliasField('education_grade_id IN') => $educationGradesToUpdate,
                        $this->aliasField('status_id IN') => $pendingStatuses
                    ])
                    ->toArray();

                foreach ($pendingTransfers as $entity) {
                    $prevStep = $entity->status_id;

                    // update status_id and assignee_id
                    $entity->status_id = $rejectedStepEntity->id;
                    $this->autoAssignAssignee($entity);

                    if ($this->save($entity)) {
                        // add workflow transition
                        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
                        $prevStepEntity = $this->Statuses->get($prevStep);

                        $transition = [
                            'comment' => __('On Student Transfer into another Institution'),
                            'prev_workflow_step_name' => $prevStepEntity->name,
                            'workflow_step_name' => $rejectedStepEntity->name,
                            'workflow_action_name' => 'Administration - Reject Record',
                            'workflow_model_id' => $workflowEntity->workflow_model_id,
                            'model_reference' => $entity->id,
                            'created_user_id' => 1,
                            'created' => new Time('NOW')
                        ];
                        $transitionEntity = $WorkflowTransitions->newEntity($transition);
                        $WorkflowTransitions->save($transitionEntity);
                    }
                }
            }
        }
    }

    public function findInstitutionStudentTransferIn(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $incomingInstitution = self::INCOMING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::INCOMING, // institution_owner for the step can always see the record
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }

    public function findInstitutionStudentTransferOut(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $outgoingInstitution = self::OUTGOING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('previous_institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::OUTGOING, // institution_owner for the step can always see the record
                    'WorkflowStepsParams.value' => self::INCOMING, // POCOR-4937
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    } 
}
