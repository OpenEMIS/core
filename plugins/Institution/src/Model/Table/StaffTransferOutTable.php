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
use Institution\Model\Table\InstitutionStaffTransfersTable;

class StaffTransferOutTable extends InstitutionStaffTransfersTable
{
    private $transferTypeOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->transferTypeOptions = [
            self::FULL_TRANSFER => __('Full Transfer'),
            self::PARTIAL_TRANSFER => __('Partial Transfer'),
            self::NO_CHANGE => __('No Change')
        ];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('previous_end_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'new_start_date', false],
                'on' => function ($context) {
                    return array_key_exists('new_start_date', $context['data']) && !empty($context['data']['new_start_date']);
                }
            ])
            ->notEmpty('transfer_type')
            ->notEmpty('new_institution_id');
    }

    public function validationFullTransfer(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('current_staff_positions')
            ->notEmpty(['current_staff_positions', 'previous_end_date']);
    }

    public function validationPartialTransfer(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('current_staff_positions')
            ->notEmpty(['current_staff_positions', 'previous_end_date', 'previous_FTE', 'previous_staff_type_id']);
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
        $this->field('comment', ['type' => 'hidden']);
        $this->field('initiated_by', ['type' => 'hidden']);

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
        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);
        $this->field('new_institution_id', ['type' => 'integer']);

        // show fields according to transfer type
        if ($entity->transfer_type == self::FULL_TRANSFER) {
            $this->field('previous_FTE', ['type' => 'hidden']);
            $this->field('previous_staff_type_id', ['type' => 'hidden']);
        } else if ($entity->transfer_type == 0 || $entity->transfer_type == self::NO_CHANGE) {
            $this->field('previous_end_date', ['type' => 'hidden']);
            $this->field('previous_FTE', ['type' => 'hidden']);
            $this->field('previous_staff_type_id', ['type' => 'hidden']);
        }

        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'new_institution_id', 'transfer_type', 'previous_end_date', 'previous_FTE', 'previous_staff_type_id', 'comment', 'initiated_by']);
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
        $institutionStaffId = $this->getQueryString('institution_staff_id');
        $userId = $this->getQueryString('user_id');

        if (empty($institutionStaffId) || empty($userId)) {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }

        // check pending transfers
        $pendingTransfer = $this->find()
            ->contain('Statuses.WorkflowStepsParams')
            ->where([
                $this->aliasField('staff_id') => $userId,
                $this->Statuses->aliasField('category <> ') => self::DONE
            ])
            ->first();

        if (!empty($pendingTransfer)) {
            // check if the outgoing institution can view the transfer record
            $visible = 0;
            if ($pendingTransfer->previous_institution_id == $institutionId) {
                $params = $pendingTransfer->status->workflow_steps_params;
                foreach ($params as $param) {
                    if ($param['name'] == 'institution_visible' && $param['value'] == self::OUTGOING) {
                        $visible = 1;
                        break;
                    }
                }
            }

            if ($visible) {
                $url = $this->url('view');
                $url[1] = $this->paramsEncode(['id' => $pendingTransfer->id]);

                $event->stopPropagation();
                return $this->controller->redirect($url);

            } else {
                $url = $this->url('view');
                $url['action'] = 'StaffUser';
                $url[1] = $this->paramsEncode(['id' => $userId]);
                $url = $this->setQueryString($url, ['institution_staff_id' => $institutionStaffId]);

                $this->Alert->warning($this->aliasField('existingStaffTransfer'), ['reset' => true]);
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }
        }

        // if no pending transfers
        $StaffTable = TableRegistry::get('Institution.Staff');
        $institutionStaffEntity = $StaffTable->get($institutionStaffId, ['contain' => ['Users', 'Institutions', 'Positions', 'StaffTypes']]);
        $this->setupFields($institutionStaffEntity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['transfer_type'] = $entity->transfer_type;

        if (!empty($entity->previous_institution_staff_id)) {
            $this->request->data[$this->alias()]['current_staff_positions'] = $entity->previous_institution_staff_id;
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
        $this->field('current_staff_positions', ['entity' => $entity]);

        // to populate current institution staff fields based on selected current_staff_positions
        $FTE = $staffType = $startDate = '';
        if (isset($this->request->data[$this->alias()]['current_staff_positions']) && !empty($this->request->data[$this->alias()]['current_staff_positions'])) {
            $institutionStaffId = $this->request->data[$this->alias()]['current_staff_positions'];
            $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId, ['contain' => ['StaffTypes']]);
            if (!empty($staffEntity)) {
                $FTE = $this->fteOptions["$staffEntity->FTE"];
                $staffType = $staffEntity->staff_type->name;
                $startDate = $this->formatDate($staffEntity->start_date);
            }
        }
        $this->field('current_FTE', ['type' => 'readonly', 'attr' => ['value' => $FTE]]);
        $this->field('current_staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffType]]);
        $this->field('current_start_date', ['type' => 'readonly', 'attr' => ['value' => $startDate]]);

        $this->field('transfer_type');
        $this->field('previous_end_date');
        $this->field('previous_FTE');
        $this->field('previous_staff_type_id');

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('new_institution_id', ['entity' => $entity]);
        $this->field('new_start_date', ['entity' => $entity]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');

        $this->field('initiated_by', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden', 'entity' => $entity]);
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
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

    public function onUpdateFieldCurrentStaffPositions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $entity = $attr['entity'];

            if ($this->action == 'add') {
                // using institution_staff entity
                $institutionId = $entity->institution_id;
            } else if ($this->action == 'edit') {
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
            foreach($staffEntity as $staff) {
                $options[$staff->id] = $staff->_matchingData['Positions']->name;
            }

            if (!isset($this->request->data[$this->alias()]['current_staff_positions'])) {
                reset($options);
                $this->request->data[$this->alias()]['current_staff_positions'] = key($options);
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
        if ($action == 'add' || $action == 'edit') {
            $options = $this->transferTypeOptions;

            if (isset($this->request->data[$this->alias()]['current_staff_positions']) && !empty($this->request->data[$this->alias()]['current_staff_positions'])) {
                $institutionStaffId = $this->request->data[$this->alias()]['current_staff_positions'];
                $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId);

                if (!empty($staffEntity)) {
                    // do not show partial transfer option if current fte is 25%
                    if ($staffEntity->FTE <= 0.25) {
                        unset($options[self::PARTIAL_TRANSFER]);
                    }
                }
            }

            $attr['type'] = 'select';
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousFTE(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $type = 'hidden';

            if (isset($this->request->data[$this->alias()]['transfer_type'])) {
                $transferType = $request->data[$this->alias()]['transfer_type'];
                if (in_array($transferType, [self::PARTIAL_TRANSFER])) {
                    $options = $this->fteOptions;

                    if (isset($this->request->data[$this->alias()]['current_staff_positions']) && !empty($this->request->data[$this->alias()]['current_staff_positions'])) {
                        $institutionStaffId = $this->request->data[$this->alias()]['current_staff_positions'];
                        $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId);

                        if (!empty($staffEntity)) {
                            // only show fte options less than the current fte
                            foreach($options as $key => $option) {
                                if ($key >= $staffEntity->FTE) {
                                    unset($options[$key]);
                                }
                            }
                        }
                    }

                    $type = 'select';
                    $attr['options'] = $options;
                }
            }

            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousStaffTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $type = 'hidden';

            if (isset($this->request->data[$this->alias()]['transfer_type'])) {
                $transferType = $request->data[$this->alias()]['transfer_type'];
                if (in_array($transferType, [self::PARTIAL_TRANSFER])) {
                    $type = 'select';
                }
            }
            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $type = 'hidden';

            if (isset($this->request->data[$this->alias()]['transfer_type'])) {
                $transferType = $request->data[$this->alias()]['transfer_type'];
                if (in_array($transferType, [self::FULL_TRANSFER, self::PARTIAL_TRANSFER])) {
                    $type = 'date';
                }
            }
            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_staff entity
                $options = $this->NewInstitutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where([$this->NewInstitutions->aliasField('id <>') => $entity->institution_id])
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
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $type = 'hidden';

            if ($action == 'edit' && !empty($entity->new_start_date)) {
                $type = 'readonly';
                $attr['value'] = $entity->new_start_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->new_start_date);
            }
            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldNewEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            if (!empty($entity->new_end_date)) {
                $attr['value'] = $entity->new_end_date->format('Y-m-d');
            }
            return $attr;
        }
    }

    public function onUpdateFieldInitiatedBy(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['value'] = self::OUTGOING;
            return $attr;
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            $transferType = $data->offsetGet('transfer_type');

            if ($data->offsetExists('current_staff_positions')) {
                $institutionStaffId = $data->offsetGet('current_staff_positions');
                $data->offsetSet('previous_institution_staff_id', $institutionStaffId);
            }

            if ($transferType == self::FULL_TRANSFER) {
                $options['validate'] = 'fullTransfer';
                $data->offsetSet('previous_FTE', NULL);
                $data->offsetSet('previous_staff_type_id', NULL);

            } else if ($transferType == self::PARTIAL_TRANSFER) {
                $options['validate'] = 'partialTransfer';

            } else {
                $data->offsetSet('previous_end_date', NULL);
                $data->offsetSet('previous_FTE', NULL);
                $data->offsetSet('previous_staff_type_id', NULL);
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
