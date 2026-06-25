<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log; // POCOR-8532

// This file serves as an abstract class for StaffTransferIn and StaffTransferOut

class InstitutionStaffTransfersTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    // Transfer Type
    const FULL_TRANSFER = 1;
    const PARTIAL_TRANSFER = 2;
    const NO_CHANGE = 3;

    // fte options
    public $fteOptions = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_transfers');
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

        $this->belongsTo('InstitutionStaffShifts', ['className' => 'Institution.InstitutionStaffShifts','foreignKey' => 'staff_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1.00' => '100%'];
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onTransferStaff',
            'text' => 'Transfer Staff',
            'description' => 'Performing this action will transfer the staff to the selected institution.',
            'method' => 'onTransferStaff',
            'unique' => true
        ]
    ];

    public function implementedEvents(): array
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

    public function getWorkflowEvents(EventInterface $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onTransferStaff(EventInterface $event, $id, Entity $workflowTransitionEntity)
    {
        $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
        $StaffStatusesTable = TableRegistry::getTableLocator()->get('Staff.StaffStatuses');
        $entity = $this->get($id);
        $institutionStaffEntity = $StaffTable->find('all',
            ['conditions'=>
                ['staff_id'=>$entity->staff_id]
            ])->first(); //POCOR-7311
//        Log::debug(print_r(['entity' => $entity], true));
//        Log::debug(print_r(['institutionStaffEntity' => $institutionStaffEntity], true));

        // add new institution staff record in new institution
        $incomingStaff = [
            'FTE' => $entity->new_FTE,
            'start_date' => $entity->new_start_date,
            'start_year' => $entity->new_start_date->year,
            'staff_id' => $entity->staff_id,
            'is_homeroom' => $entity->is_homeroom,
            'staff_type_id' => $entity->new_staff_type_id,
            'staff_status_id' => $StaffStatusesTable->getIdByCode('ASSIGNED'),
            'institution_id' => $entity->new_institution_id,
            'institution_position_id' => $entity->new_institution_position_id,
            'staff_position_grade_id' => $institutionStaffEntity->staff_position_grade_id //POCOR-7311
        ];
        if (!empty($entity->new_end_date)) {
            $incomingStaff['end_date'] = $entity->new_end_date;
            $incomingStaff['end_year'] = $entity->new_end_date->year;
        }
//        Log::debug(print_r(['incomingStaff' => $incomingStaff], true));

        $newEntity = $StaffTable->newEntity($incomingStaff, ['validate' => 'AllowPositionType']);
//        Log::debug(print_r(['new Entity' => $newEntity], true));
        $savedEntity = $StaffTable->save($newEntity); // POCOR-8532
//        Log::debug(print_r(['saved Entity' => $savedEntity], true));

        if ($savedEntity) {
//            Log::debug(print_r($savedEntity, true));
            if (!empty($entity->previous_institution_staff_id)) {
                $transferType = $entity->transfer_type;
                $previous_institution_staff_id = $entity->previous_institution_staff_id; // POCOR-8532
                $oldRecord = $StaffTable->get($previous_institution_staff_id);
                $oldRecord->unset('newFTE'); // POCOR-8532
//                Log::debug(print_r(['oldRecord' => $oldRecord], true));

                if ($transferType == self::FULL_TRANSFER) {
                     // end previous institution staff record
                     $oldRecord->end_date = $entity->previous_end_date;

                     $oldRecord = $StaffTable->save($oldRecord); // POCOR-8532
                     $this->removeStaffFromSecurityGroups($previous_institution_staff_id); // POCOR-8532

                } else if ($transferType == self::PARTIAL_TRANSFER) {
                    // end previous institution staff record
                    $oldRecord->end_date = $entity->previous_end_date;
                    $savedOlderEntity = $StaffTable->save($oldRecord); // POCOR-8532
//                    Log::debug(print_r(['savedOlderEntity' => $savedOlderEntity], true)); // POCOR-8532

                    // add new institution staff record in previous institution
                    $newerRecord = [ // POCOR-8532
                        'FTE' => $entity->previous_FTE,
                        'start_date' => $entity->previous_effective_date,
                        'start_year' => $entity->previous_effective_date->year,
                        'staff_id' => $entity->staff_id,
                        'staff_type_id' => $entity->previous_staff_type_id,
                        'staff_status_id' => $StaffStatusesTable->getIdByCode('ASSIGNED'),
                        'institution_id' => $oldRecord->institution_id,
                        'institution_position_id' => $oldRecord->institution_position_id,
                        'staff_position_grade_id' => $institutionStaffEntity->staff_position_grade_id //POCOR-7311
                    ];
//                    Log::debug(print_r(['newerRecord' => $newerRecord], true));
                    $newerEntity = $StaffTable->newEntity($newerRecord, ['validate' => 'AllowPositionType']);
//                    Log::debug(print_r(['newerEntity' => $newerEntity], true));

                    $savedNewerEntity =  $StaffTable->save($newerEntity); // POCOR-8532
//                    Log::debug(print_r(['savedNewerEntity' => $savedNewerEntity], true));
                }
            }
        }
    }

    public function checkIfCanAddButtons(EventInterface $event, Entity $entity)
    {
        $canAddButtons = false;
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $getInstitutionId = $this->getQueryString('institution_id');
        //$requestInstitutionId = $this->request->getParam('institutionId');
        //$currentInstitutionId = isset($requestInstitutionId) ? $this->paramsDecode($requestInstitutionId)['id'] : $getInstitutionId;
        $requestInstitutionId = $this->request->getQueryParams()['institution_id'] ?? null; //POCOR-9373
        $currentInstitutionId = isset($requestInstitutionId) ? $requestInstitutionId : $getInstitutionId; //POCOR-9373
        $ConfigStaffTransfersTable = TableRegistry::getTableLocator()->get('Configuration.ConfigStaffTransfers');
        $isRestricted = $ConfigStaffTransfersTable->checkStaffTransferRestricted($entity->previous_institution_id, $entity->new_institution_id);

        if ($isRestricted) {
            $this->Alert->warning('StaffTransfers.restrictStaffTransfer', ['reset' => true]);
        } else {
            if ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) {
                $canAddButtons = $this->NewInstitutions->isActive($entity->new_institution_id);
            } else if ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id) {
                $canAddButtons = $this->PreviousInstitutions->isActive($entity->previous_institution_id);
            }
        }

        return $canAddButtons;
    }

    public function onSetCustomAssigneeParams(EventInterface $event, Entity $entity, $params)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');

        if ($institutionOwner == self::INCOMING) {
            $params['institution_id'] = $entity->new_institution_id;
        } else if ($institutionOwner == self::OUTGOING) {
            $params['institution_id'] = $entity->previous_institution_id;
        }
        return $params;
    }

    public function setAutoAssignAssigneeFlag(EventInterface $event, Entity $action)
    {
        $currentInstitutionOwner = $this->getWorkflowStepsParamValue($action->workflow_step_id, 'institution_owner');
        $nextInstitutionOwner = $this->getWorkflowStepsParamValue($action->next_workflow_step_id, 'institution_owner');
        return $currentInstitutionOwner != $nextInstitutionOwner ? 1 : 0;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('all_visible', ['type' => 'hidden']);
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'staff_id';
    }

    // for index
    public function onGetStatusId(EventInterface $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $getInstitutionId = $this->getQueryString('institution_id');
        $requestInstitutionId = $this->request->getParam('institutionId');
        $currentInstitutionId = isset($requestInstitutionId) ? $this->paramsDecode($requestInstitutionId)['id'] : $getInstitutionId;
        //$currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->status->name . '</span>';
        } else {
            return '<span class="status past">' . $entity->status->name . '</span>';
        }
    }

    // for view
    public function onGetWorkflowStatus(EventInterface $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $getInstitutionId = $this->getQueryString('institution_id');
        $requestInstitutionId = $this->request->getParam('institutionId');
        $currentInstitutionId = isset($requestInstitutionId) ? $this->paramsDecode($requestInstitutionId)['id'] : $getInstitutionId;
       //$currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->new_institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->workflow_status . '</span>';
        } else {
            return '<span class="status past">' . $entity->workflow_status . '</span>';
        }
    }

    public function onGetNewFTE(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('new_FTE')) {
            $fte = $entity->new_FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onGetPreviousFTE(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_FTE')) {
            $fte = $entity->previous_FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onGetPreviousInstitutionId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetNewInstitutionId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('new_institution')) {
            $value = $entity->new_institution->code_name;
        }
        return $value;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
//        Log::debug(print_r([__FUNCTION__ => $entity], true));
        foreach($entity->shifts_id['_ids'] as $shiftId)
        {
            $shiftData = array( 'staff_id'=> $entity->staff_id ,'shift_id'=> $shiftId);
            $saveShift = $this->InstitutionStaffShifts->newEntity($shiftData);
            $this->InstitutionStaffShifts->save($saveShift);
        }

        if (!$entity->isNew() && $entity->getDirty('status_id')) {
            if (!$entity->all_visible) {
                $currentInstitutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
                $previousInstitutionOwner = $this->getWorkflowStepsParamValue($entity->getOriginal('status_id'), 'institution_owner');

                if ($currentInstitutionOwner != $previousInstitutionOwner) {
                    $this->updateAll(['all_visible' => 1], ['id' => $entity->id]);
                }
            }
        }
    }

    public function findInstitutionStaffTransferIn(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $incomingInstitution = self::INCOMING;
        $pending = isset($options['pending_records']) ? $options['pending_records'] : false;

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

    public function findInstitutionStaffTransferOut(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $outgoingInstitution = self::OUTGOING;
        $pending = isset($options['pending_records']) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('previous_institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::OUTGOING, // institution_owner for the step can always see the record
                    'WorkflowStepsParams.value' => self::INCOMING, // POCOR-4998
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }

    // POCOR-8532: refactor
    private function removeStaffFromSecurityGroups($previous_institution_staff_id)
    {
        $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff'); // POCOR-8532
        $oldRecord = $StaffTable->get($previous_institution_staff_id); // POCOR-8532
        $security_group_user_id = $oldRecord->security_group_user_id;
        $oldRecord->security_group_user_id = null;
        $oldRecord->unset('newFTE'); // POCOR-8532
        $StaffTable->save($oldRecord);
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        if ($security_group_user_id) { // POCOR-8532
            $SecurityGroupUsers->deleteAll([
                $SecurityGroupUsers->aliasField($SecurityGroupUsers->getPrimaryKey()) => $security_group_user_id
            ]);
        }
    }

}
