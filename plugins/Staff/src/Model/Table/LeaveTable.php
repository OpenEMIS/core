<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;

class LeaveTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
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
        $this->addBehavior('Excel', [
            'excludes' => ['file_name'],
            'pages' => ['index'],
            'auto_contain' => false,
            'autoFields' => false,
        ]);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->controller->name !== 'Directories') {
            $this->removeBehavior('Excel');
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => ['index' => false, 'view' => true]]);
        $this->field('full_day', ['visible' => ['index' => false, 'view' => true]]);
        $this->field('start_time', ['visible' => ['index' => false, 'view' => true]]);
        $this->field('end_time', ['visible' => ['index' => false, 'view' => true]]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('time', ['after' => 'date_to']);
        $this->setFieldOrder(['status_id','assignee_id','institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'time', 'full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);

        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['status_id','assignee_id','institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'start_time', 'end_time','full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);
    }

    public function indexHistoricalBeforeQuery(Event $event, Query $mainQuery, Query $historicalQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $session = $this->request->session();

        if ($this->controller->name === 'Directories') {
            $userId = $session->read('Directory.Directories.id');
        } elseif ($this->controller->name === 'Profiles') {
            $userId = $this->Auth->user('id');
        }

        $extra['auto_contain'] = false;

        $select = [
            $this->aliasField('id'),
            $this->aliasField('is_historical'),
            $this->aliasField('date_from'),
            $this->aliasField('date_to'),
            $this->aliasField('comments'),
            $this->aliasField('number_of_days'),
            $this->aliasField('start_time'),
            $this->aliasField('end_time'),
            $this->aliasField('full_day')
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
                $this->Users->aliasField('id'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
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
                $this->aliasField('staff_id') => $userId
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
                'user_id' => 'Users.id',
                'user_openemis_no' => 'Users.openemis_no',
                'user_first_name' => 'Users.first_name',
                'user_middle_name' => 'Users.middle_name',
                'user_third_name' => 'Users.third_name',
                'user_last_name' => 'Users.last_name',
                'user_preferred_name' => 'Users.preferred_name',
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
                $HistoricalTable->aliasField('staff_id') => $userId
            ]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $this->dispatchEvent('Excel.Historical.beforeQuery', [$query, new ArrayObject([])], $this);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'Leave.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID'
        ];
        $newFields[] = [
            'key' => 'Leave.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Leave.status_id',
            'field' => 'status_id',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.assignee_id',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.staff_leave_type_id',
            'field' => 'staff_leave_type_id',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Leave.date_from',
            'field' => 'date_from',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.date_to',
            'field' => 'date_to',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.start_time',
            'field' => 'start_time',

            'type' => 'time',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.end_time',
            'field' => 'end_time',
            'type' => 'time',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.full_day',
            'field' => 'full_day',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.comments',
            'field' => 'comments',
            'type' => 'text',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Leave.number_of_days',
            'field' => 'number_of_days',
            'type' => 'decimal',
            'label' => '',
        ];

        $fields->exchangeArray($newFields);
    }
    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->openemis_no;
    }

    public function onExcelGetName(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->name;
    }

    public function onExcelGetStaffLeaveTypeId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_leave_type');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetFullDay(Event $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
    }

    public function onExcelRenderTime(Event $event, Entity $entity, $attr)
    {
        $searchKey = $attr['field'];
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, $searchKey);
        $attr['value'] = $this->formatTime($rowEntity);
        return $attr;
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onExcelGetAssigneeId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'assignee');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'academic_period');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetStatusId(Event $event, Entity $entity)
    {
        if ($entity->is_historical){
            $statusName = 'Historical';
        } else {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'status');
            $statusName = $rowEntity->name;
        }
        return $statusName;
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
            return $rowEntity->code_name;
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

    public function onGetStartTime(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->start_time;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'start_time');
            return $rowEntity;
        }
    }

    public function onGetEndTime(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->end_time;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'end_time');
            return $rowEntity;
        }
    }

    public function onGetFullDay(Event $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
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

    public function onGetTime(Event $event, Entity $entity)
    {
        $time = '-';
        $isFullDay = $this->getFieldEntity($entity->is_historical, $entity->id, 'full_day');
        if($entity->full_day == 0){
            $startTime = $this->getFieldEntity($entity->is_historical, $entity->id, 'start_time');
            $endTime = $this->getFieldEntity($entity->is_historical, $entity->id, 'end_time');
            $time = $this->formatTime($startTime). ' - '. $this->formatTime($endTime);
        }
        return $time;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            if ($entity->is_historical) {
                $rowEntityId = $this->getFieldEntity($entity->is_historical, $entity->id, 'id');
                if ($this->controller->name === 'Directories') {
                     $url = [
                        'plugin' => 'Directory',
                        'controller' => $this->controller->name,
                        'action' => 'HistoricalStaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $rowEntityId])
                    ];
                } elseif ($this->controller->name === 'Profiles') {
                    $url = [
                        'plugin' => 'Profile',
                        'controller' => $this->controller->name,
                        'action' => 'HistoricalStaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $rowEntityId])
                    ];
                }
            } else {
                $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
                $institutionId = $rowEntity->id;
                if ($this->controller->name === 'Directories') {
                    $url = [
                        'plugin' => 'Directory',
                        'controller' =>  $this->controller->name,
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $entity->id]),
                        'institution_id' => $institutionId,
                    ];
                } elseif ($this->controller->name === 'Profiles') {
                    $url = [
                        'plugin' => 'Profile',
                        'controller' => $this->controller->name,
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $entity->id]),
                        'institution_id' => $institutionId,
                    ];
                }
            }
            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }
}
