<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Cake\Controller\Component;
use Cake\Datasource\ConnectionManager; // POCOR-9457
use Cake\I18n\FrozenTime; // POCOR-9457


class StudentTransferOutTable extends InstitutionStudentTransfersTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        if ($this->behaviors()->has('Workflow')) {
            // $this->behaviors()->get('Workflow')->config([
            //     'institution_key' => 'previous_institution_id'
            // ]);
            $workflowBehavior = $this->behaviors()->get('Workflow');
            $workflowBehavior->setConfig('institution_key', 'previous_institution_id');
        }

        //$this->toggle('add', true);//POCOR-6925
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StudentTransferOut'=>['id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmpty(['requested_date', 'workflow_assignee_id'])
            ->add('requested_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'previous_academic_period_id', []]
                ],
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'start_date', true],
                    'on' => function ($context) {
                        return array_key_exists('start_date', $context['data']) && !empty($context['data']['start_date']);
                    }
                ]
            ])
// POCOR-8946
//            ->add('institution_id', 'rulecompareStudentGenderWithInstitution', [
//                'rule' => ['compareStudentGenderWithInstitution'],
//                'on' => 'create'
//            ])
            ->add('student_id', [
                'ruleNoNewWithdrawRequestInGradeAndInstitution' => [
                    'rule' => ['noNewWithdrawRequestInGradeAndInstitution'],
                    'on' => 'create'
                ],
                'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                    'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                        'excludeInstitutions' => ['previous_institution_id']
                    ]],
                    'on' => 'create'
                ],
                'ruleStudentNotCompletedGrade' => [
                    'rule' => ['studentNotCompletedGrade', []],
                    'on' => 'create'
                ]
            ]);
    }

    public function validationBulkTransfer(Validator $validator): Validator
    {
        // requested_date is not relevent for transfer of promoted/graduated students
        $validator = $this->validationDefault($validator);
        return $validator->remove('requested_date');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['UpdateAssignee.onSetSchoolBasedConditions'] = 'onSetSchoolBasedConditions';
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['ControllerAction.Model.associated'] = 'associated';
        return $events;
    }

    // to get correct set of unassigned records for each workflow model in UpdateAssigneeShell
    public function onSetSchoolBasedConditions(EventInterface $event, Entity $entity, $where)
    {
        $where[$this->aliasField('previous_institution_id')] = $entity->id;
        unset($where[$this->aliasField('institution_id')]);
        return $where;
    }

    public function studentsAfterSave(EventInterface $event, $student)
    {
        if ($student->isNew()) {
            // close other pending SENDING transfer applications (in same education system) if the student is successfully transferred in one school
            $this->rejectPendingTransferRequests($this->getRegistryAlias(), $student);
        }
        // POCOR-9457 start
        $statuses = self::getDynamicTableInstance('Student.StudentStatuses')
            ->findCodeList();

        if (
            $student->get('student_status_id') == $statuses['TRANSFERRED'] &&
            $student->isDirty('student_status_id')
        ) {
            $this->removeFromOldSecurityGroup(
                $student->get('student_id'),
                $student->get('institution_id') // old institution
            );
        }
    }

    /**
     * POCOR-9457
     * @param $studentId
     * @param $oldInstitutionId
     * @return void
     */
    protected function removeFromOldSecurityGroup($studentId, $oldInstitutionId)
    {
        if (empty($studentId) || empty($oldInstitutionId)) {
            Log::warning("Missing student ID or previous institution student ID.");
            return;
        }

        // Get role and group for old institution
        $studentRoleId = self::getStudentSecurityRoleId();
        $oldGroupId = self::getInstitutionSecurityGroupId($oldInstitutionId);

        $securityGroupUsersTbl = self::getDynamicTableInstance('security_group_users');

        $deleted = $securityGroupUsersTbl->deleteAll([
            'security_user_id' => $studentId,
            'security_group_id' => $oldGroupId,
            'security_role_id' => $studentRoleId
        ]);

        Log::info("Removed $deleted old security group record(s) for student $studentId in institution $oldInstitutionId.");
    }

    /**
     * POCOR-9457
     * @return int
     */
    private
    static function getStudentSecurityRoleId(): int
    {
        $securityRolesTbl = self::getDynamicTableInstance('security_roles');
        $securityRoles = $securityRolesTbl->find()
            ->where([
                $securityRolesTbl->aliasField('code') => 'STUDENT',
            ])->first();
        $student_role_id = $securityRoles->id;
        return $student_role_id;
    }

    /**
     * POCOR-9457
     * @param $institution_id
     * @return integer
     *
     */
    private static function getInstitutionSecurityGroupId($institution_id)
    {
        $institutionTbl = self::getDynamicTableInstance('Institution.Institutions');
        $securityGroupsTbl = self::getDynamicTableInstance('Security.SecurityGroups');
        $securityGroupInstitutionsTbl = self::getDynamicTableInstance('Security.SecurityGroupInstitutions');

        $institution = $institutionTbl->find()
            ->where([$institutionTbl->aliasField('id') => $institution_id])
            ->first();

        if (empty($institution)) {
            return null;
        }

        $security_group_id = $institution->security_group_id;

        if ($securityGroupsTbl->exists(['id' => $security_group_id])) {
            return $security_group_id;
        }

        // 1 Find a security group with only this institution
        $subQuery = $securityGroupInstitutionsTbl->find()
            ->select(['security_group_id' => $securityGroupInstitutionsTbl->aliasField('security_group_id')])
            ->group($securityGroupInstitutionsTbl->aliasField('security_group_id'))
            ->having(['COUNT(*) =' => 1])
            ->matching('Institutions', function ($q) use ($institution_id) {
                return $q->where(['Institutions.id' => $institution_id]);
            })
            ->first();

        if (!empty($subQuery)) {
            $new_group_id = $subQuery->security_group_id;

            // Update institution to point to this valid group
            $connection = ConnectionManager::get('default');
            $updateQuery = 'UPDATE institutions SET security_group_id = ' . $new_group_id . ' WHERE id = ' . $institution_id;
            $connection->execute($updateQuery);

            return $new_group_id;
        }

        // 2 No group found — create new one (auto-incremented ID)
        $newGroup = $securityGroupsTbl->newEntity([
            'name' => 'Auto-Recovered Group for Institution ' . $institution_id,
            'created_user_id' => 1,
            'created' => new FrozenTime('now')
        ]);

        if (!$securityGroupsTbl->save($newGroup)) {
            Log::error('Failed to create new security group: ' . print_r($newGroup->getErrors(), true));
            return null;
        }

        $new_group_id = $newGroup->id;

        // 3 Link new group to institution
        $linkEntity = $securityGroupInstitutionsTbl->newEntity([
            'security_group_id' => $new_group_id,
            'institution_id' => $institution_id,
            'created_user_id' => 1,
            'created' => new FrozenTime('now')
        ]);

        if (!$securityGroupInstitutionsTbl->save($linkEntity)) {
            Log::error('Failed to link institution to new group: ' . print_r($linkEntity->getErrors(), true));
            return null;
        }

        // 4 Update institution to use new group ID
        $connection = ConnectionManager::get('default');
        $updateQuery = 'UPDATE institutions SET security_group_id = ' . $new_group_id . ' WHERE id = ' . $institution_id;
        $connection->execute($updateQuery);

        return $new_group_id;
    }
    // POCOR-9457 end

    // POCOR-3649
    public function associated(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $this->Alert->error($this->aliasField('unableToTransfer'));
        $currentEntity = $this->Session->read($this->getRegistryAlias() . '.associated');
        $action = $this->Session->read($this->getRegistryAlias() . '.referralAction');

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $this->fields = []; // reset all the fields

        $this->field('student_id', ['entity' => $currentEntity]);
        $this->field('requested_date', ['entity' => $currentEntity]);
        $this->field('associated_records', ['type' => 'associated_records']);

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
        $toolbarButtonsArray['back']['url'] = $this->url($action);
        $toolbarButtonsArray['back']['url'][1] = $encodedQueryString;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back button

        $entity = $this->newEntity();
        $this->controller->set('data', $entity);
        return $entity;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        // Generate encoded query string once
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $studentsUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Students',
            0 => 'index',
            1 => $encodedQueryString
        ];
        $previousTitle = Inflector::humanize(Inflector::underscore($this->getAlias()));

        $Navigation->substituteCrumb($previousTitle, 'Students', $studentsUrl);
        $Navigation->addCrumb($previousTitle);

    }

    public function onGetAssociatedRecordsElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $fieldKey = 'associated_records';
        $dataBetweenDate = [];
        $sessionKey = $this->getRegistryAlias() . '.associatedData';

        if ($this->Session->check($sessionKey)) {
            $dataBetweenDate = $this->Session->read($sessionKey);

            if (!empty($dataBetweenDate)) {
                $tableHeaders = [__('Feature'), __('No of Records')];
                $tableCells = [];

                foreach ($dataBetweenDate as $feature => $count) {
                    $rowData = [];
                    $rowData[] = __(Inflector::humanize(Inflector::underscore($feature)));
                    $rowData[] = __($count);
                    $tableCells[] = $rowData;
                }

                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;
            }
        }
        return $event->getSubject()->renderElement('StudentTransfer/' . $fieldKey, ['attr' => $attr]);;
    }
