<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;

class StudentAdmissionTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $workflowEvents = [
        [
            'value' => 'Workflow.onApprove',
            'text' => 'Approval of Student Admission',
            'description' => 'Performing this action will enroll the student into the institution.',
            'method' => 'OnApprove',
            'unique' => true
        ],
        [
            'value' => 'Workflow.onCancel',
            'text' => 'Cancellation of Student Admission',
            'description' => 'Performing this action will remove the student from the institution.',
            'method' => 'onCancel',
            'unique' => true
        ]
    ];

    public function initialize(array $config)
    {
        $this->table('institution_student_admission');

        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Students' => ['index', 'add']
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'end_date', false]
                ],
                'ruleCheckProgrammeEndDateAgainstStudentStartDate' => [
                    'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
                ]
            ])
            ->add('student_id', [
                'ruleCheckPendingAdmissionExist' => [
                    'rule' => ['checkPendingAdmissionExist'],
                    'on' => 'create',
                    'last' => true
                ],
                'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                    'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                    'on' => 'create',
                    'last' => true
                ],
                'ruleStudentNotCompletedGrade' => [
                    'rule' => ['studentNotCompletedGrade', []],
                    'on' => 'create',
                    'last' => true
                ],
                'ruleCheckAdmissionAgeWithEducationCycleGrade' => [
                    'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                    'on' => 'create',
                    'last' => true
                ]
            ])
            ->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->add('gender_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution'],
                'on' => 'create'
            ])
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id']
            ])
            ->allowEmpty('institution_class_id')
            ->add('institution_class_id', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit']
            ]);

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        foreach ($this->workflowEvents as $event) {
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

    public function onApprove(Event $event, $id, Entity $workflowTransitionEntity)
    {
        // add student into institution_students
        $entity = $this->get($id);
        $this->addInstitutionStudent($entity);
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

        if (!empty($newStudentRecord)) {
            // delete student record in the new institution
            $Students->delete($newStudentRecord);
        }
    }

    public function addInstitutionStudent(Entity $entity)
    {
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $incomingStudent = [
            'student_status_id' => $statuses['CURRENT'],
            'student_id' => $entity->student_id,
            'education_grade_id' => $entity->education_grade_id,
            'academic_period_id' => $entity->academic_period_id,
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
            'institution_id' => $entity->institution_id
        ];
        if (!empty($entity->institution_class_id)) {
            $incomingStudent['class'] = $entity->institution_class_id;
        }

        $newEntity = $Students->newEntity($incomingStudent);
        $Students->save($newEntity);
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
            // close other pending admission applications (in same education system) if the student is successfully enrolled in one school
            if ($student->student_status_id == $Enrolled) {
                $educationSystemId = $this->EducationGrades->getEducationSystemId($student->education_grade_id);
                $educationGradesToUpdate = $this->EducationGrades->getEducationGradesBySystem($educationSystemId);

                // get the first step in 'REJECTED' workflow statuses
                $workflowEntity = $this->getWorkflow($this->registryAlias());
                $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
                $statuses = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'REJECTED');
                ksort($statuses);
                $rejectedStatusId = key($statuses);
                $rejectedStatusEntity = $this->Statuses->get($rejectedStatusId);

                if (!empty($rejectedStatusEntity)) {
                    $doneStatus = self::DONE;
                    $pendingAdmissions = $this->find()
                        ->innerJoinWith($this->Statuses->alias(), function ($q) use ($doneStatus) {
                            return $q->where(['category <> ' => $doneStatus]);
                        })
                        ->where([
                            $this->aliasField('student_id') => $student->student_id,
                            $this->aliasField('education_grade_id IN') => $educationGradesToUpdate
                        ])
                        ->toArray();

                    foreach ($pendingAdmissions as $entity) {
                        $prevStep = $entity->status_id;

                        // update status_id and assignee_id
                        $entity->status_id = $rejectedStatusEntity->id;
                        $this->autoAssignAssignee($entity);

                        if ($this->save($entity)) {
                            // add workflow transition
                            $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
                            $prevStepEntity = $this->Statuses->get($prevStep);

                            $transition = [
                                'comment' => __('On Student Admission into another Institution'),
                                'prev_workflow_step_name' => $prevStepEntity->name,
                                'workflow_step_name' => $rejectedStatusEntity->name,
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
        $StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
        $doneStatus = self::DONE;

        //remove all pending transfer requests
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and transfer/admission can be done on different grade / academic period)
        $pendingTransfers = $StudentTransfers->find()
            ->innerJoinWith($StudentTransfers->Statuses->alias(), function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([
                $StudentTransfers->aliasField('student_id') => $studentId,
                $StudentTransfers->aliasField('previous_institution_id') => $institutionId
            ])
            ->toArray();

        if (!empty($pendingTransfers)) {
            foreach ($pendingTransfers as $entity) {
                $StudentTransfers->delete($entity);
            }
        }

        //remove all pending admission requests
        $pendingAdmissions = $this->find()
            ->innerJoinWith($this->Statuses->alias(), function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([$this->aliasField('student_id') => $studentId])
            ->toArray();

        if (!empty($pendingAdmissions)) {
            foreach ($pendingAdmissions as $entity) {
                $this->delete($entity);
            }
        }
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
        $toolbarButtons = $extra['toolbarButtons'];
        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');

        if ($this->action == 'index') {
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = [
                'title' => __('Back'),
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtons['back']['url'] = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                'action' => 'Students',
                0 => 'index'
            ];

        } elseif ($this->action == 'edit') {
            $toolbarButtons['back']['url'][0] = 'index';
            if ($toolbarButtons['back']['url']['controller'] == 'Dashboard') {
                $toolbarButtons['back']['url']['action'] = 'index';
                unset($toolbarButtons['back']['url'][0]);
            }
            unset($toolbarButtons['back']['url'][1]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['type' => 'hidden']);
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
        $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
        $this->field('institution_class_id', ['entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->setFieldOrder(['student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $academicPeriodId = $entity->academic_period_id;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $periodStartDate->format('d-m-Y'),
                'endDate' => $periodEndDate->format('d-m-Y'),
                'todayBtn' => false
            ];
            return $attr;
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $endDate = $attr['entity']->end_date;
            $attr['type'] = 'readonly';
            $attr['value'] = $endDate->format('d-m-Y');
            $attr['attr']['value'] = $endDate->format('d-m-Y');
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $Classes = TableRegistry::get('Institution.InstitutionClasses');

            $options = $Classes->find('list')
                ->innerJoinWith('ClassGrades')
                ->where([
                    $Classes->aliasField('institution_id') => $entity->institution_id,
                    $Classes->aliasField('academic_period_id') => $entity->academic_period_id,
                    'ClassGrades.education_grade_id' => $entity->education_grade_id
                ])
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $options;
            return $attr;
        }
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // auto approve admission and add student into the institution
        if ($entity->isNew()) {
            $superAdmin = Hash::get($_SESSION['Auth'], 'User.super_admin');
            $executePermission = isset($_SESSION['Permissions']) && Hash::check($_SESSION['Permissions'], 'Institutions.StudentAdmission.execute');

            // creator must be admin or have 'Student Admission -> Execute' permission
            if ($superAdmin || $executePermission) {
                $workflowEntity = $this->getWorkflow($this->registryAlias());

                // get the first step in 'APPROVED' workflow statuses
                $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
                $statuses = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'APPROVED');
                ksort($statuses);
                $approvedStatusId = key($statuses);
                $approvedStatusEntity = $this->Statuses->get($approvedStatusId);

                if (!empty($approvedStatusEntity)) {
                    $prevStepEntity = $this->Statuses->get($entity->status_id);

                    // update status_id and assignee_id of admission record
                    $entity->status_id = $approvedStatusEntity->id;
                    $this->autoAssignAssignee($entity);

                    if ($this->save($entity)) {
                        // add student into institution_students
                        $this->addInstitutionStudent($entity);

                        // add workflow transition
                        $transition = [
                            'comment' => __('On Auto Approve Student Admission'),
                            'prev_workflow_step_name' => $prevStepEntity->name,
                            'workflow_step_name' => $approvedStatusEntity->name,
                            'workflow_action_name' => 'Administration - Approve Record',
                            'workflow_model_id' => $workflowEntity->workflow_model_id,
                            'model_reference' => $entity->id,
                            'created_user_id' => 1,
                            'created' => new Time('NOW')
                        ];

                        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
                        $transitionEntity = $WorkflowTransitions->newEntity($transition);
                        $WorkflowTransitions->save($transitionEntity);
                    }
                }
            }
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
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
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentAdmission',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Admission of student %s'), $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
