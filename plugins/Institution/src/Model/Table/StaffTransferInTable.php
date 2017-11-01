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

class StaffTransferInTable extends InstitutionStaffTransfersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Staff' => ['index', 'add']
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('new_start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'new_end_date', false],
                'on' => function ($context) {
                    return array_key_exists('new_end_date', $context['data']) && !empty($context['data']['new_end_date']);
                }
            ])
            ->add('new_start_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'previous_end_date', false],
                'on' => function ($context) {
                    return array_key_exists('previous_end_date', $context['data']) && !empty($context['data']['previous_end_date']);
                }
            ])
            ->notEmpty(['new_institution_position_id', 'new_FTE', 'new_staff_type_id', 'new_start_date']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_staff_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('transfer_type', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('new_end_date', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_institution_id', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->field('initiated_by', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('previous_institution_id', ['sort' => ['field' => 'PreviousInstitutions.code']]);
        $this->field('new_start_date', ['sort' => ['field' => 'new_start_date']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'previous_institution_id', 'new_start_date', 'new_institution_position_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('new_institution_id', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        // to allow institution_position field to be populated on first load
        $this->request->data[$this->alias()]['new_institution_id'] = $entity->new_institution_id;
        $this->request->data[$this->alias()]['new_FTE'] = $entity->new_FTE;
        $this->request->data[$this->alias()]['new_start_date'] = $entity->new_start_date;
        $this->request->data[$this->alias()]['new_end_date'] = $entity->new_end_date;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'NewInstitutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('initiated_by', ['type' => 'hidden']);

        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('staff_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('previous_institution_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('previous_end_date', ['entity' => $entity]);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('new_institution_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('new_start_date', ['type' => 'date', 'onChangeReload' => true]);
        $this->field('new_end_date', ['type' => 'date', 'onChangeReload' => true, 'default_date' => false]);
        $this->field('new_FTE', ['type' => 'select', 'options' => $this->fteOptions, 'onChangeReload' => true]);
        $this->field('new_institution_position_id', ['type' => 'select']);
        $this->field('new_staff_type_id', ['type' => 'select']);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->previous_institution_id;
            $attr['attr']['value'] = $entity->previous_institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
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

    public function onUpdateFieldNewInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->new_institution_id;
            $attr['attr']['value'] = $entity->new_institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionPositionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $options = [];
            if (!empty($request->data[$this->alias()]['new_institution_id']) && !empty($request->data[$this->alias()]['new_FTE']) && !empty($request->data[$this->alias()]['new_start_date'])) {
                $PositionsTable = TableRegistry::get('Institution.InstitutionPositions');

                $userId = $this->Auth->user('id');
                $isAdmin = $this->AccessControl->isAdmin();
                $activeStatusId = $this->Workflow->getStepsByModelCode($PositionsTable->registryAlias(), 'ACTIVE');

                $institutionId = $request->data[$this->alias()]['new_institution_id'];
                $fte = $request->data[$this->alias()]['new_FTE'];
                $startDate = $request->data[$this->alias()]['new_start_date'];
                $endDate = !empty($request->data[$this->alias()]['new_end_date']) ? $request->data[$this->alias()]['new_end_date'] : '';

                $options = $PositionsTable->getInstitutionPositions($userId, $isAdmin, $activeStatusId, $institutionId, $fte, $startDate, $endDate);
            }
            $attr['options'] = $options;
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
}
