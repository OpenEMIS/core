<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Date;

use App\Model\Table\ControllerActionTable;

class InstitutionStaffOutgoingAssignmentsTable extends ControllerActionTable
{
    use OptionsTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'staff_type_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('NextInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'next_institution_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onRequestTransferFromIncomingInstitution',
            'text' => 'Request Transfer From Incoming Institution',
            'description' => 'Performing this action will initiate the staff transfer workflow in the incoming institution.',
            'method' => 'onRequestTransferFromIncomingInstitution'
        ]
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';

        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onRequestTransferFromIncomingInstitution(Event $event, $id, Entity $workflowTransitionEntity)
    {

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['institution_staff_id'] = $this->getQueryString('institution_staff_id');
        $extra['user_id'] = $this->getQueryString('user_id');
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $staffEntity = $InstitutionStaff->get($extra['institution_staff_id'], ['contain' => ['Users', 'Institutions', 'Positions', 'StaffTypes']]);

        $this->field('staff_id', ['type' => 'readonly', 'value' => $extra['user_id'], 'attr' => ['value' => $staffEntity->staff_name]]);
        $this->field('assignee_id', ['type' => 'hidden']);
        $this->field('status_id', ['type' => 'hidden']);

        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('institution_id', ['type' => 'readonly', 'entity' => $staffEntity]);
        $this->field('previous_institution_position', ['type' => 'readonly', 'entity' => $staffEntity]);
        $this->field('previous_staff_type', ['type' => 'readonly', 'entity' => $staffEntity]);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('next_institution_id', ['type' => 'chosenSelect', 'entity' => $staffEntity]);
        $this->field('staff_type_id', ['type' => 'select']);
        $this->field('FTE', ['type' => 'select']);
        $this->field('institution_position_id', ['type' => 'select']);
        $this->field('start_date', ['type' => 'date']);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
        $this->field('comment');

        // transfer status: staff name, workflow status?
        // transfer from: position, type, institution
        // transfer to: area?, institution, academic period?, start date, end date, position type (full time/part time), fte, position, staff type
        // other details: staff transfer reason?, comment
    }

    public function onGetFTE(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('FTE')) {
            $fte = $entity->FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->institution->code_name;
            $attr['attr']['label']['text'] = 'Current Institution';
        }

        return $attr;
    }

    public function onUpdateFieldPreviousInstitutionPosition(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['attr']['value'] = $entity->position->name;
            $attr['attr']['label']['text'] = 'Institution Position';
        }

        return $attr;
    }

    public function onUpdateFieldPreviousStaffType(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $attr['attr']['value'] = $entity->staff_type->name;
            $attr['attr']['label']['text'] = 'Staff Type';
        }

        return $attr;
    }

    public function onUpdateFieldNextInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $institutionOptions = $this->Institutions->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->where([$this->Institutions->aliasField('id <>') => $entity->institution_id])
                ->toArray();

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['onChangeReload'] = true;
            $attr['options'] = $institutionOptions;
            $attr['attr']['label']['text'] = 'Institution';
        }

        return $attr;
    }

    public function onUpdateFieldFTE(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $this->fteOptions;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionPositionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $options = [];

            if (!empty($request->data[$this->alias()]['FTE']) && !empty($request->data[$this->alias()]['institution_id']) && !empty($request->data[$this->alias()]['start_date'])) {
                $fte = $request->data[$this->alias()]['FTE'];
                $institutionId = $request->data[$this->alias()]['institution_id'];
                $startDate = $request->data[$this->alias()]['start_date'];
                $userId = $this->Auth->user('id');
                $endDate = '';

                $StaffTable = TableRegistry::get('Institution.Staff');
                $positionTable = TableRegistry::get('Institution.InstitutionPositions');

                $selectedFTE = empty($fte) ? 0 : $fte;
                $excludePositions = $StaffTable->find();

                $startDate = new Date($startDate);

                $excludePositions = $excludePositions
                    ->select([
                        'position_id' => $StaffTable->aliasField('institution_position_id'),
                    ])
                    ->where([
                        $StaffTable->aliasField('institution_id') => $institutionId,
                    ])
                    ->group($StaffTable->aliasField('institution_position_id'))
                    ->having([
                        'OR' => [
                            'SUM('.$StaffTable->aliasField('FTE') .') >= ' => 1,
                            'SUM('.$StaffTable->aliasField('FTE') .') > ' => (1-$selectedFTE),
                        ]
                    ])
                    ->hydrate(false);

                if (!empty($endDate)) {
                    $endDate = new Date($endDate);
                    $excludePositions = $excludePositions->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
                } else {
                    $orCondition = [
                        $StaffTable->aliasField('end_date') . ' >= ' => $startDate,
                        $StaffTable->aliasField('end_date') . ' IS NULL'
                    ];
                    $excludePositions = $excludePositions->where([
                            'OR' => $orCondition
                        ]);
                }

                if ($this->AccessControl->isAdmin()) {
                    $userId = null;
                    $roles = [];
                } else {
                    $roles = $StaffTable->Institutions->getInstitutionRoles($userId, $institutionId);
                }

                // Filter by active status
                $activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
                $positionConditions = [];
                $positionConditions[$StaffTable->Positions->aliasField('institution_id')] = $institutionId;
                if (!empty($activeStatusId)) {
                    $positionConditions[$StaffTable->Positions->aliasField('status_id').' IN '] = $activeStatusId;
                }

                if ($selectedFTE > 0) {
                    $staffPositionsOptions = $StaffTable->Positions
                        ->find()
                        ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                        ->innerJoinWith('StaffPositionGrades')
                        ->where($positionConditions)
                        ->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type', 'grade_name' => 'StaffPositionGrades.name'])
                        ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
                        ->autoFields(true)
                        ->toArray();
                } else {
                    $staffPositionsOptions = [];
                }

                // Filter by role previlege
                $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
                $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
                $roleOptions = array_keys($roleOptions);
                $staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
                $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

                // Adding the opt group
                $types = $this->getSelectOptions('Staff.position_types');
                $excludePositions = array_column($excludePositions->toArray(), 'position_id');
                foreach ($staffPositionsOptions as $position) {
                    $name = $position->name . ' - ' . $position->grade_name;

                    $type = __($types[$position->type]);
                    // $options[] = ['value' => $position->id, 'group' => $type, 'name' => $name, 'disabled' => in_array($position->id, $excludePositions)];
                    $id = $position->id;

                    if (!in_array($position->id, $excludePositions)) {
                        $options[$id] = $name;
                    }
                }
            }

            $attr['options'] = $options;
            $attr['type'] = 'select';
        }

        return $attr;
    }
}
