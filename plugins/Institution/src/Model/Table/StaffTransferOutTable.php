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
    // Transfer Type
    const FULL_TRANSFER = 1;
    const PARTIAL_TRANSFER = 2;

    private $transferTypeOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->transferTypeOptions = [
            self::FULL_TRANSFER => 'Full Transfer',
            self::PARTIAL_TRANSFER => 'Partial Transfer'
        ];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('staff_id', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStaffTransferIn'],
                'on' => 'create'
            ])
            ->notEmpty('transfer_type')
            ->notEmpty('institution_id', null, 'create');
    }

    public function validationFullTransfer(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('staff_positions')
            ->notEmpty('staff_positions')
            ->notEmpty('previous_end_date');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_staff_id', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->field('FTE', ['type' => 'hidden']);
        $this->field('staff_type_id', ['type' => 'hidden']);
        $this->field('institution_position_id', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }

        $this->field('start_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->field('initiated_by', ['type' => 'hidden']);
        $this->field('currently_assigned_to');
        $this->field('institution_id', ['type' => 'integer']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'currently_assigned_to', 'staff_id', 'institution_id', 'previous_end_date']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffTransferOut', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'Institutions' => ['code']];
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('currently_assigned_to');
        $this->field('institution_id', ['type' => 'integer']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'currently_assigned_to', 'staff_id', 'institution_id', 'previous_end_date', 'comment', 'initiated_by']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $institutionStaffId = $this->getQueryString('institution_staff_id');

        if (!empty($institutionStaffId)) {
            $StaffTable = TableRegistry::get('Institution.Staff');
            $institutionStaffEntity = $StaffTable->get($institutionStaffId, ['contain' => ['Users', 'Institutions', 'Positions', 'StaffTypes']]);
            $this->setupFields($institutionStaffEntity);

        } else {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!empty($entity->institution_staff_id) && !empty($entity->previous_end_date)) {
            $this->request->data[$this->alias()]['transfer_type'] = self::FULL_TRANSFER;
            $this->request->data[$this->alias()]['staff_positions'] = $entity->institution_staff_id;
        } else {
            $this->request->data[$this->alias()]['transfer_type'] = self::PARTIAL_TRANSFER;
        }
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupFields(Entity $entity)
    {
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('transfer_type', ['type' => 'select', 'options' => $this->transferTypeOptions, 'onChangeReload' => true]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('staff_positions', ['type' => 'staff_positions', 'entity' => $entity]);
        $this->field('previous_end_date', ['type' => 'date']);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');

        $this->field('initiated_by', ['type' => 'hidden']);
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

    public function onGetStaffPositionsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'edit') {
            $fieldKey = 'staff_positions';
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');

            if ($this->action == 'add') {
                // using institution_staff entity
                $institutionId = $attr['entity']->institution_id;
                $staffId = $attr['entity']->staff_id;
            } else if ($this->action == 'edit') {
                // using institution_staff_transfer entity
                $institutionId = $entity->previous_institution_id;
                $staffId = $entity->staff_id;
            }

            $staffEntities = $InstitutionStaff->find()
                ->contain(['Positions', 'StaffTypes'])
                ->where([
                    $InstitutionStaff->aliasField('institution_id') => $institutionId,
                    $InstitutionStaff->aliasField('staff_id') => $staffId,
                    $InstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                ])
                ->order([$InstitutionStaff->aliasField('created') => 'DESC'])
                ->toArray();

            $staffData = [];
            foreach ($staffEntities as $obj) {
                $selected = false;
                if (isset($this->request->data[$this->alias()]['staff_positions']) && $this->request->data[$this->alias()]['staff_positions'] == $obj->id) {
                    $selected = true;
                }
                $staffData[] = [
                    'institution_staff_id' => $obj->id,
                    'selected' => $selected,
                    'position' => $obj->position->name,
                    'staff_type' => $obj->staff_type->name,
                    'fte' => $this->fteOptions["$obj->FTE"],
                    'start_date' => $this->formatDate($obj->start_date)
                ];
            }

            $showRadioButtons = false;
            if (isset($this->request->data[$this->alias()]['transfer_type']) && !empty($this->request->data[$this->alias()]['transfer_type'])) {
                $transferType = $this->request->data[$this->alias()]['transfer_type'];
                if ($transferType == self::FULL_TRANSFER) {
                    $showRadioButtons = true;
                }
            }

            $attr['staffData'] = $staffData;
            $attr['showRadioButtons'] = $showRadioButtons;
            return $event->subject()->renderElement('InstitutionStaffTransfers/' . $fieldKey, ['attr' => $attr]);
        }
    }

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $type = 'hidden';

            if (isset($this->request->data[$this->alias()]['transfer_type']) && !empty($this->request->data[$this->alias()]['transfer_type'])) {
                $transferType = $request->data[$this->alias()]['transfer_type'];
                if ($transferType == self::FULL_TRANSFER) {
                    $type = 'date';
                }
            }
            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_staff entity
                $options = $this->Institutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where([$this->Institutions->aliasField('id <>') => $entity->institution_id])
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $options;
            } else {
                // using institution_staff_transfer entity
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            }
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $type = 'hidden';

            if ($action == 'edit' && !empty($entity->start_date)) {
                $type = 'readonly';
                $attr['value'] = $entity->start_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->start_date);
            }
            $attr['type'] = $type;
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

            if ($transferType == self::FULL_TRANSFER) {
                $options['validate'] = 'fullTransfer';
                if ($data->offsetExists('staff_positions')) {
                    $institutionStaffId = $data->offsetGet('staff_positions');
                    $data->offsetSet('institution_staff_id', $institutionStaffId);
                }
            } else if ($transferType == self::PARTIAL_TRANSFER) {
                $data->offsetSet('institution_staff_id', NULL);
                $data->offsetSet('previous_end_date', NULL);
            }
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        return $this->processStaffPositionsError($entity);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        return $this->processStaffPositionsError($entity);
    }

    private function processStaffPositionsError($entity)
    {
        $errors = $entity->errors();
        if (!empty($errors)) {
            if (array_key_exists('staff_positions', $errors)) {
                $this->Alert->warning($this->aliasField('noStaffPositionSelected'));
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
