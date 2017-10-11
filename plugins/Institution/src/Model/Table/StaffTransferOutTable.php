<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
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
            ->notEmpty('previous_end_date', __('This field cannot be left empty'), function ($context) {
                if (array_key_exists('transfer_type', $context['data'])) {
                    return $context['data']['transfer_type'] == self::FULL_TRANSFER;
                }
                return false;
            });
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
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);

        $this->field('institution_id', ['type' => 'integer']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'initiated_by', 'staff_id', 'institution_id', 'previous_end_date']);

        if ($extra['toolbarButtons']['add']) {
            unset($extra['toolbarButtons']['add']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffTransferOut', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'Institutions' => ['code']];
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        // NOTE
        $transferType = self::PARTIAL_TRANSFER;
        if (!empty($entity->institution_staff_id) && !empty($entity->previous_end_date)) {
            $transferType = self::FULL_TRANSFER;
        }

        $this->request->data[$this->alias()]['transfer_type'] = $transferType;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
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

    private function setupFields(Entity $entity)
    {
        $this->field('staff_id', ['entity' => $entity]);

        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('transfer_type', ['type' => 'select', 'options' => $this->transferTypeOptions, 'onChangeReload' => true]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('staff_positions', ['type' => 'staff_positions', 'entity' => $entity]);
        $this->field('previous_end_date', ['type' => 'date', 'entity' => $entity]);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');

        $this->field('initiated_by', ['type' => 'hidden']);
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            if ($action == 'add') {
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            } else {
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
                $staffEntities = [$attr['entity']];

            } else if ($this->action == 'edit') {
                $staffEntities = $InstitutionStaff->find()
                    ->contain(['Positions', 'StaffTypes'])
                    ->where([
                        $InstitutionStaff->aliasField('institution_id') => $entity->previous_institution_id,
                        $InstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                        $InstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                    ])
                    ->order([$InstitutionStaff->aliasField('created') => 'DESC'])
                    ->toArray();
            }

            $staffData = [];
            foreach ($staffEntities as $obj) {
                $selected = false;
                if ($this->action == 'edit' && !empty($entity->institution_staff_id) && $entity->institution_staff_id == $obj->id) {
                    $selected = true;
                }
                $staffData[] = [
                    'institution_staff_id' => $obj->id,
                    'selected' => $selected,
                    'position' => $obj->position->name,
                    'staff_type' => $obj->staff_type->name,
                    'fte' => $this->fteOptions[$obj->FTE],
                    'start_date' => $this->formatDate($obj->start_date)
                ];
            }

            $showRadioButtons = false;
            if (!empty($this->request->data[$this->alias()]['transfer_type'])) {
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

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $type = 'hidden';
            if (!empty($request->data[$this->alias()]['transfer_type'])) {
                $transferType = $request->data[$this->alias()]['transfer_type'];
                if ($transferType == self::FULL_TRANSFER) {
                    $type = 'date';
                }
            }

            $attr['type'] = $type;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($action == 'add') {
                $options = $this->Institutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where([$this->Institutions->aliasField('id <>') => $entity->institution_id])
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $options;
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            }
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($action == 'edit' && !empty($entity->start_date)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->start_date->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($entity->start_date);
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if ($this->action == 'add') {
                $data->offsetSet('initiated_by', self::OUTGOING);
            }

            $transferType = $data->offsetGet('transfer_type');
            if ($transferType == self::FULL_TRANSFER) {
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

    public function findInstitutionStaffTransferOut(Query $query, array $options)
    {
        $StatusesTable = $this->Statuses;
        $institutionId = $options['institution_id'];

        $query
            ->matching('Statuses')
            ->where([
                $this->aliasField('previous_institution_id') => $institutionId,
                'OR' => [
                    $this->aliasField('initiated_by') => self::OUTGOING,
                    $StatusesTable->aliasField('params') => $this->outgoingOwnerParams
                ]
            ]);
    }
}
