<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\I18n\Date;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\Http\ServerRequest;

class WithdrawRequestsTable extends ControllerActionTable
{
    const NEW_REQUEST = 0;

    public function initialize(array $config): void
    {
        $this->setTable('institution_student_withdraw');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentWithdrawReasons', ['className' => 'Student.StudentWithdrawReasons', 'foreignKey' => 'student_withdraw_reason_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.StudentWithdraw']);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->toggle('index', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['WithdrawRequests' =>['id','student_id','institution_id']
            ]
        ]);

    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $queryString = $this->getQueryString();
        $institutionStudentId = $this->getQueryString('institution_student_id');
        $encodedQueryString = $this->paramsEncode($queryString);
        if (!$entity->getInvalid()) {
            $studentId = $this->getStudentID();
            $action = $this->url('add');
            $action['action'] = 'StudentUser';
            $action[0] = 'view';
            $action[1] = $encodedQueryString;
            $action[2] = $this->paramsEncode(['id' => $studentId]);
            //$action['id'] = $this->Session->read($this->getRegistryAlias().'.id');
            $action['id'] = $institutionStudentId;

            $event->stopPropagation();
            //$this->Session->delete($this->getRegistryAlias().'.id');
            return $this->controller->redirect($action);
        }
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData)
    {
//        POCOR-8003 refactured
        $status_can_be_changed = $this->checkStatusCanBeChanged($entity);

        if ($status_can_be_changed['can'] == false) {
            $Students = TableRegistry::getTableLocator()->get('Institution.Students');
            $action = $this->url('index');
            $action['action'] = $Students->getAlias();
            $event->stopPropagation();
            $this->Alert->error($status_can_be_changed['message']);
            return $this->controller->redirect($action);
        }
    }


    /**
     * POCOR-8003
     * @param Entity $entity
     * @return array
     */
    private function checkStatusCanBeChanged(Entity $entity)
    {
        $status_can_be_changed = true;
        $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
        $student_id = $entity->student_id;
        $institution_id = $entity->institution_id;
        $education_grade_id = $entity->education_grade_id;
        $academic_period_id = $entity->academic_period_id;
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $editableAcademicPeriods = $AcademicPeriods->getYearList(['isEditable' => true]);
        $Enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $message = "";
        $is_the_year_editable = array_key_exists($academic_period_id, $editableAcademicPeriods);
        if ($status_can_be_changed == true && !$is_the_year_editable) {
            $status_can_be_changed = false;
            $message = 'StudentWithdraw.wrongAcademicPeriod';
        }

        if ($status_can_be_changed == true) {
            $currentStudentRecord = $StudentsTable->find()
                ->where([$StudentsTable->aliasField('student_id') => $student_id,
                    $StudentsTable->aliasField('institution_id') => $institution_id,
                    $StudentsTable->aliasField('academic_period_id') => $academic_period_id,
                    $StudentsTable->aliasField('education_grade_id') => $education_grade_id,
                    $StudentsTable->aliasField('student_status_id') => $Enrolled])
                ->first();
            if (empty($currentStudentRecord)) {
                $status_can_be_changed = false;
                $message = 'StudentWithdraw.wrongStatus';
            }
        }


        if ($status_can_be_changed == true) {
            $StudentTransfersTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentTransfers');
            $pendingTransferStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('PENDING');
            $conditions = [
                'student_id' => $entity->student_id,
                'status_id IN ' => $pendingTransferStatuses,
                'previous_education_grade_id' => $entity->education_grade_id,
                'previous_institution_id' => $entity->institution_id,
                'previous_academic_period_id' => $entity->academic_period_id
            ];

            $count = $StudentTransfersTable->find()
                ->where($conditions)
                ->count();

            if ($count > 0) {
                $status_can_be_changed = false;
                $message = 'StudentWithdraw.hasTransferApplication';
            }
        }

        $can_be_changed = ['can' => $status_can_be_changed, 'message' => $message];
        return $can_be_changed;
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if ($this->Session->check($this->getRegistryAlias().'.id')) {
            $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
            $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
            $this->field('academic_period_id', ['type' => 'hidden', 'attr' => ['value' => $entity->academic_period_id]]);
            $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
            $this->field('effective_date');
            $this->field('student_withdraw_reason_id', ['type' => 'select']);
            $this->field('comment');
            $this->field('assignee_id');

            $this->setFieldOrder([
                'student_id','institution_id', 'academic_period_id', 'education_grade_id',
                'effective_date',
                'student_withdraw_reason_id', 'comment','assignee_id',
            ]);
        } else {
            $Students = TableRegistry::getTableLocator()->get('Institution.Students');
            $action = $this->url('index');
            $action['action'] = $Students->getAlias();
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }

        $toolbarButtons = $extra['toolbarButtons'];
        $studentId = $this->getStudentID();
        $Students = TableRegistry::getTableLocator()->get('Institution.StudentUser');
        $toolbarButtons['back']['url']['action'] = $Students->getAlias();
        $toolbarButtons['back']['url'][0] = 'view';
        $toolbarButtons['back']['url'][1] =  $encodedQueryString;
        $toolbarButtons['back']['url'][2] = $this->paramsEncode(['id' => $studentId]);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $institutionId = $this->getInstitutionID();
        //$id = $this->Session->read($this->getRegistryAlias().'.id');
        $id = $this->getQueryString('institution_student_id');
        $Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $student = $Students->get($id);
        $entity->student_id = $student->student_id;
        $entity->academic_period_id = $student->academic_period_id;
        $entity->education_grade_id = $student->education_grade_id;
        $entity->institution_id = $student->institution_id;
        $entity->id = $id;

        $this->request->getData()[$this->getAlias()]['student_id'] = $entity->student_id;
        $this->request->getData()[$this->getAlias()]['academic_period_id'] = $entity->academic_period_id;
        $this->request->getData()[$this->getAlias()]['education_grade_id'] = $entity->education_grade_id;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator->add('effective_date', 'ruleDateAfterEnrollment', [
                    'rule' => ['dateAfterEnrollment'],
                    'provider' => 'table'
                    ]);
        return $validator;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        return $events;
    }

    public function onUpdateFieldEffectiveDate(EventInterface $event, array $attr, $action, $request)
    {
       // $id = $this->Session->read($this->getRegistryAlias().'.id');
        $id = $this->getQueryString('institution_student_id');
        $studentData = TableRegistry::getTableLocator()->get('Institution.Students')->get($id);

        $enrolledDate = $studentData['start_date']->format('d-m-Y');
        //POCOR-8003:start
        $academicPeriodId = $studentData['academic_period_id'];
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $endDate = $periodEntity->end_date->format('d-m-Y');
        $attr['date_options'] = ['startDate' => $enrolledDate, 'endDate' => $endDate];
        //POCOR-8003:end

        return $attr;
    }


    //POCOR-6925
    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'add', 'approve'])) { // POCOR-8411 start
            $workflowModel = 'Institutions > Students > Student Withdraw';
            $workflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            $workflowStepsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->getSession();
            /*if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }*/
            $institutionId = $this->getInstitutionID();
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();

                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);

                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }
}