//POCOR-7881 commented out the function
//    public function getDataBetweenDate($data, $alias)
//    {
//        $StudentAbsences = self::getDynamicTableInstance('Institution.InstitutionStudentAbsences');
//        $StudentBehaviours = self::getDynamicTableInstance('Institution.StudentBehaviours');
//
//        $relatedModels = [$StudentAbsences, $StudentBehaviours];
//
//        $studentId = $data[$alias]['student_id'];
//        $previousInstitutionId = $data[$alias]['previous_institution_id'];
//        $dateRequested = new Date($data[$alias]['requested_date']);
//        $today = new Date();
//
//        $dataBetweenDate = [];
//
//        foreach ($relatedModels as $model) {
////            print_r($model->getAlias());die();
//            switch ($model->getAlias()) {
//                /*case 'InstitutionStudentAbsences':
//                    $absenceCount = $model->find()
//                        ->where([
//                            $model->aliasField('student_id') => $studentId,
//                            $model->aliasField('institution_id') => $previousInstitutionId,
//                            $model->aliasField('date >=') => $dateRequested,
//                            $model->aliasField('date <=') => $today
//                        ])
//                        ->count();
//                    if ($absenceCount) {
//                        $dataBetweenDate[$model->getAlias()] = $absenceCount;
//                    }
//                    break;
//                */
//
//                case 'StudentBehaviours':
//                    $behaviourCount = $model->find()
//                        ->where([
//                            $model->aliasField('student_id') => $studentId,
//                            $model->aliasField('institution_id') => $previousInstitutionId,
//                            $model->aliasField('date_of_behaviour >=') => $dateRequested,
//                            $model->aliasField('date_of_behaviour <=') => $today
//                        ])
//                        ->count();
//                    if ($behaviourCount) {
//                        $dataBetweenDate[$model->getAlias()] = $behaviourCount;
//                    }
//                    break;
//            }
//        }
//
//        return $dataBetweenDate;
//    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);
        $this->field('institution_class_id', ['type' => 'hidden']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        /*if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }*/

        $this->field('start_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('previous_academic_period_id', ['type' => 'hidden']);
        $this->field('previous_education_grade_id', ['type' => 'hidden']);
        $this->field('student_transfer_reason_id', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('institution_id', ['type' => 'integer',
            'sort' => ['field' => 'Institutions.code']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'requested_date']);
        $this->addStudentsExtraButtons($extra['toolbarButtons']); // POCOR-9155

        // End bulk Student Transfer Out button POCOR-6028 end
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $paramInstitutionId = $this->request->getAttribute('params')['institutionId'];

        $institutionId = $this->getQueryString('institution_id');
        //$institutionId = isset($paramInstitutionId) ? $this->paramsDecode($paramInstitutionId)['id'] : $session->read('Institution.Institutions.id');
        $institutionId = isset($paramInstitutionId) ? $this->paramsDecode($paramInstitutionId)['id'] : $institutionId;

        $query->find('InstitutionStudentTransferOut', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'Institutions.code', 'requested_date'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->addSections();
        if (empty($entity->start_date)) {
            $this->field('start_date', ['type' => 'hidden']);
            $this->field('end_date', ['type' => 'hidden']);
        }

        $this->setFieldOrder([
            'status_id', 'assignee_id',
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_academic_period_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'education_grade_id', 'institution_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment']);

        //POCOR-5944 starts
        $statusId = $entity['status']->id;
        $session = $this->request->getSession();
        $institutionId = $this->request->getParam('pass')[1];
        $WorkflowSteps = self::getDynamicTableInstance('Workflow.WorkflowSteps'); // POCOR-8946
        $editCheck = $WorkflowSteps->find()
            ->where([$WorkflowSteps->aliasField('id') => $statusId])
            ->first();
        if (!empty($editCheck)) {
            $isEditable = $editCheck->is_editable;
            $isRemovable = $editCheck->is_removable;
            //hide edit button
            if ($isEditable == 0) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false
                ];

                $extraButtons = [
                    'edit' => [
                        'Institution' => ['Institutions', 'Institutions', 'index'],
                        'action' => 'Institutions',
                        'icon' => '<i class="fa kd-edit"></i>',
                        'title' => __('Edit')
                    ]
                ];
                foreach ($extraButtons as $key => $attr) {
                    if ($this->AccessControl->check($attr['permission'])) {
                        $button = [
                            'type' => 'hidden',
                            'attr' => $btnAttr,
                            'url' => [0 => 'xrindexa']
                        ];
                        $button['url']['action'] = $attr['action'];
                        $button['attr']['title'] = $attr['title'];
                        $button['label'] = $attr['icon'];

                        $extra['toolbarButtons'][$key] = $button;
                    }
                }
            }
            //hide delete button
            if ($isRemovable == 0) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false
                ];

                $extraButtons = [
                    'remove' => [
                        'Institution' => ['Institutions', 'Institutions', 'index'],
                        'action' => 'Institutions',
                        'icon' => '<i class="fa fa-trash"></i>',
                        'title' => __('Delete')
                    ]
                ];
                foreach ($extraButtons as $key => $attr) {
                    if ($this->AccessControl->check($attr['permission'])) {
                        $button = [
                            'type' => 'hidden',
                            'attr' => $btnAttr,
                            'url' => [0 => 'indexasdew']
                        ];
                        $button['url']['action'] = $attr['action'];
                        $button['attr']['title'] = $attr['title'];
                        $button['label'] = $attr['icon'];

                        $extra['toolbarButtons'][$key] = $button;
                    }
                }
            }
        }
        //POCOR-5944 ends
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $studentId = $this->getQueryString('institution_student_id');
        $userId = $this->getQueryString('user_id');

        $queryString = $this->getQueryString();
        $queryString['id'] = $userId;
        $encodedQueryString = $this->paramsEncode($queryString);

        if (empty($studentId) || empty($userId)) {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        } else {
            // url to redirect to studentUser page
            $extra['redirect'] = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentUser',
                '0' => 'view',
                '1' => $encodedQueryString
            ];
            $extra['toolbarButtons']['back']['url'] = $extra['redirect'];

            $Students = self::getDynamicTableInstance('Institution.Students'); // POCOR-8946
            $institutionStudentEntity = $Students->get($studentId, [
                'contain' => ['Users', 'Institutions', 'EducationGrades', 'AcademicPeriods']
            ]);

            // check pending transfers
            $doneStatus = self::DONE;
            $pendingTransfer = $this->find()
                ->matching('Statuses.WorkflowStepsParams', function ($q) use ($doneStatus) {
                    return $q->where([
                        'Statuses.category <> ' => $doneStatus,
                        'WorkflowStepsParams.name' => 'institution_owner'
                    ]);
                })
                ->where([
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('previous_institution_id') => $institutionStudentEntity->institution_id
                ])
                ->first();

            if (!empty($pendingTransfer)) {
                // check if the outgoing institution can view the transfer record
                $visible = 0;
                $institutionOwner = $pendingTransfer->_matchingData['WorkflowStepsParams']->value;
                if ($institutionOwner == self::OUTGOING || $pendingTransfer->all_visible) {
                    $visible = 1;
                }

                if ($visible) {
                    $url = $this->url('view');
                    $url[1] = $this->paramsEncode(['id' => $pendingTransfer->id,
                        'institution_id' => $pendingTransfer->previous_institution_id]);
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                } else {
                    $this->Alert->warning($this->aliasField('existingStudentTransfer'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);
                }

            } else {
                // check pending withdraw
                $StudentWithdrawTable = self::getDynamicTableInstance('Institution.StudentWithdraw'); // POCOR-8946
                $WorkflowModelsTable = self::getDynamicTableInstance('Workflow.WorkflowModels'); // POCOR-8946
                $pendingWithdrawStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentWithdraw', 'PENDING');

                $conditions = [
                    'student_id' => $institutionStudentEntity->student_id,
                    'status_id IN ' => $pendingWithdrawStatus,
                    'education_grade_id' => $institutionStudentEntity->education_grade_id,
                    'institution_id' => $institutionStudentEntity->institution_id,
                    'academic_period_id' => $institutionStudentEntity->academic_period_id
                ];

                $withdrawCount = $StudentWithdrawTable->find()
                    ->where($conditions)
                    ->first();

                if (!empty($withdrawCount)) {
                    $this->Alert->error($this->aliasField('pendingStudentWithdraw'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);
                }
            }

            // if no pending transfer or withdraw
            $this->setupFields($institutionStudentEntity);
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('previous_academic_period_id') && $data->offsetExists('academic_period_id')) {
            $previousAcademicPeriodId = $data->offsetGet('previous_academic_period_id');
            $academicPeriodId = $data->offsetGet('academic_period_id');
            if ($previousAcademicPeriodId != $academicPeriodId) {
                $options['validate'] = 'bulkTransfer';
            }
        }
    }

    //POCOR-7881 commented out the function
