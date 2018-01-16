<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Institution\Model\Table\InstitutionStudentTransfersTable;

class StudentTransferOutTable extends InstitutionStudentTransfersTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        if ($this->behaviors()->has('Workflow')) {
            $this->behaviors()->get('Workflow')->config([
                'institution_key' => 'previous_institution_id'
            ]);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty(['requested_date']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['UpdateAssignee.onSetSchoolBasedConditions'] = 'onSetSchoolBasedConditions';
        return $events;
    }

    // to get correct set of unassigned records for each workflow model in UpdateAssigneeShell
    public function onSetSchoolBasedConditions(Event $event, Entity $entity, $where)
    {
        $where[$this->aliasField('previous_institution_id')] = $entity->id;
        unset($where[$this->aliasField('institution_id')]);
        return $where;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);
        $this->field('institution_class_id', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }

        $this->field('start_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('previous_education_grade_id', ['type' => 'hidden']);
        $this->field('student_transfer_reason_id', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('institution_id', ['type' => 'integer', 'sort' => ['field' => 'Institutions.code']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'institution_id', 'requested_date', 'academic_period_id', 'education_grade_id']);

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
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->addSections();
        $this->field('institution_id', ['type' => 'integer']);

        if (empty($entity->start_date)) {
            $this->field('start_date', ['type' => 'hidden']);
            $this->field('end_date', ['type' => 'hidden']);
        }

        $this->setFieldOrder([
            'status_id', 'assignee_id',
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'institution_id', 'education_grade_id', 'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
        $studentId = $this->getQueryString('student_id');
        $userId = $this->getQueryString('user_id');

        if (empty($studentId) || empty($userId)) {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        } else {
            // url to redirect to studentUser page
            $studentUserUrl = $this->url('view');
            $studentUserUrl['action'] = 'StudentUser';
            $studentUserUrl[1] = $this->paramsEncode(['id' => $userId]);

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
                    $this->aliasField('previous_institution_id') => $institutionId
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
                    $url[1] = $this->paramsEncode(['id' => $pendingTransfer->id]);
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                } else {
                    $this->Alert->warning($this->aliasField('existingStudentTransfer'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($studentUserUrl);
                }

            } else {
                // if no pending transfers
                $Students = TableRegistry::get('Institution.Students');
                $institutionStudentEntity = $Students->get($studentId, [
                    'contain' => ['Users', 'Institutions', 'EducationGrades', 'AcademicPeriods']
                ]);
                $this->setupFields($institutionStudentEntity);
                $extra['toolbarButtons']['back']['url'] = $studentUserUrl;
            }
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        // redirect to view page of record after save
        $extra['redirect'][0] = 'view';
        $extra['redirect'][1] = $this->paramsEncode(['id' => $entity->id]);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'EducationGrades', 'PreviousEducationGrades', 'AcademicPeriods']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupFields(Entity $entity)
    {
        $this->addSections();

        $this->field('student_id', ['entity' => $entity]);
        $this->field('previous_institution_id', ['entity' => $entity]);
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
            'previous_information_header', 'student_id', 'previous_institution_id', 'previous_education_grade_id', 'requested_date',
            'new_information_header', 'academic_period_id', 'education_grade_id', 'area_id', 'institution_id',  'start_date', 'end_date',
            'transfer_reasons_header', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->student_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldPreviousEducationGradeId(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldRequestedDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];
            $academicPeriodId = $entity->academic_period_id;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;
            $studentStartDate = $entity->start_date;
            $studentEndDate = $entity->end_date;

            // for date_options, date restriction
            $startDate = ($studentStartDate >= $periodStartDate) ? $studentStartDate: $periodStartDate;
            $endDate = ($studentEndDate <= $periodStartDate) ? $studentEndDate: $periodEndDate;

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
                'todayBtn' => false
            ];
        } elseif ($action == 'edit') {
            // using institution_student_transfer entity
            $entity = $attr['entity'];

            $academicPeriodId = $entity->academic_period_id;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            $Students = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

            $studentEntity = $Students->find()
                ->where([
                    $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                    $Students->aliasField('education_grade_id') => $entity->education_grade_id,
                    $Students->aliasField('student_id') => $entity->student_id,
                    $Students->aliasField('student_status_id') => $enrolledStatus
                ])
                ->first();

            $studentStartDate = $studentEntity->start_date;
            $studentEndDate = $studentEntity->end_date;

            // for date_options, date restriction
            $startDate = ($studentStartDate >= $periodStartDate) ? $studentStartDate: $periodStartDate;
            if (!empty($entity->start_date)) {
                $endDate = $entity->start_date->modify('-1 day');
            } else {
                $endDate = ($studentEndDate <= $periodStartDate) ? $studentEndDate: $periodEndDate;
            }

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
                'todayBtn' => false
            ];
        }
        //  else if ($action == 'associated') {
        //     $sessionKey = $this->registryAlias() . '.associated';
        //     $currentEntity = $this->Session->read($sessionKey);
        //     $requestedDate = $currentEntity->requested_date;

        //     $attr['type'] = 'readonly';
        //     $attr['value'] = $requestedDate->format('d-m-Y');
        //     $attr['attr']['value'] = $requestedDate->format('d-m-Y');
        // }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->education_grade_id;
            $attr['attr']['value'] = $entity->education_grade->programme_grade_name;
            return $attr;
        }
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $Areas = TableRegistry::get('Area.Areas');
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_student entity
                $today = Date::now();

                $selectedAcademicPeriodData = $this->AcademicPeriods->get($entity->academic_period_id);
                if ($selectedAcademicPeriodData->end_date instanceof Time || $selectedAcademicPeriodData->end_date instanceof Date) {
                    $academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
                } else {
                    $academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
                }

                $areaOptions = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->innerJoinWith('Institutions.InstitutionGrades')
                    ->where([
                        'InstitutionGrades.institution_id <>' => $entity->institution_id,
                        'InstitutionGrades.education_grade_id' => $entity->education_grade_id,
                        'InstitutionGrades.start_date <=' => $academicPeriodEndDate,
                        'OR' => [
                            'InstitutionGrades.end_date IS NULL',
                            'InstitutionGrades.end_date >=' => $today->format('Y-m-d')
                        ]
                    ])
                    ->order([$Areas->aliasField('order')]);

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $areaOptions->toArray();
                $attr['onChangeReload'] = true;
            } else {
                // using institution_student_transfer entity
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $Areas->get($entity->institution_id)->code_name;
            }
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_student entity
                $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
                $InstitutionStatuses = TableRegistry::get('Institution.Statuses');
                $today = Date::now();

