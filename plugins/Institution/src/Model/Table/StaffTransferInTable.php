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

class StaffTransferInTable extends InstitutionStaffTransfersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index', 'add']
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('staff_id', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStaffTransferIn'],
                'on' => 'create'
            ])
            ->notEmpty(['institution_position_id', 'FTE', 'staff_type_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('end_date', ['type' => 'hidden']);
        $this->field('FTE', ['type' => 'hidden']);
        $this->field('staff_type_id', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'initiated_by', 'staff_id', 'previous_institution_id', 'start_date', 'institution_position_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffTransferIn', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'Institutions' => ['code']];
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'initiated_by', 'staff_id', 'previous_institution_id', 'staff_type_id', 'FTE', 'institution_position_id', 'start_date', 'end_date', 'comment'
    ]);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        // to allow institution_position field to be populated on first load
        $this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
        $this->request->data[$this->alias()]['FTE'] = $entity->FTE;
        $this->request->data[$this->alias()]['start_date'] = $entity->start_date;
        $this->request->data[$this->alias()]['end_date'] = $entity->end_date;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('initiated_by', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);

        $this->field('staff_id', ['entity' => $entity]);
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('previous_institution_id', ['entity' => $entity]);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('staff_type_id');
        $this->field('FTE', ['type' => 'select', 'options' => $this->fteOptions, 'onChangeReload' => true]);
        $this->field('institution_position_id');
        $this->field('start_date', ['type' => 'date', 'onChangeReload' => true]);
        $this->field('end_date', ['type' => 'date', 'onChangeReload' => true, 'default_date' => false]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->previous_institution_id;
            $attr['attr']['value'] = $entity->previous_institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->institution->code_name;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionPositionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $options = [];
            if (!empty($request->data[$this->alias()]['institution_id']) && !empty($request->data[$this->alias()]['FTE']) && !empty($request->data[$this->alias()]['start_date'])) {
                $PositionsTable = TableRegistry::get('Institution.InstitutionPositions');
                $userId = $this->Auth->user('id');
                $isAdmin = $this->AccessControl->isAdmin();
                $activeStatusId = $this->Workflow->getStepsByModelCode($PositionsTable->registryAlias(), 'ACTIVE');

                $institutionId = $request->data[$this->alias()]['institution_id'];
                $fte = $request->data[$this->alias()]['FTE'];
                $startDate = $request->data[$this->alias()]['start_date'];
                $endDate = !empty($request->data[$this->alias()]['end_date']) ? $request->data[$this->alias()]['end_date'] : '';

                $options = $PositionsTable->getInstitutionPositions($userId, $isAdmin, $activeStatusId, $institutionId, $fte, $startDate, $endDate);
            }

            $attr['type'] = 'select';
            $attr['options'] = $options;
            return $attr;
        }
    }

    public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $options = $this->StaffTypes->find('list')->toArray();
            $attr['type'] = 'select';
            $attr['options'] = $options;
            return $attr;
        }
    }

    public function findInstitutionStaffTransferIn(Query $query, array $options)
    {
        $StatusesTable = $this->Statuses;
        $institutionId = $options['institution_id'];

        $query
            ->matching('Statuses')
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                'OR' => [
                    $this->aliasField('initiated_by') => self::INCOMING,
                    $StatusesTable->aliasField('params') => $this->incomingOwnerParams
                ]
            ]);
    }
}
