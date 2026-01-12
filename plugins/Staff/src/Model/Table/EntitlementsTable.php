<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class EntitlementsTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_leave_entitlements');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])
            ->add('date_to', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ])
            ->add('date_from', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ])
            ->allowEmpty('file_content');
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $StaffLeave = TableRegistry::getTableLocator()->get('Institution.StaffLeave');
        $entity = $StaffLeave->getNumberOfDays($entity);
        if (!$entity) {
            // Error message to tell that leave period applied has overlapped exisiting leave records.
            $this->Alert->error('AlertRules.StaffLeave.leavePeriodOverlap', ['reset' => true]);
            return false;
        }
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-7485 use for remove reserved LEAVE keyword starts
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        //POCOR-7485 ends
        if ($this->controller->getName() !== 'Directories') {
             $this->removeBehavior('Excel');
             if (isset($extra['toolbarButtons']['export'])) {
                 unset($extra['toolbarButtons']['export']);
             }
        }
        if ($this->controller->getName() !== 'Profiles') {
            $this->removeBehavior('Workflow');
        }

        if ($this->controller->getName() == 'Profiles' && $this->action == 'index') {
            $this->removeBehavior('Workflow');
        }

        // $this->field('institution_id', ['visible' => ['index' => false, 'add' => true, 'view' => true, 'edit' => false]]);
        $this->field('number_of_days', ['visible' => ['index' => true, 'view' => true, 'edit' => false]]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => ['index' => false, 'view' => true,  'edit' => true]]);
        $this->field('full_day', ['visible' => ['index' => false, 'view' => true, 'edit' => true]]);

        // Start pocor-5188
        $is_manual_exist = $this->getManualUrl('Directory','Leave','Staff - Career');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // end pocor-5188

    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('time', ['after' => 'date_to']);
        $this->setFieldOrder(['status_id','assignee_id','institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'time', 'full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);

        $options = ['type' => 'staff'];
        //$tabElements = $this->controller->getCareerTabElements($options);
        $tabElements = $this->getCareerTabElements($options);
        $controllerName = $this->controller->getName();
        $selectedAction = 'Staff'.$this->getAlias();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('start_time', ['entity' => $entity]);
        $this->field('end_time', ['entity' => $entity]);
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('academic_period_id', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
            'entity' => $entity
        ]);

        $this->setFieldOrder(['institution_id', 'staff_leave_type_id', 'academic_period_id','date_from', 'date_to', 'full_day', 'start_time', 'end_time','number_of_days', 'comments', 'file_name', 'file_content', 'assignee_id']);
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['status_id','assignee_id','institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'start_time', 'end_time','full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);
    }

    public function indexHistoricalBeforeQuery(EventInterface $event, Query $mainQuery, Query $historicalQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        $session = $this->request->getSession();

        if ($this->controller->getName() === 'Directories') {
            $userId = $session->read('Directory.Directories.id');
        } elseif ($this->controller->getName() === 'Profiles') {
            $userId = $this->Auth->user('id');
        } elseif ($this->controller->getName() === 'Staff') {
            $userId = $this->getStaffID();
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

        $HistoricalTable = $historicalQuery->getRepository();
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $this->dispatchEvent('Excel.Historical.beforeQuery', [$query, new ArrayObject([])], $this);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'Leave.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
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
    public function onExcelGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->openemis_no;
    }

    public function onExcelGetName(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->name;
    }

    public function onExcelGetStaffLeaveTypeId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_leave_type');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetFullDay(EventInterface $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
    }

    public function onExcelRenderTime(EventInterface $event, Entity $entity, $attr)
    {
        $searchKey = $attr['field'];
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, $searchKey);
        $attr['value'] = $this->formatTime($rowEntity);
        return $attr;
    }

    public function onExcelGetInstitutionId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onExcelGetAssigneeId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'assignee');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'academic_period');
        return isset($rowEntity->name) ? $rowEntity->name : '-';
    }

    public function onExcelGetStatusId(EventInterface $event, Entity $entity)
    {
        if ($entity->is_historical){
            $statusName = 'Historical';
        } else {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'status');
            $statusName = $rowEntity->name;
        }
        return $statusName;
    }

    public function onGetStatusId(EventInterface $event, Entity $entity)
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

    public function onGetAssigneeId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->assignee->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'assignee');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action != 'add' && $action != 'edit'){
            return $attr;
        }
        $attr['value'] = 0;
        $attr['type'] = 'hidden';
        return $attr;
    }

    public function onGetInstitutionId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->institution->code_name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
            return $rowEntity->code_name;
        }
    }

    public function onGetStaffLeaveTypeId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->staff_leave_type->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_leave_type');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onGetStartTime(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->start_time;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'start_time');
            return $rowEntity;
        }
    }

    public function onGetEndTime(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->end_time;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'end_time');
            return $rowEntity;
        }
    }

    public function onGetFullDay(EventInterface $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
    }

    public function onGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->academic_period->name;
        } elseif ($this->action == 'index') {
            $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'academic_period');
            return isset($rowEntity->name) ? $rowEntity->name : '-';
        }
    }

    public function onGetTime(EventInterface $event, Entity $entity)
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        }
        return $attr;
    }

    public function onUpdateFieldFullDay(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['select'] = false;
            $attr['options'] = $this->fullDayOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        if ($action == 'add' ) {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($request->getData()[$this->getAlias()]['full_day'])) {
                if ($request->getData()[$this->getAlias()]['full_day']) {
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

    public function onUpdateFieldEndTime(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData()[$this->getAlias()]['full_day'])) {
                if ($this->request->getData()[$this->getAlias()]['full_day']) {
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

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr,  $action, ServerRequest $request)
    {
        $all_instittutions = $this->Institutions->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])->select([
            $this->Institutions->aliasField('id'),
            $this->Institutions->aliasField('name')
        ])->order([
            $this->Institutions->aliasField('name ASC')
        ])->toArray();
        if ($action == 'add') {
            // at the point of doing, only Profiles can add staff leave
            if ($this->controller->getName() === 'Profiles') {
                $staffId = $this->Auth->user('id');
            }
            $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
            $institutionOptions = $StaffTable
                ->find('list', ['keyField' => 'institution.id', 'valueField' => 'institution.name'])
                ->select([
                    $this->Institutions->aliasField('id'),
                    $this->Institutions->aliasField('name')
                ])
                ->contain('Institutions')
                ->where([
                    $StaffTable->aliasField('staff_id') => $staffId,
                    $StaffTable->aliasField('staff_status_id') => 1
                ])
                ->toArray();
            $attr['type'] = 'select';
            $attr['options'] =  (empty($institutionOptions)) ? $all_instittutions : $institutionOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];
            $institutionId = $entity->institution_id;
            $attr['type'] = 'readonly';
            $attr['value'] = $institutionId;
            $attr['attr']['value'] = $this->Institutions->get($institutionId)->name;
        }
        return $attr;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        //echo "<pre>"; print_r($entity);die;
        if (isset($buttons['view'])) {
            if ($entity->is_historical) {
                $rowEntityId = $this->getFieldEntity($entity->is_historical, $entity->id, 'id');
                $buttons = $this->getHistoricalActionButtons($buttons, $rowEntityId);
                if ($this->controller->getName() === 'Directories') {
                    if(isset($buttons['view']['url'][1])) {
                        $queryString = $this->paramsDecode($buttons['view']['url'][1]);
                    }
                    $queryString['id'] = $rowEntityId;
                     $url = [
                        'plugin' => 'Directory',
                        'controller' => $this->controller->getName(),
                        'action' => 'HistoricalStaffLeave',
                        'view',
                        $this->paramsEncode($queryString)
                    ];
                } elseif ($this->controller->getName() === 'Profiles') {
                    $url = [
                        'plugin' => 'Profile',
                        'controller' => $this->controller->getName(),
                        'action' => 'HistoricalStaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $rowEntityId])
                    ];
                }
                $buttons['view']['url'] = $url;
            } else {
                $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
                $institutionId = $rowEntity->id;
                if ($this->controller->getName() === 'Directories') {
                    $url = [
                        'plugin' => 'Directory',
                        'controller' =>  $this->controller->getName(),
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $entity->id]),
                        'institution_id' => $institutionId,
                    ];
                } elseif ($this->controller->getName() === 'Profiles') {
                    $url = [
                        'plugin' => 'Profile',
                        'controller' => $this->controller->getName(),
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $entity->id]),
                        'institution_id' => $institutionId,
                    ];
                }
                $buttons['view']['url'] = $url;
            }
        }
        return $buttons;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Institution');
        } else if ($field == 'status_id') {
            return __('Status');
        } else if ($field == 'assignee_id') {
            return __('Assignee');
        } else if ($field == 'staff_leave_type_id') {
            return __('Staff Leave Type');
        } else if ($field == 'date_from') {
            return __('Date From');
        } else if ($field == 'date_to') {
            return __('Date To');
        } else if ($field == 'time') {
            return __('Time');
        } else if ($field == 'number_of_days') {
            return __('Number Of Days');
        } else if ($field == 'comments') {
            return __('Comments');
        } else if ($field == 'academic_period_id') {
            return __('Academic Period');
        } else if ($field == 'start_time') {
            return __('Start Time');
        } else if ($field == 'end_time') {
            return __('End Time');
        } else if ($field == 'full_day') {
            return __('Full Day');
        } else if ($field == 'file_content') {
            return __('File Content');
        } else if ($field == 'modified') {
            return __('Modified');
        } else if ($field == 'modified_user_id') {
            return __('Modified By');
        } else if ($field == 'created') {
            return __('Created');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
