<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Institution\Model\Table\InstitutionStaffTransfersTable;
use Cake\Log\Log;

class StaffTransferInTable extends InstitutionStaffTransfersTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Staff' => ['index', 'add']
        ]);

        $this->toggle('add', false);
        if ($this->behaviors()->has('Workflow')) {
            // $this->behaviors()->get('Workflow')->config([
            //     'institution_key' => 'new_institution_id'
            // ]);

            $reorderBehavior = $this->behaviors()->get('Workflow');
            $reorderBehavior->setConfig('institution_key', 'new_institution_id');
        }

        $this->addBehavior('Institution.InstitutionTab'
            , [
                'appliedAction' => ['StaffTransferIn' => [
                        'assignee_id',
                        'new_institution_id',
                        'previous_institution_id',
                        'transfer_type'
                    ]
                ]
            ]
        );
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('new_start_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'previous_end_date', false],
                'on' => function ($context) {
                    return array_key_exists('previous_end_date', $context['data']) && !empty($context['data']['previous_end_date']);
                }
            ])
            ->add('new_end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'new_start_date', false],
                'on' => function ($context) {
                    return array_key_exists('new_end_date', $context['data']) && !empty($context['data']['new_end_date']);
                }
            ])
            ->notEmpty(['new_institution_position_id', 'new_FTE', 'new_staff_type_id', 'new_start_date', 'workflow_assignee_id','assignee_id']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['UpdateAssignee.onSetSchoolBasedConditions'] = 'onSetSchoolBasedConditions';
        return $events;
    }

    public function onSetSchoolBasedConditions(EventInterface $event, Entity $entity, $where)
    {
        $where[$this->aliasField('new_institution_id')] = $entity->id;
        unset($where[$this->aliasField('institution_id')]);
        return $where;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-7034 start
        $url = $_SERVER['REQUEST_URI'];
        if(strpos($url, "StaffTransferIn/approve")!==false){
            $this->field('assignee_id', ['type' => 'hidden']);
        }
        //POCOR-7034 end
        parent::beforeAction($event, $extra);
        $this->field('is_homeroom'); //POCOR-7780
        $this->field('previous_institution_staff_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('transfer_type', ['type' => 'hidden']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('new_end_date', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_institution_id', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('previous_effective_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->field('is_homeroom', ['type' => 'string']); //POCOR-7780
        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('previous_institution_id', ['sort' => ['field' => 'PreviousInstitutions.code']]);
        $this->field('new_start_date', ['sort' => ['field' => 'new_start_date']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'previous_institution_id', 'new_start_date', 'new_institution_position_id']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $getInstitutionId = $this->getQueryString('institution_id');
        $requestInstitutionId = $this->request->getParam('institutionId');
        $institutionId = isset($requestInstitutionId) ? $this->paramsDecode($requestInstitutionId)['id'] : $getInstitutionId;
        //$session = $this->request->session();
        //$institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffTransferIn', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'NewInstitutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'PreviousInstitutions.code', 'new_start_date'];
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
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('previous_effective_date', ['type' => 'hidden']);

        if (empty($entity->previous_end_date)) {
            $this->field('previous_end_date', ['type' => 'hidden']);
        }
        $this->field('is_homeroom', ['entity' => $entity, 'type' => 'string']); //POCOR-7780

        $this->setFieldOrder([
            'previous_information_header', 'staff_id', 'previous_institution_id', 'previous_end_date',
            'new_information_header', 'new_institution_id', 'new_institution_position_id', 'new_staff_type_id', 'new_FTE', 'new_start_date', 'new_end_date',
            'is_homeroom', //POCOR-7780
            'transfer_reasons_header', 'comment',
            // hidden fields
            'all_visible', 'previous_effective_date', 'previous_institution_staff_id', 'previous_staff_type_id', 'previous_FTE', 'transfer_type'
        ]);
    }

    public function onGetStaffId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }
    //POCOR-7780:start
    public function onGetIsHomeroom(EventInterface $event, Entity $entity)
    {
        $this->log(print_r($entity->is_homeroom, true), 'debug');
        return ($entity->is_homeroom) ? __('Yes') : __('No');
    }
    //POCOR-7780:end

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // to allow institution_position field to be populated on first load
        $requestData = $this->request->getData();
        $requestData[$this->getAlias()]['new_institution_id'] = $entity->new_institution_id;
        $requestData[$this->getAlias()]['new_FTE'] = $entity->new_FTE;
        $requestData[$this->getAlias()]['new_start_date'] = $entity->new_start_date;
        $requestData[$this->getAlias()]['new_end_date'] = $entity->new_end_date;
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'NewInstitutions', 'PreviousInstitutions']);
    }
    //POCOR-7870:start
    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $this->field('is_homeroom',[
            'type'=>'select'
        ]);
        $this->fields['is_homeroom']['options'] = [0=>'No',1=>'Yes'];
    }
    //POCOR-7870:end
    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('staff_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('previous_institution_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('previous_end_date', ['entity' => $entity]);
        $this->field('previous_effective_date', ['type' => 'hidden', 'entity' => $entity]);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('new_institution_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('new_start_date', ['type' => 'date', 'onChangeReload' => true]);
        $this->field('new_end_date', ['type' => 'date', 'onChangeReload' => true, 'default_date' => false]);
        $this->field('new_FTE', ['type' => 'select']);
        $this->field('new_institution_position_id', ['type' => 'select']);
        $this->field('new_staff_type_id', ['type' => 'select']);
        // $this->field('shifts_id', ['type' => 'chosenSelect','placeholder' => __('Select Shifts'),]);
        //POCOR-7780:start
        $this->field('is_homeroom',[
            'entity' => $entity,
            'type'=>'select'
        ]);
        $this->fields['is_homeroom']['options'] = [0=>'No',1=>'Yes'];
        $this->fields['is_homeroom']['value'] = $entity->is_homeroom;
        //POCOR-7780:end

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('comment');
    }

    public function onUpdateFieldStaffId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['value'] = $entity->previous_institution_id;
            $attr['attr']['value'] = $entity->previous_institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];

            if (!empty($entity->previous_end_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->previous_end_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->previous_end_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEffectiveDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            if (!empty($entity->previous_effective_date)) {
                $attr['value'] = $entity->previous_effective_date->format('Y-m-d');
            }
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['value'] = $entity->new_institution_id;
            $attr['attr']['value'] = $entity->new_institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionPositionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $options = [];
            $requestData = $request->getData();
            if (!empty($requestData[$this->getAlias()]['new_institution_id']) && !empty($requestData[$this->getAlias()]['new_FTE']) && !empty($requestData[$this->getAlias()]['new_start_date'])) {
                $PositionsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');

                $userId = $this->Auth->user('id');
                $isAdmin = $this->AccessControl->isAdmin();
                $activeStatusId = $this->Workflow->getStepsByModelCode($PositionsTable->getRegistryAlias(), 'ACTIVE');

                $institutionId = $requestData[$this->getAlias()]['new_institution_id'];
                $fte = $requestData[$this->getAlias()]['new_FTE'];
                $startDate = $requestData[$this->getAlias()]['new_start_date'];
                $endDate = !empty($requestData[$this->getAlias()]['new_end_date']) ? $requestData[$this->getAlias()]['new_end_date'] : '';

                $options = $PositionsTable->getInstitutionPositions($userId, $isAdmin, $activeStatusId, $institutionId, $fte, $startDate, $endDate);
            }

            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
            return $attr;
        }
    }

    public function onUpdateFieldNewFTE(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $this->fteOptions;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldNewStaffTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $options = $this->NewStaffTypes->find('list')->toArray();

            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
            return $attr;
        }
    }
    
    public function onUpdateFieldShiftsId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $getInstitutionId = $this->getQueryString('institution_id');
        $requestInstitutionId = $this->request->getParam('institutionId');     
        $institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $getInstitutionId;
        //$institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $session->read('Institution.Institutions.id');
        
        if (in_array($action, ['edit', 'approve'])) {
            $academicPeriodId = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getCurrent();
            $options = $this->ShiftOptions($institutionId,$academicPeriodId);
            // need to specify select option for approve action
            $attr['options'] = $options;
            return $attr;
        }
    }

    
    public function ShiftOptions($institutionId,$academicPeriodId)
    {
        $institutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $institutionData   = $institutionShifts->find()
                            ->innerJoinWith('ShiftOptions')
                            ->innerJoinWith('Institutions')
                            ->select([
                                'institutionShiftId' => 'InstitutionShifts.id',
                                'institutionId' => 'Institutions.id',
                                'institutionCode' => 'Institutions.code',
                                'institutionName' => 'Institutions.name',
                                'shiftOptionName' => 'ShiftOptions.name'
                             ])
                            ->where([
                            'location_institution_id' => $institutionId,
                            'academic_period_id'      => $academicPeriodId
                            ]);
                            
                $data = $institutionData->toArray();
               
        $list = [];
        foreach ($data as $key => $obj) {
           
            if ($obj->institutionId == $institutionId) { //if the shift owned by itself, then no need to show the shift owner
                $shiftName = $obj->shiftOptionName;
            } else {
                $shiftName = $obj->institutionCode . " - " . $obj->institutionName . " - " . __($obj->shiftOptionName);
            }
            
            $list[$obj->institutionShiftId] = $shiftName;
        }
         
        return $list;
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
                $this->aliasField('new_institution_id'),
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
                $this->NewInstitutions->aliasField('code'),
                $this->NewInstitutions->aliasField('name'),
                $this->PreviousInstitutions->aliasField('code'),
                $this->PreviousInstitutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->getAlias(), $this->NewInstitutions->getAlias(), $this->PreviousInstitutions->getAlias(), $this->CreatedUser->getAlias(),'Assignees'])
            ->matching($Statuses->getAlias().'.'.$StepsParams->getAlias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $incomingInstitution) {
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
                        'action' => 'StaffTransferIn',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->new_institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s from %s'), $row->user->name_with_id, $row->previous_institution->code_name);
                    $row['institution'] = $row->new_institution->code_name;
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
        if(in_array($action, ['add','edit'])) { 
            $assigneeOptions = [$this->Auth->user('id') => __('Auto Assign')]; //POCOR-7080
            $attr['options'] = $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
        
    }
    
}
