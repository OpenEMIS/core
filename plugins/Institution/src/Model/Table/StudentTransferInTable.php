<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Controller\Component;
use Cake\Utility\Inflector;

class StudentTransferInTable extends InstitutionStudentTransfersTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Students' => ['index', 'add']
        ]);

        if ($this->behaviors()->has('Workflow')) {
            // $this->behaviors()->get('Workflow')->config([
            //     'institution_key' => 'institution_id'
            // ]);
            $workflowBehavior = $this->behaviors()->get('Workflow');
            $workflowBehavior->setConfig('institution_key', 'institution_id');
        }

        $this->toggle('add', true);//POCOR-6925
        $this->addBehavior('Institution.InstitutionTab'
            , [
                'appliedAction' => ['StudentTransferIn' => [
                    'assignee_id',
                    'institution_id',
                    'academic_period_id',
                    'previous_institution_id',
                    'previous_academic_period_id',
                    'previous_education_grade_id',
                    'student_transfer_reason_id'
                ]
                ]
            ]
        );
    }

    public function validationBulkTransfer(Validator $validator)
    {
        // requested_date is not relevent for transfer of promoted/graduated students
        $validator = $this->validationDefault($validator);
        return $validator->remove('start_date', 'ruleCompareDateReverse');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmpty(['start_date', 'workflow_assignee_id'])
            ->add('start_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'requested_date', true],
                    'on' => function ($context) {
                        return array_key_exists('requested_date', $context['data'])
                            && !empty($context['data']['requested_date']);
                    }
                ],
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'end_date', false],
                    'on' => function ($context) {
                        return array_key_exists('end_date', $context['data']) && !empty($context['data']['end_date']);
                    }
                ],
                'ruleCheckProgrammeEndDateAgainstStudentStartDate' => [
                    'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
                ],
                'dateAlreadyTaken' => [
                    'rule' => ['dateAlreadyTaken']
                ]
            ])
            ->allowEmpty('institution_class_id')
            ->add('institution_class_id', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit']
            ])
            ->add('student_transfer', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStudentTransfer'],
                'on' => 'create'
            ])
            ->add('student_id', [
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
            ])
            ->add('education_grade_id', [
                'ruleCheckInstitutionOffersGrade' => [
                    'rule' => ['checkInstitutionOffersGrade'],
                    'on' => 'create'
                ],
                'ruleCheckProgrammeEndDate' => [
                    'rule' => ['checkProgrammeEndDate', 'education_grade_id']
                ]
            ])
            ->add('institution_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution'],
                'on' => 'create'
            ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        return $events;
    }

    public function studentsAfterSave(EventInterface $event, $student)
    {
        if ($student->isNew()) {
            // close other pending RECEIVING transfer applications (in same education system) if the student is successfully transferred in one school
            $this->rejectPendingTransferRequests($this->getRegistryAlias(), $student);
        }
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

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $this->field('requested_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->field('institution_id', ['type' => 'hidden']);
        $this->field('academic_period_id', ['type' => 'hidden']);
        $this->field('previous_academic_period_id', ['type' => 'hidden']);
        $this->field('previous_education_grade_id', ['type' => 'hidden']);
        $this->field('student_transfer_reason_id', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('previous_institution_id', ['type' => 'integer', 'sort' => ['field' => 'PreviousInstitutions.code']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'previous_institution_id', 'start_date', 'education_grade_id', 'institution_class_id']);

        // back button
        $this->addStudentsExtraButtons($extra['toolbarButtons']); // POCOR-9155
    }


    private function addStudentsExtraButtons($toolbarButtons1): void  // POCOR-9155
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
            $extraButtons['bulkTransferIn'] = [
                'permission' => ['Institutions', 'Transfer', 'add'],
                'action' => 'BulkStudentTransferIn',
                'next_action' => 'edit',
                'icon' => '<i class="fa kd-transfer"></i>',
                'title' => __('Bulk Student Transfer In')
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
    /**
     * @return bool
     */
    private function checkUserAccessForPendingTransferIn()
    {
        $check = false;
        $newQ = clone $this->query();
        $institutionId = $this->getInstitutionID();
        $newQ->find('InstitutionStudentTransferIn', ['institution_id' => $institutionId]);
        $newQ->where(['Statuses.category' => self::IN_PROGRESS]);
        $one_req = $newQ->find('all')->first();
        if ($one_req) {
            $status_id = $one_req->status_id;
//            $this->log($status_id, 'debug');
        } else {
            return false;
        }
        $session = $this->Session;
        $superAdmin = $session->read('Auth.User.super_admin');
        if ($superAdmin) {
            return true;
        }
        $roleIds = [];
        $event = $this->dispatchEvent('Workflow.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roleIds = $event->getResult();
        } else {
            $roles = $this->AccessControl->getRolesByUser()->toArray();
            foreach ($roles as $key => $role) {
                $roleIds[$role->security_role_id] = $role->security_role_id;
            }
        }
        if (empty($roleIds)) {
            $roleIds = [0];
        }
//        $this->log($roleIds);
        $all_steps_and_roles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
        $distinct_step = $all_steps_and_roles->find()
            ->select(['workflow_step_id'])
            ->where(['workflow_step_id' => $status_id,
                'security_role_id IN' => $roleIds])
            ->distinct(['workflow_step_id'])
            ->first();
//        $this->log($distinct_step);
        if ($distinct_step) {
//            $this->log($distinct_step, 'debug');
            $check = true;
        }
//        $this->log($check, 'debug');
        return $check;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $paramInstituionId = $this->request->getAttribute('params')['institutionId'];
        $getInstitutionId = $this->getQueryString('institution_id');
        $institutionId = isset($paramInstituionId) ? $this->paramsDecode($paramInstituionId)['id'] : $getInstitutionId;

        $query->find('InstitutionStudentTransferIn', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'PreviousInstitutions.code', 'start_date'];
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
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $selectedAcademicPeriodData = $this->AcademicPeriods->get($entity->academic_period_id);

        //$entity->start_date = $selectedAcademicPeriodData->start_date;
        $entity->end_date = $selectedAcademicPeriodData->end_date;
        $this->addSections();
        if (empty($entity->requested_date)) {
            $this->field('requested_date', ['type' => 'hidden']);
        }

        $this->setFieldOrder([
            'status_id', 'assignee_id',
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_academic_period_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'education_grade_id', 'institution_id', 'institution_class_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
        //POCOR-5944 starts
        $statusId = $entity['status']->id;
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
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
                            'url' => [0 => 'index', 1 => $encodedQueryString]
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
                            'url' => [0 => 'index', 1 => $encodedQueryString]
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

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'EducationGrades', 'PreviousEducationGrades', 'AcademicPeriods', 'PreviousAcademicPeriods']);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $selectedAcademicPeriodData = $this->AcademicPeriods->get($entity->academic_period_id);

        //$entity->start_date = $selectedAcademicPeriodData->start_date;
        $entity->end_date = $selectedAcademicPeriodData->end_date;
        $this->addSections();
        $this->field('student_id', [
            'type' => 'readonly',
            'value' => $entity->student_id,
            'attr' => ['value' => $entity->user->name_with_id]
        ]);
        $this->field('previous_institution_id', [
            'type' => 'readonly',
            'value' => $entity->previous_institution_id,
            'attr' => ['value' => $entity->previous_institution->code_name]
        ]);
        $this->field('previous_academic_period_id', [
            'type' => 'readonly',
            'value' => $entity->previous_academic_period_id,
            'attr' => ['value' => $entity->previous_academic_period->name]
        ]);
        $this->field('previous_education_grade_id', [
            'type' => 'readonly',
            'value' => $entity->previous_education_grade_id,
            'attr' => ['value' => $entity->previous_education_grade->code_name]
        ]);
        $this->field('requested_date', ['entity' => $entity]);
        $this->field('academic_period_id', [
            'type' => 'readonly',
            'value' => $entity->academic_period_id,
            'attr' => ['value' => $entity->academic_period->name]
        ]);
        $this->field('education_grade_id', [
            'type' => 'readonly',
            'value' => $entity->education_grade_id,
            'attr' => ['value' => $entity->education_grade->programme_grade_name]
        ]);
        $this->field('institution_id', [
            'type' => 'readonly',
            'value' => $entity->institution_id,
            'attr' => ['value' => $entity->institution->code_name]
        ]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->field('institution_class_id', ['entity' => $entity]);
        $this->field('student_transfer_reason_id', ['type' => 'select']);

        $this->setFieldOrder([
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_academic_period_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'education_grade_id', 'institution_id', 'institution_class_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $studentId = $entity->student_id;
        $institutionId = $entity->institution_id;
        $academicPeriodId = $entity->academic_period_id;
        $startDate = $entity->start_date;
        $newDate = date("Y-m-d", strtotime($startDate));
        $endDate = $entity->end_date;
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $query = $institutionStudents->query();
        $query->update()
            ->set(['start_date' => $newDate])
            ->where(['institution_id' => $institutionId, 'student_id' => $studentId, 'academic_period_id' => $academicPeriodId])
            ->execute();

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

    public function onUpdateFieldRequestedDate(EventInterface $event, array $attr, $action, $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            if (!empty($entity->requested_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->requested_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->requested_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            $classOptions = $this->InstitutionClasses->find('list')
                ->find('classOptions', [
                    'institution_id' => $entity->institution_id,
                    'academic_period_id' => $entity->academic_period_id,
                    'grade_id' => $entity->education_grade_id
                ])
                ->toArray();

            if (empty($classOptions)) {
                $classOptions = ['' => __('No Available Classes')];
            } else {
                $classOptions = ['' => '-- ' . __('Select') . ' --'] + $classOptions;
            }

            $attr['type'] = 'select';
            $attr['options'] = $classOptions;
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
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

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            if (empty($entity->end_date)) {
                $endDate = $this->AcademicPeriods->get($entity->academic_period_id)->end_date;
            } else {
                $endDate = $entity->end_date;
            }

            $attr['type'] = 'readonly';
            $attr['value'] = $endDate->format('Y-m-d');
            $attr['attr']['value'] = $this->formatDate($endDate);
            return $attr;
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
        $incomingInstitution = self::INCOMING;

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
            ->matching($Statuses->getAlias() . '.' . $StepsParams->getAlias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $incomingInstitution) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus,
                    $StepsParams->aliasField('name') => 'institution_owner',
                    $StepsParams->aliasField('value') => $incomingInstitution
                ]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentTransferIn',
                        '0' => 'view',
                        '1' => $this->paramsEncode(['id' => $row->id, //POCOR-8642
                         'institution_id' => $row->institution_id]),
                        // '1' => $encodedQueryString,
                        // '2' => $this->paramsEncode(['id' => $row->id]),
                        // // 'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Transfer of student %s from %s'), $row->user->name_with_id, $row->previous_institution->code_name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // change in  POCOR-7027 add auto assign
        $assigneeOptions = [$this->Auth->user('id') => __('Auto Assign')]; //POCOR-7080
        $attr['options'] = $assigneeOptions;
        $attr['onChangeReload'] = 'changeStatus';
        return $attr;
    }
}
