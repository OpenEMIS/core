<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

// This file serves as an abstract class for StaffReleaseIn and StaffReleaseOut

class InstitutionStaffReleasesTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    // fte options
    public $fteOptions = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff_releases');
        parent::initialize($config);

        // Mandatory data
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('NewInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'new_institution_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        // New institution data
        $this->belongsTo('NewPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'new_institution_position_id']);
        $this->belongsTo('NewStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'new_staff_type_id']);
        // Previous institution data
        $this->belongsTo('PreviousInstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'previous_institution_staff_id']);
        $this->belongsTo('PreviousStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'previous_staff_type_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onReleaseStaff',
            'text' => 'Release Staff',
            'description' => 'Performing this action will release the staff to the selected institution.',
            'method' => 'onReleaseStaff',
            'unique' => true
        ]
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Workflow.checkIfCanAddButtons'] = 'checkIfCanAddButtons';
        $events['Workflow.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['UpdateAssignee.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['Workflow.setAutoAssignAssigneeFlag'] = 'setAutoAssignAssigneeFlag';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';

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

    public function onReleaseStaff(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $StaffTable = TableRegistry::get('Institution.Staff');
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $entity = $this->get($id);

        // add new institution staff record in new institution
        $incomingStaff = [
            'FTE' => $entity->new_FTE,
            'start_date' => $entity->new_start_date,
            'start_year' => $entity->new_start_date->year,
            'staff_id' => $entity->staff_id,
            'staff_type_id' => $entity->new_staff_type_id,
            'staff_status_id' => $StaffStatusesTable->getIdByCode('ASSIGNED'),
            'institution_id' => $entity->new_institution_id,
            'institution_position_id' => $entity->new_institution_position_id
        ];
        if (!empty($entity->new_end_date)) {
            $incomingStaff['end_date'] = $entity->new_end_date;
            $incomingStaff['end_year'] = $entity->new_end_date->year;
        }
        $newEntity = $StaffTable->newEntity($incomingStaff, ['validate' => 'AllowPositionType']);

        // Update institution_staff table the staff record
        if ($StaffTable->save($newEntity)) {
            if (!empty($entity->previous_institution_staff_id)) {
                $oldRecord = $StaffTable->get($entity->previous_institution_staff_id);
                $oldRecord->end_date = $entity->previous_end_date;
                $StaffTable->save($oldRecord);
            }
        }
    }

    public function checkIfCanAddButtons(Event $event, Entity $entity)
    {
        $canAddButtons = false;
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $ConfigStaffReleaseTable = TableRegistry::get('Configuration.ConfigStaffReleases');
        $isRestricted = $ConfigStaffReleaseTable->checkStaffReleaseRestrictedBetweenSameType($entity->previous_institution_id, $entity->new_institution_id);

        if (!$isRestricted) {
            
            if ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) {
                $canAddButtons = $this->NewInstitutions->isActive($entity->new_institution_id);
            } else if ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id) {
                $canAddButtons = $this->PreviousInstitutions->isActive($entity->previous_institution_id);
            }
            
        }
        
        $isRestrictedDiffrentProvider = $ConfigStaffReleaseTable->checkStaffReleaseRestrictedBetweenDifferentProvider($entity->previous_institution_id, $entity->new_institution_id);

        if (!$isRestrictedDiffrentProvider) {
            
            if ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) {
                $canAddButtons = $this->NewInstitutions->isActive($entity->new_institution_id);
            } else if ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id) {
                $canAddButtons = $this->PreviousInstitutions->isActive($entity->previous_institution_id);
            }
            
        }
        
        return $canAddButtons;
    }

    public function onSetCustomAssigneeParams(Event $event, Entity $entity, $params)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');

        if ($institutionOwner == self::INCOMING) {
            $params['institution_id'] = $entity->new_institution_id;
        } else if ($institutionOwner == self::OUTGOING) {
            $params['institution_id'] = $entity->previous_institution_id;
        }
        return $params;
    }

    public function setAutoAssignAssigneeFlag(Event $event, Entity $action)
    {
        $currentInstitutionOwner = $this->getWorkflowStepsParamValue($action->workflow_step_id, 'institution_owner');
        $nextInstitutionOwner = $this->getWorkflowStepsParamValue($action->next_workflow_step_id, 'institution_owner');
        return $currentInstitutionOwner != $nextInstitutionOwner ? 1 : 0;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('all_visible', ['type' => 'hidden']);
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'staff_id';
    }

    public function onGetStatusId(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->status->name . '</span>';
        } else {
            return '<span class="status past">' . $entity->status->name . '</span>';
        }
    }

    public function onGetWorkflowStatus(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->workflow_status . '</span>';
        } else {
            return '<span class="status past">' . $entity->workflow_status . '</span>';
        }
    }

    public function onGetPreviousFTE(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_FTE')) {
            $fte = $entity->previous_FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onGetPreviousInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetNewFTE(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('new_FTE')) {
            $fte = $entity->new_FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onGetNewInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('new_institution')) {
            $value = $entity->new_institution->code_name;
        }
        return $value;
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
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

    public function onGetPositionsHeld(Event $event, Entity $entity)
    {
        $value = $this->getPositionsHeld($entity);
        return $value;
    }

    public function getPositionsHeld(Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');

            if ($entity->has('previous_institution')) {
                $institutionId = $entity->previous_institution->id;
            }

            if ($entity->has('user')) {
                $staffId = $entity->user->id;
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
                    $this->PreviousInstitutionStaff->aliasField('staff_id') => $staffId,
                    $this->PreviousInstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                ])
                ->order([$this->PreviousInstitutionStaff->aliasField('created') => 'DESC'])
                ->toArray();

            $positions = [];
            foreach ($staffEntity as $staff) {
                $positions[$staff->id] = $staff->_matchingData['Positions']->name;
            }

            if (!empty($positions)) {
                $value = implode(",",$positions);
            }
        }
        return $value;
    }


    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->dirty('status_id')) {
            if (!$entity->all_visible) {
                $currentInstitutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
                $previousInstitutionOwner = $this->getWorkflowStepsParamValue($entity->getOriginal('status_id'), 'institution_owner');

                if ($currentInstitutionOwner != $previousInstitutionOwner) {
                    $this->updateAll(['all_visible' => 1], ['id' => $entity->id]);
                }
            }
        }
    }

    public function findInstitutionStaffReleaseIn(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $incomingInstitution = self::INCOMING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('new_institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::INCOMING, // institution_owner for the step can always see the record
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }

    public function findInstitutionStaffRelease(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $outgoingInstitution = self::OUTGOING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('previous_institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::OUTGOING, // institution_owner for the step can always see the record
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }
}
