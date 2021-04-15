<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Institution\Model\Table\InstitutionStudentTransfersTable;

class StudentTransferInTable extends InstitutionStudentTransfersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Students' => ['index', 'add']
        ]);

        if ($this->behaviors()->has('Workflow')) {
            $this->behaviors()->get('Workflow')->config([
                'institution_key' => 'institution_id'
            ]);
        }

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['start_date', 'workflow_assignee_id'])
            ->add('start_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'requested_date', false],
                    'on' => function ($context) {
                        return array_key_exists('requested_date', $context['data']) && !empty($context['data']['requested_date']);
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

    public function validationBulkTransfer(Validator $validator)
    {
        // requested_date is not relevent for transfer of promoted/graduated students
        $validator = $this->validationDefault($validator);
        return $validator->remove('start_date', 'ruleCompareDateReverse');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        return $events;
    }

    public function studentsAfterSave(Event $event, $student)
    {
        if ($student->isNew()) {
            // close other pending RECEIVING transfer applications (in same education system) if the student is successfully transferred in one school
            $this->rejectPendingTransferRequests($this->registryAlias(), $student);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
        $toolbarButtonsArray['back']['url']['plugin'] = 'Institution';
        $toolbarButtonsArray['back']['url']['controller'] = 'Institutions';
        $toolbarButtonsArray['back']['url']['action'] = 'Students';
        $toolbarButtonsArray['back']['url'][0] = 'index';
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End

        // Start bulk Student Transfer In button POCOR-5677 start
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'BulkStudentTransferIn',
            'edit'
        ];
        $toolbarButtonsArray['bulkAdmission'] = $this->getButtonTemplate();
        $toolbarButtonsArray['bulkAdmission']['label'] = '<i class="fa kd-transfer"></i>';
        $toolbarButtonsArray['bulkAdmission']['attr']['title'] = __('Bulk Student Transfer In');
        $toolbarButtonsArray['bulkAdmission']['url'] = $url;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End bulk Student Transfer In button POCOR-5677 end
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
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
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'EducationGrades', 'PreviousEducationGrades', 'AcademicPeriods', 'PreviousAcademicPeriods']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
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
            'new_information_header', 'academic_period_id', 'education_grade_id', 'institution_id',  'institution_class_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $options)
    {   
        $studentId = $entity->student_id;
        $institutionId = $entity->institution_id;
        $academicPeriodId = $entity->academic_period_id;
        $startDate = $entity->start_date;
        $newDate = date("Y-m-d", strtotime($startDate));
        $endDate = $entity->end_date;
        $institutionStudents = TableRegistry::get('institution_students');
        $query = $institutionStudents->query();
        $query->update()
                ->set(['start_date' => $newDate])
                ->where(['institution_id' => $institutionId, 'student_id' => $studentId, 'academic_period_id' => $academicPeriodId])
                ->execute();
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('previous_academic_period_id') && $data->offsetExists('academic_period_id')) {
            $previousAcademicPeriodId = $data->offsetGet('previous_academic_period_id');
            $academicPeriodId = $data->offsetGet('academic_period_id');
            if ($previousAcademicPeriodId != $academicPeriodId) {
                $options['validate'] = 'bulkTransfer';
            }
        }
    }

    public function onUpdateFieldRequestedDate(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
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

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
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
        $session = $controller->request->session();

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
            ->contain([$this->Users->alias(), $this->Institutions->alias(), $this->PreviousInstitutions->alias(), $this->CreatedUser->alias()])
            ->matching($Statuses->alias().'.'.$StepsParams->alias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $incomingInstitution) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus,
                    $StepsParams->aliasField('name') => 'institution_owner',
                    $StepsParams->aliasField('value') => $incomingInstitution
                ]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentTransferIn',
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
                    $row['request_title'] = sprintf(__('Transfer of student %s from %s'), $row->user->name_with_id, $row->previous_institution->code_name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