                $selectedAcademicPeriodData = $this->AcademicPeriods->get($entity->academic_period_id);
                if ($selectedAcademicPeriodData->end_date instanceof Time || $selectedAcademicPeriodData->end_date instanceof Date) {
                    $academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
                } else {
                    $academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
                }

                $institutionOptions = $this->Institutions
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->innerJoin([$InstitutionGrades->alias() => $InstitutionGrades->table()], [
                        $InstitutionGrades->aliasField('institution_id =') . $this->Institutions->aliasField('id'),
                        $InstitutionGrades->aliasField('institution_id') . ' <> ' . $entity->institution_id, // Institution list will not contain the old institution.
                        $InstitutionGrades->aliasField('education_grade_id') => $entity->education_grade_id,
                        $InstitutionGrades->aliasField('start_date') . ' <= ' => $academicPeriodEndDate,
                        'OR' => [
                            $InstitutionGrades->aliasField('end_date') . ' IS NULL',
                            // Previously as long as the programme end date is later than academicPeriodStartDate, institution will be in the list.
                            // POCOR-3134 request to only displayed institution with active grades (end-date is later than today-date)
                            $InstitutionGrades->aliasField('end_date') . ' >=' => $today->format('Y-m-d')
                        ]
                    ])
                    ->where([$this->Institutions->aliasField('institution_status_id') => $InstitutionStatuses->getIdByCode('ACTIVE')])
                    ->order([$this->Institutions->aliasField('code')]);

                if (!empty($request->data[$this->alias()]['area_id'])) {
                    $institutionOptions->where([$this->Institutions->aliasField('area_id') => $request->data[$this->alias()]['area_id']]);
                }

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $institutionOptions->toArray();
            } else {
                // using institution_student_transfer entity
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            }
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
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

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

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
            ->contain([$this->Users->alias(), $this->Institutions->alias(), $this->PreviousInstitutions->alias(), $this->CreatedUser->alias()])
            ->matching($Statuses->alias().'.'.$StepsParams->alias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $outgoingInstitution) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus,
                    $StepsParams->aliasField('name') => 'institution_owner',
                    $StepsParams->aliasField('value') => $outgoingInstitution
                ]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentTransferOut',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->previous_institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s to %s'), $row->user->name_with_id, $row->institution->code_name);
                    $row['institution'] = $row->previous_institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