//    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
//    {
//        if (empty($entity->errors())) {
//            // get the data between requested date and today date (if its back date)
//            $dataBetweenDate = $this->getDataBetweenDate($requestData, $this->getAlias());
//
//            if (!empty($dataBetweenDate)) {
//                // redirect if have student data between date
//                $url = $this->url('associated');
//                $session = $this->Session;
//                $session->write($this->getRegistryAlias() . '.associated', $entity);
//                $session->write($this->getRegistryAlias() . '.associatedData', $dataBetweenDate);
//                $session->write($this->getRegistryAlias() . '.referralAction', $this->action);
//                $event->stopPropagation();
//                return $this->controller->redirect($url);
//            }
//        }
//    }

    //POCOR-7881 commented out the function
//    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
//    {
//
//        if (empty($entity->errors())) {
//            // get the data between requested date and today date (if its back date)
//            $dataBetweenDate = $this->getDataBetweenDate($requestData, $this->getAlias());
////            print_r($dataBetweenDate);die();
//
//            if (!empty($dataBetweenDate)) {
//                // redirect if have student data between date
//                $url = $this->url('associated');
//                $session = $this->Session;
//                $session->write($this->getRegistryAlias() . '.associated', $entity);
//                $session->write($this->getRegistryAlias() . '.associatedData', $dataBetweenDate);
//                $session->write($this->getRegistryAlias() . '.referralAction', $this->action);
//                $event->stopPropagation();
//                return $this->controller->redirect($url);
//            }
//        }
//    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'EducationGrades', 'PreviousEducationGrades', 'AcademicPeriods', 'PreviousAcademicPeriods']);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupFields(Entity $entity)
    {
        $this->addSections();

        $this->field('student_id', ['entity' => $entity]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('previous_academic_period_id', ['entity' => $entity]);
        $this->field('previous_education_grade_id', ['entity' => $entity]);
        $this->field('requested_date', ['entity' => $entity]);
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['entity' => $entity]);
        $this->field('area_id', ['entity' => $entity]);
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->field('student_transfer_reason_id', ['type' => 'select']);

        $this->setFieldOrder([
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_academic_period_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'education_grade_id', 'area_id', 'institution_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function onUpdateFieldStudentId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve', 'associated'])) {
            $entity = $attr['entity'];
            if ($action == 'associated') {
                $attr['value'] = $entity->student_id;
                $attr['attr']['value'] = $this->Users->get($entity->student_id)->name_with_id;
            } else {
                $attr['value'] = $entity->student_id;
                $attr['attr']['value'] = $entity->user->name_with_id;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldPreviousAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            if ($action == 'add') {
                // using institution_student entity
                $attr['value'] = $entity->academic_period_id;
                $attr['attr']['value'] = $entity->academic_period->name;
            } else {
                // using institution_student_transfer entity
                $attr['value'] = $entity->previous_academic_period_id;
                $attr['attr']['value'] = $entity->previous_academic_period->name;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            if ($action == 'add') {
                // using institution_student entity
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            } else {
                // using institution_student_transfer entity
                $attr['value'] = $entity->previous_institution_id;
                $attr['attr']['value'] = $entity->previous_institution->code_name;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            if ($action == 'add') {
                // using institution_student entity
                $attr['value'] = $entity->education_grade_id;
                $attr['attr']['value'] = $entity->education_grade->programme_grade_name;
            } else {
                // using institution_student_transfer entity
                $attr['value'] = $entity->previous_education_grade_id;
                $attr['attr']['value'] = $entity->previous_education_grade->programme_grade_name;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldRequestedDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //Single transfer
        if (in_array($action, ['add', 'edit', 'approve', 'associated'])) {
            $entity = $attr['entity'];

            if ($action == 'associated') {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->requested_date->format('d-m-Y');
                $attr['attr']['value'] = $this->formatDate($entity->requested_date);
            } else {
                $enrolledStudent = false;
                if ($action == 'add') {
                    // using institution_student entity
                    $studentStartDate = $entity->start_date;
                    $studentEndDate = $entity->end_date;
                    $academicPeriodId = $entity->academic_period_id;
                    $enrolledStudent = true;
                } else {
                    // using institution_student_transfer entity
                    $Students = self::getDynamicTableInstance('Institution.Students'); // POCOR-8946
                    $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses'); // POCOR-8946
                    $statuses = $StudentStatuses->findCodeList();

                    $studentEntity = $Students->find()
                        ->where([
                            $Students->aliasField('institution_id') => $entity->previous_institution_id,
                            $Students->aliasField('academic_period_id') => $entity->previous_academic_period_id,
                            $Students->aliasField('education_grade_id') => $entity->previous_education_grade_id,
                            $Students->aliasField('student_id') => $entity->student_id,
                            $Students->aliasField('student_status_id') => $statuses['CURRENT']
                        ])
                        ->first();

                    // if enrolled student cannot be found (perhaps is promoted/graduated)
                    if (!empty($studentEntity)) {
                        $studentStartDate = $studentEntity->start_date;
                        $studentEndDate = $studentEntity->end_date;
                        $academicPeriodId = $entity->previous_academic_period_id;
                        $enrolledStudent = true;
                    }
                }

                if ($enrolledStudent) {
                    $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
                    $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;
                    // for date options, date restriction
                    $startDate = ($studentStartDate >= $periodStartDate) ? $studentStartDate : $periodStartDate;
                    $endDate = ($studentEndDate <= $periodStartDate) ? $studentEndDate : $periodEndDate;
                    $attr['type'] = 'date';
                    $attr['date_options'] = [
                        'startDate' => $startDate->format('d-m-Y'),
                        'endDate' => $endDate->format('d-m-Y'),
                        'todayBtn' => false
                    ];
                } else {
                    $requestedDate = !empty($entity->requested_date) ? $entity->requested_date : (new Date());
                    $attr['type'] = 'readonly';
                    $attr['value'] = $requestedDate->format('Y-m-d');
                    $attr['attr']['value'] = $this->formatDate($requestedDate);
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->education_grade_id;
            $attr['attr']['value'] = $entity->education_grade->programme_grade_name;
            return $attr;
        }
    }

    /**
     * @param EventInterface $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     *
     */
    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = $attr['entity'];
        $next_period_id = $entity->academic_period_id;
        $next_grade_id = $entity->education_grade_id;
        $institution_id = $entity->institution_id;
        if (in_array($action, ['add', 'edit', 'approve'])) {
            // POCOR-8943 start
            $Areas = self::getDynamicTableInstance('Area.Areas');
            $Institutions = self::getDynamicTableInstance('Institution.Institutions');
            $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
            $InstitutionStatuses = self::getDynamicTableInstance('Institution.Statuses');
            // POCOR-8943 end
            if ($action == 'add') {
                // using institution_student entity
                $today = Date::now()->format('Y-m-d');
                $nextPeriodData = $this->AcademicPeriods->get($next_period_id);
                if ($nextPeriodData->start_date instanceof Time) {
                    $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
                } else {
                    $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
                }

                $activeId = $InstitutionStatuses->getIdByCode('ACTIVE');
                $areaOptions = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->innerJoin([$Institutions->getAlias() => $Institutions->getTable()],
                        [$Institutions->aliasField('area_id = ') . $Areas->aliasField('id')])
                    ->innerJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],
                        [$InstitutionGrades->aliasField('institution_id = ') . $Institutions->aliasField('id')])
                    ->where([
                        $InstitutionGrades->aliasField('institution_id <>') => $institution_id,
                        $InstitutionGrades->aliasField('education_grade_id') => $next_grade_id,
                        $InstitutionGrades->aliasField('start_date >=') => $nextPeriodStartDate,
                        $Institutions->aliasField('institution_status_id') =>
                            $activeId,
                        'OR' => [
                            $InstitutionGrades->aliasField('end_date IS NULL'),
                            $InstitutionGrades->aliasField('end_date >=') => $today
                        ]
                    ])
                    ->orderAsc($Areas->aliasField('parent_id'))
                    ->orderAsc($Areas->aliasField('order'))
                ;
//                $this->log($areaOptions->sql(), 'debug');
//                $this->log("$institution_id = $next_grade_id = $nextPeriodStartDate = $activeId = $today", 'debug');
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $areaOptions->toArray();
                $attr['onChangeReload'] = true;
            } else {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    /**
     * @param EventInterface $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     *
     */
    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //single student
        // POCOR-8946 start
        if (in_array($action, ['add', 'edit', 'approve'])) {
            // POCOR-9012 start
            $entity = $attr['entity'];
            $student_id = $entity->student_id;
//            Log::write('debug', 'student_id: ' . $student_id);
            $institution_id = $entity->institution_id;
            if($student_id == null){
                $student_id = $this->getQueryString('student_id');
            }
            if($student_id === null){
                $attr['type'] = 'readonly';
                $attr['value'] = $institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
                return $attr;
            }
            // POCOR-9012 end
            $student_gender_id = $this->Users->get($student_id)->gender_id;
            $next_period_id = $entity->academic_period_id;
            $next_grade_id = $entity->education_grade_id;
            $InstitutionGrades = self::getDynamicTableInstance('institution_grades');
            $InstitutionStatuses = self::getDynamicTableInstance('Institution.Statuses');
            $InstitutionGenders = self::getDynamicTableInstance('institution_genders');
            $Genders = self::getDynamicTableInstance('genders');
            $genderCode = $Genders->get($student_gender_id)->code;
            $neededGenders = ['X', $genderCode];
            $area_id = $request->getData($this->getAlias())['area_id'];
            $institutionOptions = [];
            if ($action == 'add') {
                if (!is_null($next_period_id) && !is_null($next_grade_id)) {
                    $today = Date::now();
                    $nextPeriodData = $this->AcademicPeriods->get($next_period_id);
                    if ($nextPeriodData->start_date instanceof Time) {
                        $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
                    } else {
                        $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
                    }

                    $Institutions = $this->Institutions;
                    $institutionQuery = $Institutions
                        ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                        ->join([
                            'table' => $InstitutionGrades->getTable(),
                            'alias' => $InstitutionGrades->getAlias(),
                            'conditions' => [
                                $InstitutionGrades->aliasField('institution_id = ') .
                                $Institutions->aliasField('id'),
                                $InstitutionGrades->aliasField('education_grade_id') => $next_grade_id,
                                $InstitutionGrades->aliasField('start_date >=') => $nextPeriodStartDate,
                                'OR' => [
                                    $InstitutionGrades->aliasField('end_date IS NULL'),
                                    $InstitutionGrades->aliasField('end_date >=') => $today->format('Y-m-d')
                                ]
                            ]
                        ])->join([
                            'table' => $InstitutionGenders->getTable(),
                            'type' => 'INNER',
                            'alias' => $InstitutionGenders->getAlias(),
                            'conditions' => [
                                $InstitutionGenders->aliasField('id = ') .
                                $Institutions->aliasField('institution_gender_id'),
                                $InstitutionGenders->aliasField('code IN ') => $neededGenders
                            ]
                        ])
                        ->where([
                            $Institutions->aliasField('id <>') => $institution_id,
                            $Institutions->aliasField('institution_status_id') =>
                                $InstitutionStatuses->getIdByCode('ACTIVE')
                        ])
                        ->orderAsc($Institutions->aliasField('code'));

                    if (!empty($area_id)) {
                        $institutionQuery->where([$Institutions->aliasField('area_id')
                        => $area_id]);
                    }
                    $institutionOptions = $institutionQuery->toArray();
                }

                // POCOR-8946 end
                $attr['attr']['label'] = __('Institution');
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $institutionOptions;
                $attr['onChangeReload'] = true;

//                $attr['type'] = 'chosenSelect';
//                $attr['attr']['multiple'] = false;
//                $attr['select'] = true;
//                $today = Date::now();
//
//                $selectedAcademicPeriodData = $this->AcademicPeriods->get($next_period_id);
//                if ($selectedAcademicPeriodData->end_date instanceof Time
//                    || $selectedAcademicPeriodData->end_date instanceof Date) {
//                    $academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
//                } else {
//                    $academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
//                }
//
//
//                $institutionOptions = $this->Institutions
//                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
//                    ->innerJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()], [
//                        $InstitutionGrades->aliasField('institution_id =') .
//                        $this->Institutions->aliasField('id'),
//                        $InstitutionGrades->aliasField('education_grade_id') =>
//                            $next_grade_id,
//                        $InstitutionGrades->aliasField('start_date') . ' <= ' => $academicPeriodEndDate,
//                        'OR' => [
//                            $InstitutionGrades->aliasField('end_date') . ' IS NULL',
//                            // Previously as long as the programme
//                            // end date is later than academicPeriodStartDate,
//                            // institution will be in the list.
//                            // POCOR-3134 request to only
//                            // displayed institution with active grades
//                            // (end-date is later than today-date)
//                            $InstitutionGrades->aliasField('end_date') . ' >=' => $today->format('Y-m-d')
//                        ]
//                    ])
//                    ->where([$this->Institutions->aliasField('institution_status_id')
//                    => $InstitutionStatuses->getIdByCode('ACTIVE'),
//                        $this->Institutions->aliasField('id <>') => $institution_id,]
//                        )
//                    ->order([$this->Institutions->aliasField('code')]);
//
//                if ($area_id) {
//                    $institutionOptions->where([$this->Institutions->aliasField('area_id')
//                    => $area_id]);
//                }
//                $attr['options'] = $institutionOptions->toArray();
            } else {
                // using institution_student_transfer entity
                $attr['type'] = 'readonly';
                $attr['value'] = $institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            }
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if (in_array($action, ['edit', 'approve']) && !empty($entity->start_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->start_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->start_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if (in_array($action, ['edit', 'approve']) && !empty($entity->end_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->end_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->end_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if ($this->action == 'associated') {
            $sessionKey = $this->getRegistryAlias() . '.associatedData';
            if ($this->Session->check($sessionKey) && !empty($this->Session->read($sessionKey))) {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $StepsParams = $this->Statuses->WorkflowStepsParams;
        $doneStatus = self::DONE;
        $outgoingInstitution = self::OUTGOING;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('previous_institution_id'),
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
                $this->PreviousInstitutions->aliasField('code'),
                $this->PreviousInstitutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->getAlias(), $this->Institutions->getAlias(), $this->PreviousInstitutions->getAlias(), $this->CreatedUser->getAlias(), 'Assignees'])
            ->matching($Statuses->getAlias() . '.' . $StepsParams->getAlias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $outgoingInstitution) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus,
                    $StepsParams->aliasField('name') => 'institution_owner',
                    $StepsParams->aliasField('value') => $outgoingInstitution
                ]);
            })
            ->where([
                $this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1
            ])//POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentTransferOut',
                        0 => 'view',
                        1 => $this->paramsEncode(['id' => $row->id, 'institution_id' => $row->previous_institution_id]),

                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Transfer of student %s to %s'), $row->user->name_with_id, $row->institution->code_name);
                    $row['institution'] = $row->previous_institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    //POCOR-6981
    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'add', 'approve'])) { // POCOR-8411 start
            $workflowModel = 'Institutions > Student Transfer > Sending';
            // POCOR-8946 start
            $workflowModelsTable = self::getDynamicTableInstance('Workflow.WorkflowModels');
            $workflowStepsTable = self::getDynamicTableInstance('Workflow.WorkflowSteps');
            $Workflows = self::getDynamicTableInstance('Workflow.Workflows');
            // POCOR-8946 end
            $workModelId = $Workflows
                ->find()
                ->select(['id' => $workflowModelsTable->aliasField('id'),
                    'workflow_id' => $Workflows->aliasField('id'),
                    'is_school_based' => $workflowModelsTable->aliasField('is_school_based')])
                ->LeftJoin([$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
                    [
                        $workflowModelsTable->aliasField('id') . ' = ' . $Workflows->aliasField('workflow_model_id')
                    ])
                ->where([$workflowModelsTable->aliasField('name') => $workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                ->find()
                ->select([
                    'stepId' => $workflowStepsTable->aliasField('id'),
                ])
                ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                ->first();
            $stepId = $workflowStepsOptions->stepId;
            /*$session = $request->getSession();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }*/
            $institutionId  = $this->getInstitutionID();
            $institutionId = $institutionId;

            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = self::getDynamicTableInstance('Workflow.WorkflowStepsRoles'); // POCOR-8946 end
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = self::getDynamicTableInstance('Security.SecurityGroupUsers'); // POCOR-8946 end
                    $Areas = self::getDynamicTableInstance('Area.Areas'); // POCOR-8946 end
                    $Institutions = self::getDynamicTableInstance('Institution.Institutions'); // POCOR-8946 end
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
    private static function debug($something)
    {
        if (is_null($something)) {
            $message = 'NULL';
        } elseif (is_bool($something)) {
            $message = $something ? 'TRUE' : 'FALSE';
        } elseif (is_array($something) || is_object($something)) {
            $message = json_encode($something, JSON_PRETTY_PRINT);
        } else {
            $message = (string)$something;
        }

        \Cake\Log\Log::debug($message);
    }

    /**
     * @param string $tableName
     * @return Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        $locator = TableRegistry::getTableLocator();;
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        // Parse plugin and table names if dot notation is used
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }
            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }
    //POCOR-8642 -- START
    public function getReceivingInstList($params) {
        $receivingOptions = [];

        $StudentTransferOut = self::getDynamicTableInstance('Institution.StudentTransferOut'); // POCOR-8946 end

        $receivingOptions = $StudentTransferOut->find()
            ->select(['institution_id'])
            ->where([$StudentTransferOut->aliasField('id') => $params])
            ->first();
            if ($receivingOptions) {
                $recvInstitution = $receivingOptions->institution_id; // Assign the institution_id to $newid
            }
        return $recvInstitution;
    }
    //POCOR-8642 -- END

    /**
     * @param $toolbarButtons1
     * @return void
     */
    private function addStudentsExtraButtons($toolbarButtons1): void // POCOR-9155
    {
// back button
        // Generate encoded query string once
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

// Common button attributes
        $baseBtnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
        ];

// Add back button
        $toolbarButtons = $toolbarButtons1->getArrayCopy();
        $toolbarButtons['back'] = [
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => array_merge($baseBtnAttr, ['title' => __('Back')]),
            'url' => [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Students',
                0 => 'index',
                1 => $encodedQueryString
            ]
        ];

// Define all extra toolbar buttons
        $extraButtons = [
            'add' => [
                'permission' => ['Institutions', 'Students', 'add'],
                'action' => 'Students',
                'icon' => '<i class="fa fa-plus"></i>',
                'title' => __('Add')
            ],
            'graduate' => [
                'permission' => ['Institutions', 'Promotion', 'add'],
                'action' => 'Promotion',
                'icon' => '<i class="fa kd-graduate"></i>',
                'title' => __('Promotion / Repeating / Graduation')
            ],
            'transfer' => [
                'permission' => ['Institutions', 'Transfer', 'add'],
                'action' => 'Transfer',
                'icon' => '<i class="fa kd-transfer"></i>',
                'title' => __('Transfer')
            ],
            'undo' => [
                'permission' => ['Institutions', 'Undo', 'add'],
                'action' => 'Undo',
                'icon' => '<i class="fa kd-undo"></i>',
                'title' => __('Undo')
            ],
        ];

        $extraButtons['bulkTransferOut'] = [
            'permission' => ['Institutions', 'Transfer', 'add'],
            'action' => 'BulkStudentTransferOut',
            'next_action' => 'edit',
            'icon' => '<i class="fa kd-transfer"></i>',
            'title' => __('Bulk Student Transfer Out')
        ];

        foreach ($extraButtons as $key => $config) {
            if (!empty($config['external'])) {
                $toolbarButtons[$key] = [
                    'type' => 'link',
                    'label' => $config['icon'],
                    'attr' => array_merge($baseBtnAttr, [
                        'title' => $config['title'],
                        'target' => '_blank'
                    ]),
                    'url' => $config['url']
                ];
                continue;
            }

            if (!empty($config['permission']) &&
                !$this->AccessControl->check($config['permission'])) {
                continue;
            }

            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $config['action'],
                0 => $config['next_action'] ?? 'add',
                1 => $encodedQueryString
            ];

            if (!empty($config['extraParams'])) {
                $url = array_merge($url, ['?' => $config['extraParams']]);
            }

            $toolbarButtons[$key] = [
                'type' => 'button',
                'label' => $config['icon'],
                'attr' => array_merge($baseBtnAttr, ['title' => $config['title']]),
                'url' => $url
            ];
        }

        $toolbarButtons1->exchangeArray($toolbarButtons);
    }

}
