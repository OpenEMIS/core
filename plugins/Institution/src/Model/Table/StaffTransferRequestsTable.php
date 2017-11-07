<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\StaffTransfer;
use App\Model\Traits\OptionsTrait;

class StaffTransferRequestsTable extends StaffTransfer
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Staff' => ['index', 'add']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('update', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStaffTransfer'],
                'on' => 'create'
            ])
            ->requirePresence('institution_position_id');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);
        $toolbarButtons = $extra['toolbarButtons'];

        if ($this->action != 'index') {
            if ($this->Session->check('Institution.Staff.transfer')) {
                if (isset($toolbarButtons['back'])) {
                    $url = $this->url('add');
                    $url['action'] = 'Staff';
                    $url[0] = 'add';
                    $toolbarButtons['back']['url'] = $url;
                }
            }
        } else {
            $this->Session->delete('Institution.Staff.transfer');
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, $extra)
    {
        $query->where([$this->aliasField('type') => self::TRANSFER]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code']];
    }

    public function editBeforeQuery(Event $event, Query $query, $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'Positions']);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $model = $this;
        $process = function($model, $entity) use ($data) {
            $staffDetail = $data[$this->alias()];
            $transferRecord = $data[$this->alias()];
            unset($staffDetail['id']);
            unset($staffDetail['previous_institution_id']);
            unset($staffDetail['comment']);
            $staffDetail['staff_status_id'] = $staffDetail['status'];
            unset($staffDetail['status']);
            $StaffTable = TableRegistry::get('Institution.Staff');
            $newStaffEntity = $StaffTable->newEntity($staffDetail, ['validate' => "AllowPositionType"]);

            if ($newStaffEntity->errors()) {
                $errors = $newStaffEntity->errors();

                foreach ($errors as $key => $value) {
                    $entity->errors($key, $value);
                }

                return false;
            } else {
                $success = $this->connection()->transactional(function() use ($StaffTable, $newStaffEntity, $model, $transferRecord) {
                    if (!$StaffTable->save($newStaffEntity)) {
                        return false;
                    }

                    $transferRecord['status'] = self::CLOSED;
                    $transferEntity = $model->newEntity($transferRecord);
                    if (!$model->save($transferEntity)) {
                        return false;
                    }

                    return true;
                });

                return $success;
            }
        };

        return $process;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        parent::editAfterAction($event, $entity, $extra);

        $this->field('previous_institution_id', ['type' => 'readonly', 'after' => 'staff_id', 'attr' => ['value' => $entity->previous_institution->code_name]]);
        $this->field('institution_position_id', ['type' => 'select']);
        $this->field('staff_type_id', ['type' => 'select']);
        $this->field('FTE', ['type' => 'select']);
    }

    private function isTransferExists($transfer)
    {
        $entity = $this->find()
            ->where([
                $this->aliasField('staff_id') => $transfer['staff_id'],
                $this->aliasField('previous_institution_id') => $transfer['previous_institution_id'],
                $this->aliasField('institution_position_id') => $transfer['institution_position_id'],
                $this->aliasField('status') => self::PENDING,
                $this->aliasField('type') => self::TRANSFER
            ])
            ->first();
        return $entity;
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['status'] = $entity->status;
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $url = false;
        if ($this->Session->check('Institution.Staff.transfer')) {
            $staffTransfer = $this->Session->read('Institution.Staff.transfer');

            // check if there is an existing transfer application
            if ($transferEntity = $this->isTransferExists($staffTransfer)) {
                // TODO
                // pr($transferEntity);
                // $url = $this->url('view');
                // $url[1] = $addOperation->id;
            } else { // no existing transfer application, proceed to initiate a transfer
                foreach ($staffTransfer as $key => $value) {
                    $entity->{$key} = $value;
                }
                $entity->status = self::PENDING;
                $entity->type = self::TRANSFER;
            }
        } else { // invalid transfer data
            $url = $this->url('index');
        }

        if ($url) {
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $staffName = $this->Users->get($this->getEntityProperty($entity, 'staff_id'))->name_with_id;
        $staffTypeName = $this->StaffTypes->get($this->getEntityProperty($entity, 'staff_type_id'))->name;
        $institutionCodeName = $this->Institutions->get($this->getEntityProperty($entity, 'institution_id'))->code_name;
        $prevInstitutionCodeName = $this->Institutions->get($entity->previous_institution_id)->code_name;
        $positionName = $this->Positions->get($this->getEntityProperty($entity, 'institution_position_id'))->name;

        $this->field('status', ['type' => 'readonly']);
        $this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $staffName]]);
        $this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $prevInstitutionCodeName]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $institutionCodeName]]);
        $this->field('institution_position_id', ['after' => 'institution_id', 'type' => 'readonly', 'attr' => ['value' => $positionName]]);
        $this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffTypeName]]);
        $this->field('FTE', ['type' => 'readonly']);
        $this->field('start_date', ['type' => 'readonly']);
        $this->field('comment');
        $this->field('update', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
        $this->field('type', ['type' => 'hidden', 'visible' => true, 'value' => self::TRANSFER]);

        $message = $this->getMessage($this->aliasField('alreadyAssigned'), ['sprintf' => [$staffName, $prevInstitutionCodeName]]);
        $this->Alert->warning($message, ['type' => 'text']);
        $this->Alert->info($this->aliasField('confirmRequest'));
    }

    public function onGetPreviousInstitutionId(Event $event, Entity $entity)
    {
        return $entity->previous_institution->code_name;
    }

    public function onUpdateFieldInstitutionPositionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $positionTable = TableRegistry::get('Institution.InstitutionPositions');
            $userId = $this->Auth->user('id');
            $institutionId = $this->Session->read('Institution.Institutions.id');

            // // excluding positions where 'InstitutionStaff.end_date is NULL'
            $excludePositions = $this->Positions->find('list');
            $excludePositions->matching('InstitutionStaff', function ($q) {
                    return $q->where(['InstitutionStaff.end_date is NULL', 'InstitutionStaff.FTE' => 1]);
            });
            $excludePositions->where([$this->Positions->aliasField('institution_id') => $institutionId])
                ->toArray()
                ;
            $excludeArray = [];
            foreach ($excludePositions as $key => $value) {
                $excludeArray[] = $value;
            }

            if ($this->AccessControl->isAdmin()) {
                $userId = null;
                $roles = [];
            } else {
                $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            }

            // Filter by active status
            $activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
            $positionConditions = [];
            $positionConditions[$this->Positions->aliasField('institution_id')] = $institutionId;
            if (!empty($activeStatusId)) {
                $positionConditions[$this->Positions->aliasField('status_id').' IN '] = $activeStatusId;
            }
            if (!empty($excludeArray)) {
                $positionConditions[$this->Positions->aliasField('id').' NOT IN '] = $excludeArray;
            }
            $staffPositionsOptions = $this->Positions
                    ->find()
                    ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                    ->where($positionConditions)
                    ->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type'])
                    ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
                    ->autoFields(true)
                    ->toArray();

            // Filter by role previlege
            $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
            $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
            $roleOptions = array_keys($roleOptions);
            $staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
            $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

            // Adding the opt group
            $types = $this->getSelectOptions('Staff.position_types');
            $options = [];
            foreach ($staffPositionsOptions as $position) {
                $type = __($types[$position->type]);
                $options[$type][$position->id] = $position->name;
            }

            $attr['options'] = $options;
            return $attr;
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            $url = $this->url('add');
            $url['action'] = 'Staff';
            $buttons[1]['url'] = $url;
        } else if ($this->action == 'edit') {
            if ($this->request->data[$this->alias()]['status'] == self::APPROVED) {
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Assign');
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, $extra)
    {
        parent::viewAfterAction($event, $entity, $extra);

        if ($this->Session->check('Institution.StaffTransferRequests.success')) {
            $this->Alert->success('general.add.success');
            $this->Session->delete('Institution.StaffTransferRequests.success');
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $controller->loadComponent('AccessControl');

        $session = $controller->request->session();
        $AccessControl = $controller->AccessControl;

        $isAdmin = $session->read('Auth.User.super_admin');
        $userId = $session->read('Auth.User.id');

        $where = [
            $this->aliasField('status') => self::APPROVED,
            $this->aliasField('type') => self::TRANSFER
        ];

        if (!$isAdmin) {
            if ($AccessControl->check(['Institutions', 'StaffTransferRequests', 'edit'])) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

                if (empty($institutionIds)) {
                    // return empty list if the user does not have access to any schools
                    return $query->where([$this->aliasField('id') => -1]);
                } else {
                    $where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
                }
            } else {
                // return empty list if the user does not permission to approve Staff Transfer Requests
                return $query->where([$this->aliasField('id') => -1]);
            }
        }

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('previous_institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
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
            ->where($where)
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffTransferRequests',
                        'edit',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __('Pending For Approval');
                    $row['request_title'] = sprintf(__('Staff Transfer Approved of %s from %s'), $row->user->name_with_id, $row->previous_institution->code_name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
