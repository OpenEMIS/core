<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffTransfersTable extends AppTable {
    
    private $prevPositionTitle;

    public function initialize(array $config)
    {
        $this->table('institution_staff_transfers');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('NewInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'new_institution_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('NewPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'new_institution_position_id']);
        $this->belongsTo('NewStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'new_staff_type_id']);
        $this->belongsTo('PreviousInstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'previous_institution_staff_id']);
        $this->belongsTo('PreviousStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'previous_staff_type_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['new_staff_type_id', 'new_FTE', 'new_end_date', 'previous_institution_staff_id', 'previous_staff_type_id', 'previous_FTE', 'previous_end_date', 'previous_effective_date', 'transfer_type', 'all_visible'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select([
                'openemis_no' => 'Users.openemis_no',
                'identity_type' => 'IdentityTypes.name',
                'identity_number' => 'Users.identity_number',
                'prev_institution_code' => 'PreviousInstitutions.code',
                'next_institution_code' => 'NewInstitutions.code',
                'next_position_no' => 'NewPositions.position_no',
                'next_position_title' => 'StaffPositionTitles.name',
                'authorized_date' => $this->aliasField('created')
            ])
            ->contain(['Users.IdentityTypes','NewPositions.StaffPositionTitles','NewInstitutions','PreviousInstitutions','CreatedUser']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'StaffTransfers.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.assignee_id',
            'field' => 'assignee_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => __('Staff Name')
        ];

        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'PreviousInstitutions.code',
            'field' => 'prev_institution_code',
            'type' => 'string',
            'label' => __('Institution Code Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_institution_id',
            'field' => 'previous_institution_id',
            'type' => 'integer',
            'label' => __('Institution Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_position_no',
            'field' => 'previous_position_no',
            'type' => 'string',
            'label' => __('Position No Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_position_title',
            'field' => 'previous_position_title',
            'type' => 'string',
            'label' => __('Position Transferred From')
        ];

        $newFields[] = [
            'key' => 'NewInstitutions.code',
            'field' => 'next_institution_code',
            'type' => 'string',
            'label' => __('Institution code Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.new_institution_id',
            'field' => 'new_institution_id',
            'type' => 'integer',
            'label' => __('Institution Transferred To')
        ];

        $newFields[] = [
            'key' => 'NewPositions.position_no',
            'field' => 'next_position_no',
            'type' => 'string',
            'label' => __('Position No Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'next_position_title',
            'type' => 'string',
            'label' => __('Position Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.new_start_date',
            'field' => 'new_start_date',
            'type' => 'date',
            'label' => __('Transfer Start Date')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.created',
            'field' => 'authorized_date',
            'type' => 'date',
            'label' => __('Created Date')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.created_user_id',
            'field' => 'authorized_by',
            'type' => 'string',
            'label' => __('Created By')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.comment',
            'field' => 'comment',
            'type' => 'text',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetPreviousPositionNo(Event $event, Entity $entity)
    {
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $prevInstitutionStaff = [];
        $prevPositionNo = '';

        if ($entity->has('previous_institution_staff_id') && !empty($entity->previous_institution_staff_id)) {
            $prevInstitutionStaff = $InstitutionStaff->find()
                ->contain('Positions.StaffPositionTitles')
                ->where([$InstitutionStaff->aliasField('id') => $entity->previous_institution_staff_id])
                ->first();

        } else if ($entity->has('previous_institution_id') && !empty($entity->previous_institution_id)) {
            if ($entity->has('staff_id') && !empty($entity->staff_id)) {
                $prevInstitutionStaff = $InstitutionStaff->find()
                    ->contain('Positions.StaffPositionTitles')
                    ->where([
                        $InstitutionStaff->aliasField('institution_id') => $entity->previous_institution_id,
                        $InstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                    ])
                    ->order([$InstitutionStaff->aliasField('created DESC')])
                    ->first();
            }
        }

        if (!empty($prevInstitutionStaff)) {
            if (!empty($prevInstitutionStaff->position)) {
                $prevPositionNo = $prevInstitutionStaff->position->position_no;

                if (!empty($prevInstitutionStaff->position->staff_position_title)) {
                    $this->prevPositionTitle = $prevInstitutionStaff->position->staff_position_title->name;
                }
            }
        }
        return $prevPositionNo;
    }

    public function onExcelGetPreviousPositionTitle(Event $event, Entity $entity)
    { 
        return $this->prevPositionTitle;
    }

    public function onExcelGetAuthorizedBy(Event $event, Entity $entity)
    { 
        return $entity->created_user->name;
    }
}
