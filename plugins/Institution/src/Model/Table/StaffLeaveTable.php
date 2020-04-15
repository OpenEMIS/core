<?php
namespace Institution\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\I18n\Time;


use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;

class StaffLeaveTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Historical.Historical', [
                'historicalUrl' => [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'HistoricalStaffLeave',
                ],
                'originUrl' => [
                    'action' => 'StaffLeave',
                    'type' => 'staff'
                ],
                'model' => 'Historical.HistoricalStaffLeave',
                'allowedController' => ['Directories']
            ]
        );

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportStaffLeave']);
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
        $this->fullDayOptions = $this->getSelectOptions('general.yesno');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $allowOutAcademicYear = $ConfigItems->value('allow_out_academic_year');

        if ($allowOutAcademicYear == 1) {
            $validator
            ->add('date_to', 'ruleDateToInRange', [
                'rule' => ['DateToInRange'],
                'message' => __('Date to is greater than number of year range')
            ]);
        } else {
            $validator
            ->add('date_to', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ]);
        }
        
        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])  
            ->add('date_from', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ])
            ->add('date_from', 'leavePeriodOverlap', [
                'rule' => ['noOverlappingStaffAttendance']
            ])
            ->allowEmpty('file_content');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $staffId = $entity['staff_id'];
        $institutionId = $entity['institution_id'];
        $dateFrom = $entity['date_from']->format('Y-m-d');
        $dateTo = $entity['date_to']->format('Y-m-d');
        $entity = $this->getNumberOfDays($entity);
        
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $staffData = $InstitutionStaff
            ->find('all')
            ->select([$StaffStatuses->aliasField('code')])
            ->select($InstitutionStaff)
            ->leftJoin(
                [$StaffStatuses->alias() => $StaffStatuses->table()],
                [
                    $StaffStatuses->aliasField('id = ') . $InstitutionStaff->aliasField('staff_status_id')
                ]
            )
            ->where([
                        $InstitutionStaff->aliasField('institution_id = ') => $institutionId,
                        $InstitutionStaff->aliasField('staff_id = ') => $staffId
                    ])
            ->order([
                     $StaffStatuses->aliasField('code') => 'ASC'
                   ])
            ->first();
            $startDate = $staffData->start_date->format('Y-m-d');
            if ($startDate > $dateFrom) {
            $this->Alert->error('AlertRules.StaffLeave.noLeave', ['reset' => true]);
           return false;
        }
        
        if (!empty($staffData->end_date)) {
            $endDate = $staffData->end_date->format('Y-m-d');
            if ($startDate > $dateFrom) {
                $this->Alert->error('AlertRules.StaffLeave.noLeave', ['reset' => true]);
                return false;
            } 
                if ($dateFrom > $endDate) {
                    $this->Alert->error('AlertRules.StaffLeave.noLeaveEndDate', ['reset' => true]);
                    return false;
                } else if ($dateTo > $endDate) {
                    $this->Alert->error('AlertRules.StaffLeave.noLeaveEndDateTo', ['reset' => true]);
                    return false;
                }
            }
        if (!$entity) {
            // Error message to tell that leave period applied has overlapped exisiting leave records.
            $this->Alert->error('AlertRules.StaffLeave.leavePeriodOverlap', ['reset' => true]);
            return false;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit', 'delete'])) {
            $modelAlias = 'Staff Leave';
            $userType = 'StaffUser';
            $this->controller->changeUserHeader($this, $modelAlias, $userType);
        }

        $this->field('number_of_days', [
            'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
        ]);
        $this->field('file_name', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('file_content', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('full_day', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('end_academic_period_id', ['visible' => false]);
        $this->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'time', 'start_time', 'full_day', 'end_time', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('start_time', ['visible' => false]);
        $this->field('end_time', ['visible' => false]);
        $this->field('time', ['after' => 'date_to']);
    }

    public function indexHistoricalBeforeQuery(Event $event, Query $mainQuery, Query $historicalQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        if ($session->check('Staff.Staff.id')) {
            $userId = $session->read('Staff.Staff.id');
        } elseif (isset($this->request->query['user_id'])) {
            $userId = $this->request->query['user_id'];
        }

        $extra['auto_contain'] = false;

        $select = [
            $this->aliasField('id'),
            $this->aliasField('is_historical'),
            $this->aliasField('date_from'),
            $this->aliasField('date_to'),
            $this->aliasField('comments'),
            $this->aliasField('number_of_days')
        ];
        $selectList->exchangeArray($select);

        $order = ['date_from' => 'DESC'];
        $defaultOrder->exchangeArray($order);

        $mainQuery
            ->select([
                'id' => $this->aliasField('id'),
                'date_from' => $this->aliasField('date_from'),
                'date_to' => $this->aliasField('date_to'),
                'start_time' => $this->aliasField('start_time'),
                'end_time' => $this->aliasField('end_time'),
                'full_day' => $this->aliasField('full_day'),
                'comments' => $this->aliasField('comments'),
                'staff_id' => $this->aliasField('staff_id'),
                'staff_leave_type_id' => $this->aliasField('staff_leave_type_id'),
                'assignee_id' => $this->aliasField('assignee_id'),
                'academic_period_id' =>$this->aliasField('academic_period_id'),
                'status_id' => $this->aliasField('status_id'),
                'number_of_days' => $this->aliasField('number_of_days'),
                'institution_id' => $this->aliasField('institution_id'),
                $this->Institutions->aliasField('id'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->AcademicPeriods->aliasField('name'),
                $this->StaffLeaveTypes->aliasField('id'),
                $this->StaffLeaveTypes->aliasField('name'),
                $this->Statuses->aliasField('id'),
                $this->Statuses->aliasField('name'),
                $this->Assignees->aliasField('id'),
                $this->Assignees->aliasField('first_name'),
                $this->Assignees->aliasField('middle_name'),
                $this->Assignees->aliasField('third_name'),
                $this->Assignees->aliasField('last_name'),
                $this->Assignees->aliasField('preferred_name'),
                'is_historical' => 0
            ], true)
            ->contain([
                'Institutions',
                'AcademicPeriods',
                'StaffLeaveTypes',
                'Users',
                'Assignees',
                'Statuses'
            ])
            ->where([
                $this->aliasField('staff_id') => $userId,
                $this->aliasField('institution_id') => $institutionId
            ]);

        $HistoricalTable = $historicalQuery->repository();
        $historicalQuery
            ->select([
                'id' => $HistoricalTable->aliasField('id'),
                'date_from' => $HistoricalTable->aliasField('date_from'),
                'date_to' => $HistoricalTable->aliasField('date_to'),
                'start_time' => $HistoricalTable->aliasField('start_time'),
                'end_time' => $HistoricalTable->aliasField('end_time'),
                'full_day' => $HistoricalTable->aliasField('full_day'),
                'comments' => $HistoricalTable->aliasField('comments'),
                'staff_id' => $HistoricalTable->aliasField('staff_id'),
                'staff_leave_type_id' => $HistoricalTable->aliasField('staff_leave_type_id'),
                'assignee_id' => '(null)',
                'leave_academic_period_id' => '(null)',
                'status_id' => '(null)',
                'number_of_days' => $HistoricalTable->aliasField('number_of_days'),
                'leave_institution_id' => $HistoricalTable->aliasField('institution_id'),
                'institution_id' => 'Institutions.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'academic_period_id' =>  '(null)',
                'leave_type_id' => 'StaffLeaveTypes.id',
                'leave_type_name' => 'StaffLeaveTypes.name',
                'statuses_id' => '(null)',
                'statuses_name' => '(null)',
                'assignee_user_id' => '(null)',
                'assignee_user_first_name' => '(null)',
                'assignee_user_middle_name' => '(null)',
                'assignee_user_third_name' => '(null)',
                'assignee_user_last_name' => '(null)',
                'assignee_user_preferred_name' => '(null)',
                'is_historical' => 1
            ])
            ->contain([
                'Users',
                'StaffLeaveTypes',
                'Institutions'
            ])
            ->where([
                $HistoricalTable->aliasField('staff_id') => $userId,
                $HistoricalTable->aliasField('institution_id') => $institutionId
            ]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('assignee_id', ['entity' => $entity]); //send entity information
        $this->field('start_time', ['entity' => $entity]);
        $this->field('end_time', ['entity' => $entity]);
        $this->field('academic_period_id', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
            'entity' => $entity
        ]);

        // after $this->field(), field ordering will mess up, so need to reset the field order
        $this->setFieldOrder(['staff_leave_type_id', 'academic_period_id','date_from', 'date_to', 'full_day', 'start_time', 'end_time','number_of_days', 'comments', 'file_name', 'file_content', 'assignee_id']);
    }

    public function onGetTime(Event $event, Entity $entity) {
        $time = '-';
        $isFullDay = $this->getFieldEntity($entity->is_historical, $entity->id, 'full_day');
        if($entity->full_day == 0){
            $startTime = $this->getFieldEntity($entity->is_historical, $entity->id, 'start_time');
            $endTime = $this->getFieldEntity($entity->is_historical, $entity->id, 'end_time');
            $time = $this->formatTime($startTime). ' - '. $this->formatTime($endTime);
        }
        return $time;
    }

    public function onGetFullDay(Event $event, Entity $entity)
    {
        return $this->fullDayOptions[$entity->full_day];
    }

    public function onGetStatusId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $statusName = $entity->status->name;
        } elseif ($this->action == 'index') {
            if ($entity->is_historical){
                $statusName = 'Historical';
            } else {
                $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'status');
                $statusName = $rowEntity->name;
            }
        }
        return '<span class="status highlight">' . $statusName . '</span>';
    }

    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->assignee->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'assignee');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->institution->code_name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
            if ($entity->is_historical) {
                return $rowEntity->name;
            } else {
                return $rowEntity->code_name;
            }
        }
    }

    public function onGetStaffLeaveTypeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->staff_leave_type->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_leave_type');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->academic_period->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'academic_period');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'hidden';
        } else if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $userId = $this->getUserId();

            $attr['value'] = $userId;
        }

        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($entity->isNew()) {
                $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
                $attr['value'] = $currentAcademicPeriodId;
                $attr['attr']['value'] = $currentAcademicPeriodId;
             }

            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        }
        return $attr;
    }

    public function onUpdateFieldFullDay(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->fullDayOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['full_day'])) {
                if ($request->data[$this->alias()]['full_day']) {
                    $attr['type'] = 'hidden';
                }
            } else {
                $attr['type'] = 'hidden';
            }
        } else if ($action == 'edit') {
            $fullDay = $attr['entity']->full_day;
            if ($fullDay) {
                $attr['type'] = 'hidden';
            }
        }
        return $attr;
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['full_day'])) {
                if ($request->data[$this->alias()]['full_day']) {
                    $attr['type'] = 'hidden';
                }
            } else {
                $attr['type'] = 'hidden';
            }
        } else if ($action == 'edit') {
            $fullDay = $attr['entity']->full_day;
            if ($fullDay) {
                $attr['type'] = 'hidden';
            }
        }
        return $attr;
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->getUserId();
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function getUserId()
    {
        $userId = null;
        if (!is_null($this->request->query('user_id'))) {
            $userId = $this->request->query('user_id');
        } else {
            $session = $this->request->session();
            if ($session->check('Staff.Staff.id')) {
                $userId = $session->read('Staff.Staff.id');
            }
        }

        return $userId;
    }

    public function institutionStaffAfterDelete(Event $event, Entity $institutionStaffEntity)
    {
        $staffLeaveData = $this->find()
            ->where([
                $this->aliasField('staff_id') => $institutionStaffEntity->staff_id,
                $this->aliasField('institution_id') => $institutionStaffEntity->institution_id,
            ])
            ->toArray();

        foreach ($staffLeaveData as $key => $staffLeaveEntity) {
            $this->delete($staffLeaveEntity);
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
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
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
                $this->StaffLeaveTypes->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->StaffLeaveTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
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
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id,
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s of %s'), $row->staff_leave_type->name, $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            1 => ('DATEDIFF(' . $this->aliasField('date_to') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
            // 2 => ('DATEDIFF(NOW(), ' . $this->aliasField('date_to') . ')' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'StaffLeaveTypes.name',
                'date_from',
                'date_to',
                'Institutions.id',
                'Institutions.name',
                'Institutions.code',
                'Institutions.address',
                'Institutions.postal_code',
                'Institutions.contact_person',
                'Institutions.telephone',
                'Institutions.fax',
                'Institutions.email',
                'Institutions.website',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth'
            ])
            ->contain(['Statuses', 'Users', 'Institutions', 'StaffLeaveTypes', 'Assignees'])
            ->where([
                $this->aliasField('staff_leave_type_id') => $thresholdArray['staff_leave_type'],
                $this->aliasField('date_to') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }

    private function checkDateInRange($start_date, $end_date, $comparison_date)
    {
        return (($comparison_date >= $start_date) && ($comparison_date <= $end_date));
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            if ($entity->is_historical) {
                $rowEntityId = $this->getFieldEntity($entity->is_historical, $entity->id, 'id');
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'HistoricalStaffLeave',
                    'view',
                    $this->paramsEncode(['id' => $rowEntityId])
                ];
                $buttons['view']['url'] = $url;
                if (array_key_exists('edit', $buttons)) {
                    unset($buttons['edit']);
                }
                if (array_key_exists('remove', $buttons)) {
                    unset($buttons['remove']);
                }
            }
        }
        return $buttons;
    }

    public function getNumberOfDays(Entity $entity)
    {
        $dateFrom = date_create($entity->date_from);
        $dateTo = date_create($entity->date_to);
        $staffId = $entity->staff_id;
        $institutionId = $entity->institution_id;
        $academicPeriodId = $entity->academic_period_id;
        $isFullDayLeave = $entity->full_day;
        /*
            Non full day leave is always assume to be 0.5 since staff can only apply 2 non full day leave
            Set start_time and end_time to null, in the case when user first choose Full Day = No and then Full Day = Yes. If start_time and end_time is not set to null, the start_time and end_time will be saved which shouldn't be the case.
        */
        if ($isFullDayLeave == 1) {
            $day = 1;
            $entity->start_time = null;
            $entity->end_time = null;
        } else {
            $day = 0.5;
        }
        $entityStartTime = $entity->start_time;
        $entityEndTime = $entity->end_time;

        $existingConditions = [
            $this->aliasField('staff_id') => $staffId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
        ];

        if (!$entity->isNew()) {
            $existingConditions[$this->aliasField('id !=')] = $entity->id;
        }

        $exisitingLeaveRecords = $this
            ->find()
            ->select([
                $this->aliasField('id'),
                $this->aliasField('date_from'),
                $this->aliasField('date_to'),
                $this->aliasField('full_day'),
                $this->aliasField('start_time'),
                $this->aliasField('end_time'),
            ])
            ->where($existingConditions)
            ->toArray();

        $workingDaysOfWeek = $this->AcademicPeriods->getWorkingDaysOfWeek();

        $startDate = $dateFrom;
        $endDate = $dateTo;
        $endDate = $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($startDate, $interval, $endDate);
        $CalendarEvents = TableRegistry::get('Calendars');
        $CalendarTypes = TableRegistry::get('CalendarTypes');
        $publicCalendarEvents = $CalendarEvents
                                ->find('all')
                                ->contain(['CalendarTypes','CalendarEventDates'])
                                ->join([
                                    [
                                        'type' => 'left',
                                        'table' => 'calendar_types',
                                        'conditions' => [
                                            $CalendarEvents->aliasField('calendar_type_id') => $CalendarTypes->aliasField('id')
                                        ]
                                    ]
                                ])                                
                                ->where([
                                    $CalendarEvents->aliasField('institution_id') => $institutionId])                               
                                ->orWhere([ 
                                    $CalendarEvents->aliasField('institution_id') => -1
                                ])
                                ->andWhere([
                                    $CalendarTypes->aliasField('code') => 'PUBLICHOLIDAY'
                                ])
                                ->toArray();
        $collection = new Collection($publicCalendarEvents);

        $mapCollection = $collection->map(function ($value, $key) {
            return $value['calendar_event_dates'][0]['date']->format('Y-m-d');
        });
        $publicCalendarEventDates = $mapCollection->toArray();
        
        $count = 0;
        $overlap = false;
        foreach ($datePeriod as $key => $date) {
            if (!in_array($date->format('Y-m-d'), $publicCalendarEventDates)) {
                $dayText = $date->format('l');
                if (in_array($dayText, $workingDaysOfWeek)) {
                    $count = $count + $day;
                    foreach ($exisitingLeaveRecords as $key => $value) {
                        $comparisonId = $value->id;
                        $dateFromStr = $value->date_from->format("Y-m-d");
                        $dateToStr = $value->date_to->format("Y-m-d");
                        $comparisonDateStr = $date->format("Y-m-d");
                        $comparisonStartTime = $this->formatTime($value->start_time);
                        $comparisonEndTime = $this->formatTime($value->end_time);
                        $comparisonFullDay = $value->full_day;
                        $isDateInRange = $this->checkDateInRange($dateFromStr, $dateToStr, $comparisonDateStr);

                        if ($isDateInRange) {
                            //If leave date applied overlaps existing records and both are non full day leave, check for overlapping in time.
                            if($comparisonFullDay == 0 && $isFullDayLeave == 0){
                                $existingConditions[$this->aliasField('date_from >=')] = $comparisonDateStr;
                                $existingConditions[$this->aliasField('date_to <=')] = $comparisonDateStr;
                                $overlapHalfDayLeaveRecords = $this
                                ->find()
                                ->where([$existingConditions])
                                ->count();
                                if ($overlapHalfDayLeaveRecords >= 2) {
                                    $overlap = true;
                                    break;
                                } else if (($comparisonStartTime <= $entityEndTime) && ($comparisonEndTime >= $entityStartTime)) {
                                   // Overlapping in time found
                                   $overlap = true;
                                   break;
                                }
                            } else {
                                $overlap = true;
                                break;
                            }
                        }
                    }
                }
                if ($overlap) {
                    break;
                }
            }
        }
        if ($overlap) {
            return false;
        } else {
            //The number of leave days calculation only includes working day
            $entity->number_of_days = $count;
            return $entity;
        }
    }
}
