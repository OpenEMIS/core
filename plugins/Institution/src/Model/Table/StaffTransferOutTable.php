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
use Institution\Model\Table\InstitutionStaffTransfersTable;

class StaffTransferOutTable extends InstitutionStaffTransfersTable
{
    private $transferTypeOptions = [];
    private $transferType = 0;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->transferTypeOptions = [
            self::FULL_TRANSFER => __('Full Transfer'),
            self::PARTIAL_TRANSFER => __('Partial Transfer'),
            self::NO_CHANGE => __('No Change')
        ];

        if ($this->behaviors()->has('Workflow')) {
            $this->behaviors()->get('Workflow')->config([
                'institution_key' => 'previous_institution_id'
            ]);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty(['transfer_type', 'new_institution_id', 'workflow_assignee_id']);
    }

    public function validationFullTransfer(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('positions_held')
            ->notEmpty(['positions_held', 'previous_end_date'])
            ->add('previous_end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'position_start_date', false]
            ])
            ->add('previous_end_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'new_start_date', false],
                'on' => function ($context) {
                    return array_key_exists('new_start_date', $context['data']) && !empty($context['data']['new_start_date']);
                }
            ]);
    }

    public function validationPartialTransfer(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('positions_held')
            ->notEmpty(['positions_held', 'previous_effective_date', 'previous_FTE', 'previous_staff_type_id'])
            ->add('previous_effective_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'position_start_date', false]
            ])
            ->add('previous_effective_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'new_start_date', true],
                'on' => function ($context) {
                    return array_key_exists('new_start_date', $context['data']) && !empty($context['data']['new_start_date']);
                }
            ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['UpdateAssignee.onSetSchoolBasedConditions'] = 'onSetSchoolBasedConditions';
        return $events;
    }

    public function onSetSchoolBasedConditions(Event $event, Entity $entity, $where)
    {
        $where[$this->aliasField('previous_institution_id')] = $entity->id;
        unset($where[$this->aliasField('institution_id')]);
        return $where;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);

        $this->field('previous_institution_staff_id', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_institution_position_id', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }

        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_effective_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('new_institution_id', ['type' => 'integer', 'sort' => ['field' => 'NewInstitutions.code']]);
        $this->field('previous_end_date', ['sort' => ['field' => 'previous_end_date']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'new_institution_id', 'previous_end_date']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffTransferOut', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'NewInstitutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'NewInstitutions.code', 'previous_end_date'];
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
        $this->transferType = $entity->transfer_type;

        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('institution_position_id');
        $this->field('FTE');
        $this->field('staff_type_id');
        $this->field('position_start_date');
        $this->field('new_end_date', ['type' => 'hidden']);

        // show fields according to transfer type
        if ($entity->transfer_type == self::FULL_TRANSFER) {
            $this->field('previous_effective_date', ['type' => 'hidden']);
            $this->field('previous_FTE', ['type' => 'hidden']);
            $this->field('previous_staff_type_id', ['type' => 'hidden']);
        } else if ($entity->transfer_type == self::PARTIAL_TRANSFER) {
            $this->field('previous_end_date', ['type' => 'hidden']);
        } else if ($entity->transfer_type == 0 || $entity->transfer_type == self::NO_CHANGE) {
            $this->field('previous_end_date', ['type' => 'hidden']);
            $this->field('previous_effective_date', ['type' => 'hidden']);
            $this->field('previous_FTE', ['type' => 'hidden']);
            $this->field('previous_staff_type_id', ['type' => 'hidden']);
        }

        if (empty($entity->new_start_date)) {
            $this->field('new_start_date', ['type' => 'hidden']);
        }

        $this->setFieldOrder([
            'previous_information_header', 'staff_id', 'previous_institution_id', 'institution_position_id', 'FTE', 'staff_type_id', 'position_start_date', 'transfer_type', 'previous_end_date', 'previous_effective_date', 'previous_FTE', 'previous_staff_type_id',
            'new_information_header', 'new_institution_id', 'new_start_date',
            'transfer_reasons_header', 'comment',
            // hidden fields
            'all_visible', 'new_end_date', 'previous_institution_staff_id', 'new_FTE', 'new_staff_type_id', 'new_institution_position_id'
        ]);
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onGetInstitutionPositionId(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id, ['contain' => ['Positions']]);
            $value = $StaffEntity->position->name;
        }
        return $value;
    }

    public function onGetFTE(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id);
            $value = $this->fteOptions["$StaffEntity->FTE"];
        }
        return $value;
    }

    public function onGetStaffTypeId(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id, ['contain' => ['StaffTypes']]);
            $value = $StaffEntity->staff_type->name;
        }
        return $value;
    }

    public function onGetPositionStartDate(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id);
            $value = $this->formatDate($StaffEntity->start_date);
        }
        return $value;
    }

    public function onGetTransferType(Event $event, Entity $entity)
    {
        $value = ' ';
        if ($entity->transfer_type != 0) {
            $value = $this->transferTypeOptions["$entity->transfer_type"];
        }
        return $value;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
        $userId = $this->getQueryString('user_id');

        if (empty($userId)) {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        } else {
            // url to redirect to staffUser page
            $staffUserUrl = $this->url('view');
            $staffUserUrl['action'] = 'StaffUser';
            $staffUserUrl[1] = $this->paramsEncode(['id' => $userId]);

            // check pending transfers
            $pendingTransfer = $this->find()
                ->matching('Statuses.WorkflowStepsParams', function ($q) {
                    return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
                })
                ->where([
                    $this->aliasField('staff_id') => $userId,
                    $this->Statuses->aliasField('category <> ') => self::DONE
                ])
                ->first();

            if (!empty($pendingTransfer)) {
                // check if the outgoing institution can view the transfer record
                $visible = 0;
                if ($pendingTransfer->previous_institution_id == $institutionId) {
                    $institutionOwner = $pendingTransfer->_matchingData['WorkflowStepsParams']->value;
                    if ($institutionOwner == self::OUTGOING || $pendingTransfer->all_visible) {
                        $visible = 1;
                    }
                }

                if ($visible) {
                    $url = $this->url('view');
                    $url[1] = $this->paramsEncode(['id' => $pendingTransfer->id]);
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                } else {
                    $this->Alert->warning($this->aliasField('existingStaffTransfer'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($staffUserUrl);
                }
            }

            // if no pending transfers
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

            $institutionStaffEntity = $StaffTable->find()
                ->contain(['Users', 'Institutions'])
                ->where([
                    $StaffTable->aliasField('staff_id') => $userId,
                    $StaffTable->aliasField('institution_id') => $institutionId,
                    $StaffTable->aliasField('staff_status_id') => $assignedStatus,
                ])
                ->first();
            $this->setupFields($institutionStaffEntity);

            $extra['toolbarButtons']['back']['url'] = $staffUserUrl;
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        // redirect to view page of record after save
        $extra['redirect'][0] = 'view';
        $extra['redirect'][1] = $this->paramsEncode(['id' => $entity->id]);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['transfer_type'] = $entity->transfer_type;

        if (!empty($entity->previous_institution_staff_id)) {
            $this->request->data[$this->alias()]['positions_held'] = $entity->previous_institution_staff_id;
        }
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'NewInstitutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupFields(Entity $entity)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('positions_held', ['entity' => $entity]);

        // to populate current institution staff fields based on selected positions_held
        $FTE = $staffType = $startDate = $startDateFormatted = '';
        if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
            $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
            $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId, ['contain' => ['StaffTypes']]);
            if (!empty($staffEntity)) {
                $FTE = $this->fteOptions["$staffEntity->FTE"];
                $staffType = $staffEntity->staff_type->name;
                $startDate = $staffEntity->start_date->format('Y-m-d');
                $startDateFormatted = $this->formatDate($staffEntity->start_date);
            }
        }
        $this->field('FTE', ['type' => 'readonly', 'attr' => ['value' => $FTE]]);
        $this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffType]]);
        $this->field('position_start_date', ['type' => 'readonly', 'value' => $startDate, 'attr' => ['value' => $startDateFormatted]]);

        $this->field('transfer_type');
        $this->field('previous_end_date', ['entity' => $entity]);
        $this->field('previous_effective_date');
        $this->field('previous_FTE');
        $this->field('previous_staff_type_id');

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('new_institution_id', ['entity' => $entity]);
        $this->field('new_start_date', ['entity' => $entity]);
        $this->field('new_end_date', ['type' => 'hidden', 'entity' => $entity]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('comment');
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            if ($action == 'add') {
                // using institution_staff entity
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            } else {
                // using institution_staff_transfer entity
                $attr['value'] = $entity->previous_institution_id;
                $attr['attr']['value'] = $entity->previous_institution->code_name;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldPositionsHeld(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $entity = $attr['entity'];

            if ($this->action == 'add') {
                // using institution_staff entity
                $institutionId = $entity->institution_id;
            } else {
                // using institution_staff_transfer entity
                $institutionId = $entity->previous_institution_id;
            }

            $staffEntity = $this->PreviousInstitutionStaff->find()
                ->select([
                    $this->PreviousInstitutionStaff->aliasField('id'),
                    'Positions.position_no',
                    'Positions.staff_position_title_id'
                ])
                ->matching('Positions')
                ->where([
                    $this->PreviousInstitutionStaff->aliasField('institution_id') => $institutionId,
                    $this->PreviousInstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                    $this->PreviousInstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                ])
                ->order([$this->PreviousInstitutionStaff->aliasField('created') => 'DESC'])
                ->toArray();

            $options = [];
            foreach ($staffEntity as $staff) {
                $options[$staff->id] = $staff->_matchingData['Positions']->name;
            }

            if (!isset($this->request->data[$this->alias()]['positions_held'])) {
                reset($options);
                $this->request->data[$this->alias()]['positions_held'] = key($options);
            }

            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldTransferType(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $options = $this->transferTypeOptions;

            if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
                $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
                $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId);

                if (!empty($staffEntity)) {
                    // do not show partial transfer option if current fte is 25%
                    if ($staffEntity->FTE <= 0.25) {
                        unset($options[self::PARTIAL_TRANSFER]);
                    }
                }
            }

            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousFTE(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            if (isset($this->request->data[$this->alias()]['transfer_type']) && $request->data[$this->alias()]['transfer_type'] == self::PARTIAL_TRANSFER) {
                $options = $this->fteOptions;

                if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
                    $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
                    $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId);

                    if (!empty($staffEntity)) {
                        // only show fte options less than the current fte
                        foreach ($options as $key => $option) {
                            if ($key >= $staffEntity->FTE) {
                                unset($options[$key]);
                            }
                        }
                    }
                }

                // need to specify select option for approve action
                $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
                $attr['type'] = 'select';
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldPreviousStaffTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            if (isset($this->request->data[$this->alias()]['transfer_type']) && $this->request->data[$this->alias()]['transfer_type'] == self::PARTIAL_TRANSFER) {
                $options = $this->PreviousStaffTypes->find('list')->toArray();

                // need to specify select option for approve action
                $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
                $attr['type'] = 'select';
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if (!empty($entity->previous_end_date)) {
                $attr['value'] = $entity->previous_end_date->format('Y-m-d');
            }

            if (isset($this->request->data[$this->alias()]['transfer_type']) && $this->request->data[$this->alias()]['transfer_type'] == self::FULL_TRANSFER) {
                $attr['type'] = 'date';
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEffectiveDate(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            if (isset($this->request->data[$this->alias()]['transfer_type'])  && $this->request->data[$this->alias()]['transfer_type'] == self::PARTIAL_TRANSFER) {
                $attr['type'] = 'date';
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_staff entity
                $conditions = [];
                $conditions[$this->NewInstitutions->aliasField('id <>')] = $entity->institution_id;
                
                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $Institutions = TableRegistry::get('Institution.Institutions');

                // start: restrict staff transfer by type
                $restrictStaffTransferByType = $ConfigItems->value('restrict_staff_release_between_same_type');
                if ($restrictStaffTransferByType) {
                    if ($entity->has('institution_id')) {
                        $institutionId = $entity->institution_id;

                        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;
                        
                        $conditions['institution_type_id'] = $institutionTypeId;
                    }
                }
                // end: restrict staff transfer by type

                // start: restrict staff transfer by provider
                $restrictStaffTransferByProvider = $ConfigItems->value('restrict_staff_release_between_different_provider');
                if ($restrictStaffTransferByProvider) {
                    if ($entity->has('institution_id')) {
                        $institutionId = $entity->institution_id;
                        $institutionProviderId = $Institutions->get($institutionId)->institution_provider_id;
                        
                        $conditions['institution_provider_id'] = $institutionProviderId;
                    }
                }
                // end: restrict staff transfer by provider

                $options = $this->NewInstitutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where($conditions)
                    ->order($this->NewInstitutions->aliasField('code'))
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $options;
            } else {
                // using institution_staff_transfer entity
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->new_institution_id;
                $attr['attr']['value'] = $entity->new_institution->code_name;
            }
            return $attr;
        }
    }

    public function onUpdateFieldNewStartDate(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if (in_array($action, ['edit', 'approve']) && !empty($entity->new_start_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->new_start_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->new_start_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldNewEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $entity = $attr['entity'];
            if (!empty($entity->new_end_date)) {
                $attr['value'] = $entity->new_end_date->format('Y-m-d');
            }
            return $attr;
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            $transferType = $data->offsetGet('transfer_type');

            if ($data->offsetExists('positions_held')) {
                $institutionStaffId = $data->offsetGet('positions_held');
                $data->offsetSet('previous_institution_staff_id', $institutionStaffId);
            }

            if ($transferType == self::FULL_TRANSFER) {
                $options['validate'] = 'fullTransfer';
                $data->offsetSet('previous_effective_date', null);
                $data->offsetSet('previous_FTE', null);
                $data->offsetSet('previous_staff_type_id', null);
            } else if ($transferType == self::PARTIAL_TRANSFER) {
                $options['validate'] = 'partialTransfer';
                if ($data->offsetExists('previous_effective_date')) {
                    $effectiveDate = new Date($data->offsetGet('previous_effective_date'));
                    $previousEndDate = $effectiveDate->modify('-1 day');
                    $data->offsetSet('previous_end_date', $previousEndDate->format('Y-m-d'));
                }
            } else {
                $data->offsetSet('previous_end_date', null);
                $data->offsetSet('previous_effective_date', null);
                $data->offsetSet('previous_FTE', null);
                $data->offsetSet('previous_staff_type_id', null);
            }
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
            ->contain([$this->Users->alias(), $this->NewInstitutions->alias(), $this->PreviousInstitutions->alias(), $this->CreatedUser->alias()])
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
                        'action' => 'StaffTransferOut',
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
                    $row['request_title'] = sprintf(__('%s to %s'), $row->user->name_with_id, $row->new_institution->code_name);
                    $row['institution'] = $row->previous_institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
